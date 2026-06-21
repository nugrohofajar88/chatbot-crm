<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Publikasi postingan ke Facebook Page & Instagram (TERPISAH dari messaging).
 *   - FB:  POST /me/feed (teks) atau /me/photos (gambar) — Page token.
 *   - IG:  2 langkah: POST /me/media (container) -> /me/media_publish — IG token.
 *          Instagram WAJIB gambar (image_url publik) + izin content_publish.
 *
 * @return array{ok: bool, id?: string, error?: string}
 */
class ContentPublisher
{
    private function version(): string
    {
        return (string) config('services.meta.graph_version', 'v21.0');
    }

    public function publishFacebook(string $caption, ?string $imageUrl): array
    {
        $token = (string) config('services.meta.page_access_token');
        if ($token === '') {
            return ['ok' => false, 'error' => 'Page Access Token kosong'];
        }

        $base = "https://graph.facebook.com/{$this->version()}";

        try {
            $res = $imageUrl
                ? Http::withToken($token)->timeout(30)->post("{$base}/me/photos", ['url' => $imageUrl, 'caption' => $caption])
                : Http::withToken($token)->timeout(30)->post("{$base}/me/feed", ['message' => $caption]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        Log::info('post.facebook', ['ok' => $res->successful(), 'http' => $res->status(), 'response' => $res->json() ?? $res->body()]);

        if (! $res->successful()) {
            return ['ok' => false, 'error' => $res->json('error.message') ?? $res->body()];
        }

        return ['ok' => true, 'id' => (string) ($res->json('post_id') ?? $res->json('id') ?? '')];
    }

    public function publishInstagram(string $caption, ?string $imageUrl): array
    {
        // Pakai PAGE token (graph.facebook.com) — butuh izin instagram_content_publish
        // + instagram_basic, dan IG harus terhubung ke Page (instagram_business_account).
        $token = (string) config('services.meta.page_access_token');
        if ($token === '') {
            return ['ok' => false, 'error' => 'Page Access Token kosong'];
        }
        if (! $imageUrl) {
            return ['ok' => false, 'error' => 'Instagram wajib menyertakan gambar'];
        }

        $base = "https://graph.facebook.com/{$this->version()}";

        // 0. IG business account id yang terhubung ke Page.
        $me = Http::withToken($token)->timeout(20)->get("{$base}/me", ['fields' => 'instagram_business_account']);
        $igId = (string) ($me->json('instagram_business_account.id') ?? '');
        if ($igId === '') {
            Log::warning('post.instagram.no_ig_account', ['response' => $me->json() ?? $me->body()]);

            return ['ok' => false, 'error' => 'IG tidak terhubung ke Page / token kurang izin instagram_basic'];
        }

        try {
            // 1. Buat container media.
            $container = Http::withToken($token)->timeout(30)
                ->post("{$base}/{$igId}/media", ['image_url' => $imageUrl, 'caption' => $caption]);

            $creationId = (string) $container->json('id', '');
            if (! $container->successful() || $creationId === '') {
                Log::warning('post.instagram.container_failed', ['response' => $container->json() ?? $container->body()]);

                return ['ok' => false, 'error' => $container->json('error.message') ?? $container->body()];
            }

            // 1b. Tunggu IG selesai memproses gambar (status_code FINISHED) sebelum publish.
            $ready = false;
            for ($i = 0; $i < 8; $i++) {
                $status = Http::withToken($token)->timeout(15)->get("{$base}/{$creationId}", ['fields' => 'status_code']);
                $code = (string) $status->json('status_code', '');
                if ($code === 'FINISHED') {
                    $ready = true;
                    break;
                }
                if ($code === 'ERROR') {
                    Log::warning('post.instagram.container_error', ['response' => $status->json() ?? $status->body()]);

                    return ['ok' => false, 'error' => 'Gambar gagal diproses Instagram'];
                }
                sleep(2);
            }
            if (! $ready) {
                return ['ok' => false, 'error' => 'Gambar belum siap diproses Instagram — coba publish lagi'];
            }

            // 2. Publish container.
            $publish = Http::withToken($token)->timeout(30)
                ->post("{$base}/{$igId}/media_publish", ['creation_id' => $creationId]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        Log::info('post.instagram', ['ok' => $publish->successful(), 'http' => $publish->status(), 'response' => $publish->json() ?? $publish->body()]);

        if (! $publish->successful()) {
            return ['ok' => false, 'error' => $publish->json('error.message') ?? $publish->body()];
        }

        return ['ok' => true, 'id' => (string) ($publish->json('id') ?? '')];
    }
}
