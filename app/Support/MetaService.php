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
    /**
     * Resolusi base URL + token sesuai channel.
     *   - messenger: graph.facebook.com + Page Access Token.
     *   - instagram: graph.instagram.com + Instagram access token (Instagram Login).
     *
     * @return array{base: string, token: string}
     */
    private function endpoint(string $channel): array
    {
        $version = (string) config('services.meta.graph_version', 'v21.0');

        if ($channel === 'instagram') {
            return [
                'base' => "https://graph.instagram.com/{$version}",
                'token' => (string) config('services.meta.ig_access_token'),
            ];
        }

        return [
            'base' => "https://graph.facebook.com/{$version}",
            'token' => (string) config('services.meta.page_access_token'),
        ];
    }

    /** Kirim pesan teks. Mengembalikan message_id Meta bila sukses, null bila gagal. */
    public function sendMessage(string $recipientId, string $text, string $channel = 'messenger'): ?string
    {
        ['base' => $base, 'token' => $token] = $this->endpoint($channel);

        if ($token === '') {
            Log::error('meta.send.no_token', ['channel' => $channel]);

            return null;
        }

        // IG (Instagram Login) tidak memakai messaging_type.
        $body = ['recipient' => ['id' => $recipientId], 'message' => ['text' => $text]];
        if ($channel !== 'instagram') {
            $body['messaging_type'] = 'RESPONSE';
        }

        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->post("{$base}/me/messages", $body);
        } catch (\Throwable $e) {
            Log::error('meta.send.exception', ['recipient' => $recipientId, 'channel' => $channel, 'message' => $e->getMessage()]);

            return null;
        }

        $ok = $response->successful();
        $messageId = (string) $response->json('message_id', '');

        Log::info('meta.send', [
            'recipient' => $recipientId,
            'channel' => $channel,
            'http' => $response->status(),
            'ok' => $ok,
            'response' => $response->json() ?? $response->body(),
        ]);

        return $ok ? ($messageId !== '' ? $messageId : 'sent') : null;
    }

    /** Balas komentar publik. Instagram: POST /{id}/replies; Facebook: POST /{id}/comments. */
    public function replyToComment(string $commentId, string $text, string $channel = 'instagram'): ?string
    {
        ['base' => $base, 'token' => $token] = $this->endpoint($channel);

        if ($token === '' || $commentId === '') {
            Log::error('meta.comment.no_token', ['channel' => $channel]);

            return null;
        }

        $sub = $channel === 'instagram' ? 'replies' : 'comments';

        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->post("{$base}/{$commentId}/{$sub}", ['message' => $text]);
        } catch (\Throwable $e) {
            Log::error('meta.comment.exception', ['comment' => $commentId, 'message' => $e->getMessage()]);

            return null;
        }

        $ok = $response->successful();
        $id = (string) $response->json('id', '');

        Log::info('meta.comment.reply', [
            'comment' => $commentId,
            'channel' => $channel,
            'http' => $response->status(),
            'ok' => $ok,
            'response' => $response->json() ?? $response->body(),
        ]);

        return $ok ? ($id !== '' ? $id : 'sent') : null;
    }

    /**
     * Private reply: kirim DM ke pengomentar via POST /{comment-id}/private_replies.
     * Membuka thread Messenger/IG ke orang yang berkomentar (1x per komentar, dalam 7 hari).
     * Inilah jembatan komentar iklan -> lead.
     */
    public function privateReply(string $commentId, string $text, string $channel = 'messenger'): ?string
    {
        ['base' => $base, 'token' => $token] = $this->endpoint($channel);

        if ($token === '' || $commentId === '') {
            Log::error('meta.private_reply.no_token', ['channel' => $channel]);

            return null;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(20)
                ->post("{$base}/{$commentId}/private_replies", ['message' => $text]);
        } catch (\Throwable $e) {
            Log::error('meta.private_reply.exception', ['comment' => $commentId, 'message' => $e->getMessage()]);

            return null;
        }

        $ok = $response->successful();
        $id = (string) ($response->json('message_id') ?? $response->json('id') ?? '');

        Log::info('meta.private_reply', [
            'comment' => $commentId,
            'channel' => $channel,
            'http' => $response->status(),
            'ok' => $ok,
            'response' => $response->json() ?? $response->body(),
        ]);

        return $ok ? ($id !== '' ? $id : 'sent') : null;
    }

    /**
     * Ambil nama pengguna dari PSID/IGSID via Graph User Profile API.
     * Butuh izin pages_messaging (Messenger) / instagram_manage_messages (IG)
     * dan pengguna sudah pernah mengirim pesan. Mengembalikan null bila gagal.
     */
    public function fetchProfileName(string $userId, string $channel = 'messenger'): ?string
    {
        ['base' => $base, 'token' => $token] = $this->endpoint($channel);

        if ($token === '' || $userId === '') {
            return null;
        }

        $fields = $channel === 'instagram' ? 'name,username' : 'first_name,last_name';

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("{$base}/{$userId}", ['fields' => $fields]);
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
        // Messenger ditandatangani dgn App Secret Facebook; Instagram (Instagram
        // Login) dgn Instagram App Secret. Coba keduanya — terima bila salah satu cocok.
        $secrets = array_values(array_filter([
            (string) config('services.meta.app_secret'),
            (string) config('services.meta.ig_app_secret'),
        ]));

        if ($secrets === []) {
            return true; // mode dev: tidak ada secret yang diset
        }

        if (! is_string($signatureHeader) || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        foreach ($secrets as $secret) {
            if (hash_equals('sha256='.hash_hmac('sha256', $rawBody, $secret), $signatureHeader)) {
                return true;
            }
        }

        return false;
    }
}
