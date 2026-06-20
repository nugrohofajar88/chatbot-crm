<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seed tabel `settings` dari nilai .env (TANPA secret hardcoded di file ini).
 * - Setting baru: value diambil dari env(code) (atau default bila ada).
 * - Setting yang sudah ada: value DIPERTAHANKAN (DB jadi sumber kebenaran);
 *   hanya group & description yang disegarkan.
 * Jalankan: php artisan db:seed --class=Database\\Seeders\\SettingsSeeder
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // [code, group, description, default?]
        $defs = [
            // ===== AI =====
            ['AI_DEFAULT_PROVIDER', 'ai', 'Provider AI aktif: gemini | openai | openrouter', 'gemini'],
            ['GEMINI_API_KEY', 'ai', 'API key Google Gemini'],
            ['GEMINI_MODEL', 'ai', 'Model teks Gemini', 'gemini-3.1-flash-lite'],
            ['OPENAI_API_KEY', 'ai', 'API key OpenAI'],
            ['OPENAI_MODEL', 'ai', 'Model teks OpenAI', 'gpt-4o-mini'],
            ['OPENROUTER_API_KEY', 'ai', 'API key OpenRouter'],
            ['OPENROUTER_MODEL', 'ai', 'Model OpenRouter (format vendor/model)', 'openai/gpt-4o-mini'],
            ['ai_persona', 'ai', 'Instruksi/persona sistem AI (kosong = pakai default bawaan)'],
            ['scoring_interval', 'ai', 'Auto-scoring tiap N pesan lead (0 = manual)', '3'],

            // ===== WhatsApp =====
            ['WHATSAPP_DRIVER', 'whatsapp', 'Driver WhatsApp aktif: fonnte | wablas', 'fonnte'],
            ['FONNTE_TOKEN', 'whatsapp', 'Token device Fonnte'],
            ['FONNTE_WEBHOOK_SECRET', 'whatsapp', 'Secret webhook Fonnte'],
            ['WABLAS_BASE_URL', 'whatsapp', 'Base URL Wablas', 'https://wablas.com'],
            ['WABLAS_TOKEN', 'whatsapp', 'Token Wablas'],
            ['WABLAS_SECRET_KEY', 'whatsapp', 'Secret key Wablas (v2)'],
            ['WABLAS_WEBHOOK_SECRET', 'whatsapp', 'Secret webhook Wablas'],

            // ===== Meta (Messenger & Instagram) =====
            ['META_APP_ID', 'meta', 'App ID Meta (opsional)'],
            ['META_APP_SECRET', 'meta', 'App Secret Facebook (verifikasi signature Messenger)'],
            ['META_VERIFY_TOKEN', 'meta', 'Verify token webhook (samakan dgn dashboard Meta)'],
            ['META_PAGE_ACCESS_TOKEN', 'meta', 'Page Access Token (Messenger)'],
            ['META_IG_ACCESS_TOKEN', 'meta', 'Instagram access token (graph.instagram.com, ~60 hari)'],
            ['META_IG_APP_SECRET', 'meta', 'Instagram App Secret (verifikasi signature IG)'],
            ['META_GRAPH_VERSION', 'meta', 'Versi Graph API', 'v21.0'],
            ['META_MESSENGER_ENABLED', 'meta', 'Aktifkan Messenger DM (true/false)', 'true'],
            ['META_MESSENGER_COMMENTS_ENABLED', 'meta', 'Tangani komentar FB/iklan → private DM ke pengomentar (true/false)', 'true'],
            ['META_MESSENGER_COMMENT_PUBLIC_REPLY', 'meta', 'Balas komentar FB secara PUBLIK juga (butuh izin pages_manage_engagement)', 'false'],
            ['META_INSTAGRAM_ENABLED', 'meta', 'Aktifkan Instagram DM (true/false)', 'true'],
            ['META_INSTAGRAM_COMMENTS_ENABLED', 'meta', 'Auto-reply AI ke komentar IG — PUBLIK (true/false)', 'true'],
        ];

        foreach ($defs as $d) {
            [$code, $group, $description] = $d;
            $default = $d[3] ?? null;

            $setting = Setting::firstOrNew(['code' => $code]);

            // Hanya isi value dari env saat setting BARU; yang sudah ada dipertahankan.
            if (! $setting->exists) {
                $setting->value = env($code, $default);
            }

            $setting->group = $group;
            $setting->description = $description;
            $setting->save();
        }
    }
}
