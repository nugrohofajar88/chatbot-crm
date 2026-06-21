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
        $token = (string) config('services.meta.ig_access_token');
        if ($token === '') {
            return ['ok' => false, 'error' => 'Instagram Access Token kosong'];
        }
        if (! $imageUrl) {
            return ['ok' => false, 'error' => 'Instagram wajib menyertakan gambar'];
        }

        $base = "https://graph.instagram.com/{$this->version()}";

        try {
            // 1. Buat container media.
            $container = Http::withToken($token)->timeout(30)
                ->post("{$base}/me/media", ['image_url' => $imageUrl, 'caption' => $caption]);

            $creationId = (string) $container->json('id', '');
            if (! $container->successful() || $creationId === '') {
                Log::warning('post.instagram.container_failed', ['response' => $container->json() ?? $container->body()]);

                return ['ok' => false, 'error' => $container->json('error.message') ?? $container->body()];
            }

            // 2. Publish container.
            $publish = Http::withToken($token)->timeout(30)
                ->post("{$base}/me/media_publish", ['creation_id' => $creationId]);
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
