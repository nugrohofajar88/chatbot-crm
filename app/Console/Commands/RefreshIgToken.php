<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Refresh token Instagram (Instagram Login) yang berumur ~60 hari.
 * Dijalankan harian; hanya benar-benar memanggil Graph saat token sudah
 * mendekati kedaluwarsa (>= AMBANG hari) DAN Instagram sedang aktif.
 *
 * "Umur" token dihitung dari `updated_at` baris setting META_IG_ACCESS_TOKEN
 * (Setting::put memperbarui updated_at, jadi tiap refresh me-reset jam mundur).
 */
class RefreshIgToken extends Command
{
    protected $signature = 'meta:refresh-ig-token {--force : Paksa refresh walau belum mendekati kedaluwarsa}';

    protected $description = 'Refresh token Instagram saat ~10 hari sebelum kedaluwarsa (jika IG aktif).';

    /** Token IG berumur 60 hari; refresh saat sudah >= 50 hari (10 hari sebelum habis). */
    private const REFRESH_AFTER_DAYS = 50;

    public function handle(): int
    {
        if (! filter_var(Setting::get('META_INSTAGRAM_ENABLED', 'true'), FILTER_VALIDATE_BOOL)) {
            $this->info('Instagram nonaktif — refresh dilewati.');

            return self::SUCCESS;
        }

        $setting = Setting::where('code', 'META_IG_ACCESS_TOKEN')->first();
        $token = (string) ($setting->value ?? '');

        if ($token === '') {
            $this->warn('META_IG_ACCESS_TOKEN kosong — tidak ada yang di-refresh.');

            return self::SUCCESS;
        }

        $ageDays = $setting->updated_at ? (int) $setting->updated_at->diffInDays(now()) : 999;

        if (! $this->option('force') && $ageDays < self::REFRESH_AFTER_DAYS) {
            $this->info("Token masih segar ({$ageDays} hari) — belum perlu refresh (ambang ".self::REFRESH_AFTER_DAYS.' hari).');

            return self::SUCCESS;
        }

        try {
            $res = Http::timeout(20)->get('https://graph.instagram.com/refresh_access_token', [
                'grant_type' => 'ig_refresh_token',
                'access_token' => $token,
            ]);
        } catch (\Throwable $e) {
            Log::error('meta.ig.refresh.exception', ['message' => $e->getMessage()]);
            $this->error('Gagal menghubungi Graph: '.$e->getMessage());

            return self::FAILURE;
        }

        $new = (string) $res->json('access_token', '');

        if (! $res->successful() || $new === '') {
            Log::error('meta.ig.refresh.failed', [
                'http' => $res->status(),
                'age_days' => $ageDays,
                'error' => $res->json('error') ?? $res->body(),
            ]);
            $this->error('Refresh gagal (HTTP '.$res->status().'). Token mungkin sudah kedaluwarsa — generate ulang dari dashboard Meta.');

            return self::FAILURE;
        }

        Setting::put('META_IG_ACCESS_TOKEN', $new);   // updated_at ter-reset
        $days = intdiv((int) $res->json('expires_in', 0), 86400);

        Log::info('meta.ig.refresh.done', ['valid_days' => $days, 'prev_age_days' => $ageDays]);
        $this->info("Token IG di-refresh. Berlaku ~{$days} hari ke depan.");

        return self::SUCCESS;
    }
}
