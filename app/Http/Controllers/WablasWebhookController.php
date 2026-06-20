<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Support\WaInboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Wablas - pesan WhatsApp masuk dari calon pembeli.
 * Field Wablas (incoming): sender/phone (nomor), message (teks), pushName,
 *   messageType, isFromMe, isGroup, id.
 * Pola diadaptasi dari larashop-be (secret + dedup), logika diarahkan ke CRM.
 */
class WablasWebhookController extends Controller
{
    public function handle(Request $request, WaInboundService $inbound, ?string $secret = null): JsonResponse
    {
        // 1) Verifikasi secret (kalau di-set).
        $expected = (string) config('services.wablas.webhook_secret');
        $provided = ($secret !== null && $secret !== '')
            ? $secret
            : (string) ($request->header('X-Webhook-Secret') ?? $request->query('secret', ''));

        if ($expected !== '' && ! hash_equals($expected, $provided)) {
            Log::warning('wablas.webhook.unauthorized', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('wablas.webhook.received', ['payload' => $payload]);

        // Wablas incoming: `phone` = nomor lawan bicara (lead); `sender` = nomor
        // device kita sendiri. Jadi `phone` dipakai sebagai kontak; `sender` hanya
        // untuk mendeteksi & menolak pesan yang nyasar ke diri sendiri (anti-loop).
        $device = trim((string) ($payload['sender'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        $name = trim((string) ($payload['pushName'] ?? $payload['name'] ?? '')) ?: null;
        $isFromMe = filter_var($payload['isFromMe'] ?? false, FILTER_VALIDATE_BOOL);
        $isGroup = filter_var($payload['isGroup'] ?? false, FILTER_VALIDATE_BOOL);

        // Abaikan: tanpa pengirim/teks, dari device sendiri, grup, atau nomor lead
        // sama dengan nomor device (cegah balasan AI nyasar balik ke diri sendiri).
        if ($phone === '' || $message === '' || $isFromMe || $isGroup || ($device !== '' && $phone === $device)) {
            return response()->json(['status' => 'ignored']);
        }

        // 2) Dedup (Wablas bisa retry webhook yang sama).
        $messageId = trim((string) ($payload['id'] ?? ''));
        $dedupKey = 'wablas:msg:'.($messageId !== '' ? $messageId : md5($phone.'|'.$message));

        if (! Cache::add($dedupKey, 1, now()->addMinutes(3))) {
            return response()->json(['status' => 'duplicate']);
        }

        // 3) Proses: simpan + (jika ai_enabled) auto-reply.
        $inbound->handleIncoming($phone, $message, $name);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Tracking/status callback Wablas (delivered/read) untuk pesan KELUAR.
     * Diarahkan dari kolom "Tracking URL for get status message" di device Wablas.
     * Cocokkan `id` ke message.wa_message_id, lalu majukan watermark di percakapan
     * (last_delivered_at / last_read_at). Format payload bisa flat atau di bawah `data`.
     */
    public function track(Request $request, ?string $secret = null): JsonResponse
    {
        $expected = (string) config('services.wablas.webhook_secret');
        $provided = ($secret !== null && $secret !== '')
            ? $secret
            : (string) ($request->header('X-Webhook-Secret') ?? $request->query('secret', ''));

        if ($expected !== '' && ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('wablas.tracking.received', ['payload' => $payload]);

        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $id = trim((string) ($data['id'] ?? ''));
        $status = strtolower(trim((string) ($data['status'] ?? '')));

        if ($id === '' || $status === '') {
            return response()->json(['status' => 'ignored']);
        }

        $message = Message::with('conversation')->where('wa_message_id', $id)->first();
        if (! $message || ! $message->conversation) {
            return response()->json(['status' => 'unknown']);
        }

        $conv = $message->conversation;
        $at = $message->created_at;

        // 'read' menyiratkan sudah delivered juga; majukan watermark (tak pernah mundur).
        if (in_array($status, ['read', 'played'], true)) {
            $conv->update([
                'last_read_at' => $this->advance($conv->last_read_at, $at),
                'last_delivered_at' => $this->advance($conv->last_delivered_at, $at),
            ]);
        } elseif (in_array($status, ['delivered', 'received'], true)) {
            $conv->update(['last_delivered_at' => $this->advance($conv->last_delivered_at, $at)]);
        }

        return response()->json(['status' => 'ok']);
    }

    /** Kembalikan waktu yang lebih baru (watermark hanya boleh maju). */
    private function advance(?\Illuminate\Support\Carbon $current, \Illuminate\Support\Carbon $candidate): \Illuminate\Support\Carbon
    {
        return ($current === null || $current->lt($candidate)) ? $candidate : $current;
    }
}
