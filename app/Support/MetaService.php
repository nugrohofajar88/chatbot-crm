<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pengirim pesan Meta (Facebook Messenger & Instagram) via Graph API "Send API".
 * Endpoint sama untuk kedua channel:
 *   POST https://graph.facebook.com/{version}/me/messages
 *   body: { recipient: {id: PSID/IGSID}, messaging_type: RESPONSE, message: {text} }
 *   auth: Page Access Token (Bearer).
 */
class MetaService
{
    public function sendMessage(string $recipientId, string $text): bool
    {
        $token = (string) config('services.meta.page_access_token');

        if ($token === '') {
            Log::error('meta.send.no_token');

            return false;
        }

        $version = (string) config('services.meta.graph_version', 'v21.0');
        $url = "https://graph.facebook.com/{$version}/me/messages";

        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->post($url, [
                    'recipient' => ['id' => $recipientId],
                    'messaging_type' => 'RESPONSE',
                    'message' => ['text' => $text],
                ]);
        } catch (\Throwable $e) {
            Log::error('meta.send.exception', ['recipient' => $recipientId, 'message' => $e->getMessage()]);

            return false;
        }

        $ok = $response->successful();

        Log::info('meta.send', [
            'recipient' => $recipientId,
            'http' => $response->status(),
            'ok' => $ok,
            'response' => $response->json() ?? $response->body(),
        ]);

        return $ok;
    }

    /**
     * Ambil nama pengguna dari PSID/IGSID via Graph User Profile API.
     * Butuh izin pages_messaging (Messenger) / instagram_manage_messages (IG)
     * dan pengguna sudah pernah mengirim pesan. Mengembalikan null bila gagal.
     */
    public function fetchProfileName(string $userId, string $channel = 'messenger'): ?string
    {
        $token = (string) config('services.meta.page_access_token');

        if ($token === '' || $userId === '') {
            return null;
        }

        $version = (string) config('services.meta.graph_version', 'v21.0');
        $fields = $channel === 'instagram' ? 'name,username' : 'first_name,last_name';

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("https://graph.facebook.com/{$version}/{$userId}", ['fields' => $fields]);
        } catch (\Throwable $e) {
            Log::info('meta.profile.exception', ['user' => $userId, 'message' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::info('meta.profile.failed', ['user' => $userId, 'error' => $response->json('error') ?? $response->body()]);

            return null;
        }

        $j = $response->json();

        $name = $channel === 'instagram'
            ? (trim((string) ($j['name'] ?? '')) ?: trim((string) ($j['username'] ?? '')))
            : trim(trim((string) ($j['first_name'] ?? '')).' '.trim((string) ($j['last_name'] ?? '')));

        return $name !== '' ? $name : null;
    }

    /**
     * Verifikasi header X-Hub-Signature-256 terhadap body mentah memakai app_secret.
     * Bila app_secret belum diset (mode dev), verifikasi dilewati.
     */
    public function verifySignature(?string $signatureHeader, string $rawBody): bool
    {
        $secret = (string) config('services.meta.app_secret');

        if ($secret === '') {
            return true;
        }

        if (! is_string($signatureHeader) || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $signatureHeader);
    }
}
