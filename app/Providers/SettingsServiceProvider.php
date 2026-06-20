<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

/**
 * Jembatan settings DB -> config runtime. Saat boot, nilai dari tabel `settings`
 * meng-override config() yang relevan, sehingga seluruh kode yang sudah memakai
 * config('services.meta...') dll tetap jalan tanpa diubah — sumbernya pindah ke DB.
 *
 * Aman walau tabel/DB belum siap (mis. saat migrate awal): dibungkus try/catch.
 * Nilai DB di-cache (Setting::map) & otomatis dibuang saat ada perubahan, jadi
 * tidak perlu `php artisan config:clear` untuk mengubah setting.
 */
class SettingsServiceProvider extends ServiceProvider
{
    /** code setting => path config yang di-override. */
    private const MAP = [
        'AI_DEFAULT_PROVIDER' => 'ai.default',
        'GEMINI_API_KEY' => 'ai.providers.gemini.key',
        'GEMINI_MODEL' => 'ai.providers.gemini.models.text.default',
        'OPENAI_API_KEY' => 'ai.providers.openai.key',
        'OPENAI_MODEL' => 'ai.providers.openai.models.text.default',
        'OPENROUTER_API_KEY' => 'ai.providers.openrouter.key',
        'OPENROUTER_MODEL' => 'ai.providers.openrouter.models.text.default',

        'WHATSAPP_DRIVER' => 'services.whatsapp.driver',
        'FONNTE_TOKEN' => 'services.fonnte.token',
        'FONNTE_WEBHOOK_SECRET' => 'services.fonnte.webhook_secret',
        'WABLAS_BASE_URL' => 'services.wablas.base_url',
        'WABLAS_TOKEN' => 'services.wablas.token',
        'WABLAS_SECRET_KEY' => 'services.wablas.secret_key',
        'WABLAS_WEBHOOK_SECRET' => 'services.wablas.webhook_secret',

        'META_APP_ID' => 'services.meta.app_id',
        'META_APP_SECRET' => 'services.meta.app_secret',
        'META_VERIFY_TOKEN' => 'services.meta.verify_token',
        'META_PAGE_ACCESS_TOKEN' => 'services.meta.page_access_token',
        'META_IG_ACCESS_TOKEN' => 'services.meta.ig_access_token',
        'META_IG_APP_SECRET' => 'services.meta.ig_app_secret',
        'META_GRAPH_VERSION' => 'services.meta.graph_version',
        'META_MESSENGER_ENABLED' => 'services.meta.messenger_enabled',
        'META_MESSENGER_COMMENTS_ENABLED' => 'services.meta.messenger_comments_enabled',
        'META_INSTAGRAM_ENABLED' => 'services.meta.instagram_enabled',
        'META_INSTAGRAM_COMMENTS_ENABLED' => 'services.meta.instagram_comments_enabled',
    ];

    /** Setting yang harus dicast ke boolean. */
    private const BOOLS = [
        'META_MESSENGER_ENABLED', 'META_MESSENGER_COMMENTS_ENABLED',
        'META_INSTAGRAM_ENABLED', 'META_INSTAGRAM_COMMENTS_ENABLED',
    ];

    public function boot(): void
    {
        try {
            $values = Setting::map();
        } catch (\Throwable) {
            return; // DB/tabel belum siap
        }

        foreach (self::MAP as $code => $path) {
            $value = $values[$code] ?? null;

            // Nilai kosong dilewati -> biarkan default dari config/.env.
            if ($value === null || $value === '') {
                continue;
            }

            config([$path => in_array($code, self::BOOLS, true)
                ? filter_var($value, FILTER_VALIDATE_BOOL)
                : $value]);
        }
    }
}
