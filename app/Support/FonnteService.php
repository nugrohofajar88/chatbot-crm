<?php

namespace App\Support;

use App\Support\Contracts\WhatsappGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gateway WhatsApp via Fonnte (fonnte.com).
 *   - Kirim: POST {base}/send  (form: target, message)
 *   - Auth: header Authorization = token device.
 *   - Sukses: response JSON status=true.
 *
 * Diadaptasi dari proyek larashop-be.
 */
class FonnteService implements WhatsappGateway
{
    public function sendMessage(string $phone, string $message): ?string
    {
        $base = rtrim((string) config('services.fonnte.base_url', 'https://api.fonnte.com'), '/');
        $token = (string) config('services.fonnte.token');

        if ($token === '') {
            Log::error('fonnte.send.no_token');

            return null;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(15)
                ->post($base.'/send', [
                    'target' => $this->normalize($phone),
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::error('fonnte.send.exception', ['phone' => $phone, 'message' => $e->getMessage()]);

            return null;
        }

        $ok = $response->successful() && (bool) data_get($response->json(), 'status', false);

        Log::info('fonnte.send', [
            'phone' => $phone,
            'http' => $response->status(),
            'ok' => $ok,
        ]);

        return $ok ? ((string) data_get($response->json(), 'id.0', '') ?: 'sent') : null;
    }

    /**
     * Kirim media (gambar/dokumen) via Fonnte. URL WAJIB bisa diakses publik
     * (Fonnte mengambil file dari URL ini). `filename` dengan ekstensi WAJIB
     * supaya Fonnte mengenali tipe file.
     */
    public function sendMedia(string $phone, string $url, string $filename, string $caption = ''): ?string
    {
        $base = rtrim((string) config('services.fonnte.base_url', 'https://api.fonnte.com'), '/');
        $token = (string) config('services.fonnte.token');

        if ($token === '') {
            Log::error('fonnte.media.no_token');

            return null;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->asForm()
                ->timeout(25)
                ->post($base.'/send', [
                    'target' => $this->normalize($phone),
                    'message' => $caption,
                    'url' => $url,
                    'filename' => $filename,
                ]);
        } catch (\Throwable $e) {
            Log::error('fonnte.media.exception', ['phone' => $phone, 'message' => $e->getMessage()]);

            return null;
        }

        $ok = $response->successful() && (bool) data_get($response->json(), 'status', false);

        Log::info('fonnte.media', [
            'phone' => $phone,
            'http' => $response->status(),
            'ok' => $ok,
            'url' => $url,
            'response' => $response->json() ?? $response->body(),
        ]);

        return $ok ? ((string) data_get($response->json(), 'id.0', '') ?: 'sent') : null;
    }

    /** Fonnte terima 08xx maupun 62xx; normalkan ke 62 untuk konsisten. */
    public function normalize(string $phone): string
    {
        $p = preg_replace('/[^0-9]/', '', $phone) ?? '';
        if (str_starts_with($p, '0')) {
            $p = '62'.substr($p, 1);
        }

        return $p;
    }
}
