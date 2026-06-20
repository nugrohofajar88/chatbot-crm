<?php

namespace App\Support;

use App\Support\Contracts\WhatsappGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gateway WhatsApp via Wablas (wablas.com).
 *   - Teks:  POST {base}/api/send-message  (form: phone, message)
 *   - Media: POST {base}/api/send-image | /api/send-document
 *   - Auth:  header Authorization = "{token}.{secret}" (v2) atau "{token}" (v1)
 *   - Sukses: response JSON status=true.
 *
 * Diadaptasi dari proyek larashop-be.
 */
class WablasService implements WhatsappGateway
{
    public function sendMessage(string $phone, string $message): ?string
    {
        return $this->send('/api/send-message', [
            'phone' => $this->normalize($phone),
            'message' => $message,
        ], $phone);
    }

    public function sendMedia(string $phone, string $url, string $filename, string $caption = ''): ?string
    {
        $isImage = preg_match('/\.(jpe?g|png|webp|gif)$/i', $filename) === 1;

        if ($isImage) {
            return $this->send('/api/send-image', [
                'phone' => $this->normalize($phone),
                'image' => $url,
                'caption' => $caption,
            ], $phone);
        }

        return $this->send('/api/send-document', [
            'phone' => $this->normalize($phone),
            'document' => $url,
            'caption' => $caption,
        ], $phone);
    }

    public function normalize(string $phone): string
    {
        $p = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if (str_starts_with($p, '0')) {
            $p = '62'.substr($p, 1);
        }

        return $p;
    }

    protected function send(string $endpoint, array $payload, string $phone): ?string
    {
        $base = rtrim((string) config('services.wablas.base_url', 'https://wablas.com'), '/');
        $token = (string) config('services.wablas.token');
        $secret = (string) config('services.wablas.secret_key');

        if ($token === '') {
            Log::error('wablas.send.no_token');

            return null;
        }

        // Wablas v2: Authorization = "{token}.{secret}"; v1: cukup "{token}".
        $authorization = $secret !== '' ? $token.'.'.$secret : $token;

        try {
            $response = Http::withHeaders(['Authorization' => $authorization])
                ->asForm()
                ->timeout(20)
                ->post($base.$endpoint, $payload);
        } catch (\Throwable $e) {
            Log::error('wablas.send.exception', ['phone' => $phone, 'endpoint' => $endpoint, 'message' => $e->getMessage()]);

            return null;
        }

        $ok = $response->successful() && (bool) data_get($response->json(), 'status', false);
        $messageId = (string) data_get($response->json(), 'data.messages.0.id', '');

        Log::info('wablas.send', [
            'phone' => $phone,
            'endpoint' => $endpoint,
            'http' => $response->status(),
            'ok' => $ok,
            'response' => $response->json() ?? $response->body(),
        ]);

        // Sukses: kembalikan message id (untuk pelacakan status). Bila id tak ada
        // di response, pakai sentinel 'sent' agar tetap dianggap berhasil.
        return $ok ? ($messageId !== '' ? $messageId : 'sent') : null;
    }
}
