<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Subscribe Facebook Page ke app untuk field webhook (feed, messages, dst)
 * memakai Page Access Token aktif (dari /configuration). Diperlukan agar
 * event komentar (feed) benar-benar dikirim Meta ke webhook kita.
 */
class SubscribePage extends Command
{
    protected $signature = 'meta:subscribe-page {--fields=feed,messages,messaging_postbacks}';

    protected $description = 'Subscribe FB Page ke app untuk field webhook (pakai Page token aktif).';

    public function handle(): int
    {
        $token = (string) config('services.meta.page_access_token');
        $version = (string) config('services.meta.graph_version', 'v21.0');

        if ($token === '') {
            $this->error('Page Access Token kosong — set dulu di /configuration.');

            return self::FAILURE;
        }

        $me = Http::withToken($token)->timeout(15)->get("https://graph.facebook.com/{$version}/me", ['fields' => 'id,name']);
        if (! $me->successful()) {
            $this->error('Token bukan Page token / invalid: '.json_encode($me->json('error') ?? $me->body()));

            return self::FAILURE;
        }

        $pageId = (string) $me->json('id');
        $this->info('Page: '.($me->json('name') ?? '?')." (id {$pageId})");

        $fields = (string) $this->option('fields');
        $res = Http::withToken($token)->timeout(15)->post("https://graph.facebook.com/{$version}/{$pageId}/subscribed_apps", [
            'subscribed_fields' => $fields,
        ]);

        if (! $res->successful() || ! ($res->json('success') ?? false)) {
            $this->error('Subscribe gagal: '.json_encode($res->json('error') ?? $res->body()));

            return self::FAILURE;
        }

        $this->info("Subscribed fields: {$fields} → OK ✅");

        $check = Http::withToken($token)->timeout(15)->get("https://graph.facebook.com/{$version}/{$pageId}/subscribed_apps");
        $this->line('Langganan sekarang: '.json_encode($check->json('data') ?? $check->json('error') ?? $check->body()));

        return self::SUCCESS;
    }
}
