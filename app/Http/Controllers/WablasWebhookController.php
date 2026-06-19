<?php

namespace App\Http\Controllers;

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

        // Wablas: `sender` = nomor pengirim asli; `phone` kadang nomor device.
        $phone = trim((string) ($payload['sender'] ?? $payload['phone'] ?? ''));
        $message = trim((string) ($payload['message'] ?? ''));
        $name = trim((string) ($payload['pushName'] ?? $payload['name'] ?? '')) ?: null;
        $isFromMe = filter_var($payload['isFromMe'] ?? false, FILTER_VALIDATE_BOOL);
        $isGroup = filter_var($payload['isGroup'] ?? false, FILTER_VALIDATE_BOOL);

        // Abaikan pesan dari device sendiri / grup / tanpa pengirim / tanpa teks.
        if ($phone === '' || $message === '' || $isFromMe || $isGroup) {
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
}
