<?php

namespace App\Http\Controllers;

use App\Support\WaInboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Fonnte - pesan WhatsApp masuk dari calon pembeli.
 * Field Fonnte (incoming): sender (nomor), message (teks), name, group.
 * Pola diadaptasi dari larashop-be (secret + dedup), logika diarahkan ke CRM.
 */
class FonnteWebhookController extends Controller
{
    public function handle(Request $request, WaInboundService $inbound, ?string $secret = null): JsonResponse
    {
        // 1) Verifikasi secret.
        $expected = (string) config('services.fonnte.webhook_secret');
        $provided = ($secret !== null && $secret !== '')
            ? $secret
            : (string) ($request->header('X-Webhook-Secret') ?? $request->query('secret', ''));

        if ($expected !== '' && ! hash_equals($expected, $provided)) {
            Log::warning('fonnte.webhook.unauthorized', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('fonnte.webhook.received', ['payload' => $payload]);

        $phone = trim((string) ($payload['sender'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        $name = trim((string) ($payload['name'] ?? '')) ?: null;
        $isGroup = trim((string) ($payload['group'] ?? '')) !== '';

        // Abaikan pesan grup / tanpa pengirim / tanpa teks. Balas 200 agar tak retry.
        if ($phone === '' || $message === '' || $isGroup) {
            return response()->json(['status' => 'ignored']);
        }

        // 2) Dedup: Fonnte bisa kirim webhook sama berkali-kali (retry saat balasan lambat).
        $messageId = trim((string) ($payload['id'] ?? ''));
        $dedupKey = 'fonnte:msg:'.($messageId !== '' ? $messageId : md5($phone.'|'.$message));

        if (! Cache::add($dedupKey, 1, now()->addMinutes(3))) {
            return response()->json(['status' => 'duplicate']);
        }

        // 3) Proses: simpan + (jika ai_enabled) auto-reply.
        $inbound->handleIncoming($phone, $message, $name);

        return response()->json(['status' => 'ok']);
    }
}
