<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Image;

/**
 * Generator GAMBAR AI (terpisah dari teks). Provider/model/rasio bisa diatur
 * lewat settings (IMAGE_PROVIDER / IMAGE_MODEL / IMAGE_ASPECT) — jadi teks bisa
 * OpenAI/Gemini, gambar bisa model lain (mis. Gemini image / "Nano Banana").
 */
class ImageGenerator
{
    /**
     * Generate gambar -> simpan ke public/uploads.
     *
     * @return array{ok:bool, path?:string, error?:string}
     */
    public static function generate(string $prompt): array
    {
        $provider = (string) Setting::get('IMAGE_PROVIDER', (string) config('ai.default_for_images', 'gemini'));
        $model = Setting::get('IMAGE_MODEL') ?: null;
        $aspect = (string) Setting::get('IMAGE_ASPECT', 'square');

        try {
            $pending = Image::of($prompt);
            $pending = match ($aspect) {
                'portrait' => $pending->portrait(),
                'landscape' => $pending->landscape(),
                default => $pending->square(),
            };

            $path = $pending->generate($provider, $model)
                ->storePublicly('posts', 'public_uploads');

            if (! is_string($path)) {
                return ['ok' => false, 'error' => 'Gambar gagal disimpan ke storage.'];
            }

            return ['ok' => true, 'path' => $path];
        } catch (\Throwable $e) {
            Log::warning('post.image.failed', ['provider' => $provider, 'model' => $model, 'error' => $e->getMessage()]);

            return ['ok' => false, 'error' => self::cleanError($provider, (string) $model, $e)];
        }
    }

    /** Ambil pesan ringkas dari error provider (buang JSON/HTTP mentah; tahan walau JSON terpotong). */
    protected static function cleanError(string $provider, string $model, \Throwable $e): string
    {
        $raw = $e->getMessage();

        // Ambil nilai "message":"..." PERTAMA — bekerja walau body JSON terpotong.
        if (preg_match('/"message"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/', $raw, $m)) {
            $decoded = json_decode('"'.$m[1].'"');
            $msg = is_string($decoded) ? $decoded : $m[1];
        } else {
            // Buang prefix "HTTP request returned status code XXX:" bila ada.
            $msg = preg_replace('/^HTTP request returned status code \d+:\s*/i', '', $raw) ?? $raw;
        }

        $msg = trim($msg);
        if (mb_strlen($msg) > 240) {
            $msg = mb_substr($msg, 0, 240).'…';
        }

        $label = $model !== '' ? "{$provider} · {$model}" : $provider;

        return "[{$label}] {$msg}";
    }
}
