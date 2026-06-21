<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Tukar User token (short-lived dari Graph API Explorer) menjadi Page token
 * PERMANEN, lalu simpan ke settings (META_PAGE_ACCESS_TOKEN).
 *
 * Alur resmi Meta: short user token -> long-lived user token (pakai App ID +
 * App Secret) -> /me/accounts -> Page token yang tidak kedaluwarsa.
 *
 * Pakai: php artisan meta:setup-page-token "USER_TOKEN" --page=1066085583255460
 */
class SetupPageToken extends Command
{
    protected $signature = 'meta:setup-page-token {user_token : User access token dari Graph API Explorer} {--page= : Page id (opsional; default Page pertama)}';

    protected $description = 'Buat Page token PERMANEN dari user token & simpan ke settings.';

    public function handle(): int
    {
        $userToken = (string) $this->argument('user_token');
        $appId = (string) config('services.meta.app_id');
        $appSecret = (string) config('services.meta.app_secret');
        $version = (string) config('services.meta.graph_version', 'v21.0');

        if ($appId === '' || $appSecret === '') {
            $this->error('META_APP_ID / META_APP_SECRET kosong — isi dulu di /configuration.');

            return self::FAILURE;
        }

        // 1. short-lived user token -> long-lived user token (60 hari)
        $ex = Http::timeout(20)->get("https://graph.facebook.com/{$version}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $userToken,
        ]);
        $longUser = (string) $ex->json('access_token', '');
        if (! $ex->successful() || $longUser === '') {
            $this->error('Tukar long-lived gagal: '.json_encode($ex->json('error') ?? $ex->body()));

            return self::FAILURE;
        }
        $this->info('Long-lived user token OK.');

        // 2. long-lived user token -> Page token (permanen, tidak kedaluwarsa)
        $acc = Http::withToken($longUser)->timeout(20)
            ->get("https://graph.facebook.com/{$version}/me/accounts", ['fields' => 'name,access_token']);
        $pages = $acc->json('data');
        if (! is_array($pages) || $pages === []) {
            $this->error('Tidak ada Page yang bisa diakses token ini: '.json_encode($acc->json('error') ?? $acc->body()));

            return self::FAILURE;
        }

        $wantId = (string) $this->option('page');
        $page = $wantId !== '' ? collect($pages)->firstWhere('id', $wantId) : $pages[0];
        if (! $page) {
            $this->error('Page id '.$wantId.' tidak ada. Tersedia: '.implode(', ', array_column($pages, 'id')));

            return self::FAILURE;
        }

        $pageToken = (string) ($page['access_token'] ?? '');
        Setting::put('META_PAGE_ACCESS_TOKEN', $pageToken);
        $this->info('Page token tersimpan untuk: '.($page['name'] ?? '?').' (id '.($page['id'] ?? '?').')');

        // 3. verifikasi permanen
        $dbg = Http::timeout(20)->get("https://graph.facebook.com/{$version}/debug_token", [
            'input_token' => $pageToken,
            'access_token' => $pageToken,
        ]);
        $exp = $dbg->json('data.expires_at');
        $this->line('expires_at: '.var_export($exp, true).($exp === 0 ? '  → PERMANEN ✅' : '  (bukan 0 = masih ada masa berlaku, cek lagi)'));

        return self::SUCCESS;
    }
}
