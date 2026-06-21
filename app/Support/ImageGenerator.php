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
    /** Generate gambar -> simpan ke public/uploads -> kembalikan path relatif (atau null). */
    public static function generate(string $prompt): ?string
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

            return is_string($path) ? $path : null;
        } catch (\Throwable $e) {
            Log::warning('post.image.failed', ['provider' => $provider, 'model' => $model, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
