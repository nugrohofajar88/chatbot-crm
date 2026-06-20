<?php

namespace App\Http\Controllers;

use App\Support\MetaInboundService;
use App\Support\MetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Meta untuk Facebook Messenger & Instagram.
 *   - GET  : verifikasi langganan — echo `hub.challenge` bila verify_token cocok.
 *   - POST : event pesan masuk (entry[].messaging[]).
 * Satu URL melayani kedua platform; dibedakan lewat field `object`
 *   ('page' = Messenger, 'instagram' = Instagram).
 *
 * Catatan: Meta mengirim query verifikasi sebagai hub.mode / hub.verify_token /
 * hub.challenge; PHP mengubah titik jadi underscore -> hub_mode, dst.
 */
class MetaWebhookController extends Controller
{
    public function handle(
        Request $request,
        MetaService $meta,
        MetaInboundService $inbound,
        ?string $secret = null,
    ): Response|JsonResponse {
        // 1) Handshake verifikasi (GET) dari dashboard Meta.
        if ($request->isMethod('get')) {
            $verifyToken = (string) config('services.meta.verify_token');

            if ($request->query('hub_mode') === 'subscribe'
                && $verifyToken !== ''
                && hash_equals($verifyToken, (string) $request->query('hub_verify_token'))) {
                return response((string) $request->query('hub_challenge'))
                    ->header('Content-Type', 'text/plain');
            }

            return response('Forbidden', 403);
        }

        // 2) Event (POST): verifikasi tanda tangan payload.
        if (! $meta->verifySignature($request->header('X-Hub-Signature-256'), $request->getContent())) {
            Log::warning('meta.webhook.bad_signature', ['ip' => $request->ip()]);

            return response()->json(['status' => 'invalid signature'], 403);
        }

        $payload = $request->all();

        $channel = match ((string) ($payload['object'] ?? '')) {
            'instagram' => 'instagram',
            'page' => 'messenger',
            default => null,
        };

        // Abaikan channel tak dikenal ATAU yang dimatikan lewat config
        // (services.meta.messenger_enabled / instagram_enabled).
        if ($channel === null || ! config("services.meta.{$channel}_enabled", true)) {
            return response()->json(['status' => 'ignored']);
        }

        Log::info('meta.webhook.received', ['payload' => $payload]);

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            $businessId = (string) ($entry['id'] ?? '');

            // Pesan/DM, read, delivery, echo.
            foreach ((array) ($entry['messaging'] ?? []) as $event) {
                $this->handleEvent($inbound, $channel, $event);
            }

            // Komentar (entry[].changes[] field=comments).
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $this->handleChange($inbound, $channel, $businessId, $change);
            }
        }

        // Selalu balas 200 cepat supaya Meta tidak menonaktifkan webhook.
        return response()->json(['status' => 'ok']);
    }

    /**
     * Proses event komentar (entry[].changes[] field=comments) -> auto-reply AI publik.
     * Mengabaikan komentar/balasan dari akun bisnis sendiri (anti-loop).
     */
    private function handleChange(MetaInboundService $inbound, string $channel, string $businessId, array $change): void
    {
        if (($change['field'] ?? '') !== 'comments') {
            return;
        }

        if (! config('services.meta.instagram_comments_enabled', true)) {
            return;
        }

        $value = (array) ($change['value'] ?? []);
        $commentId = trim((string) ($value['id'] ?? ''));
        $text = trim((string) ($value['text'] ?? ''));
        $fromId = trim((string) ($value['from']['id'] ?? ''));
        $username = trim((string) ($value['from']['username'] ?? '')) ?: null;

        // Abaikan: kosong, atau komentar/balasan dari akun bisnis sendiri (cegah loop).
        if ($commentId === '' || $text === '' || ($businessId !== '' && $fromId === $businessId)) {
            return;
        }

        // Dedup (Meta bisa mengirim ulang event komentar yang sama).
        if (! Cache::add('meta:comment:'.$commentId, 1, now()->addMinutes(10))) {
            return;
        }

        $inbound->handleComment($channel, $commentId, $text, $username);
    }

    /** Proses satu event messaging: pesan masuk, gema keluar, read & delivery receipt. */
    private function handleEvent(MetaInboundService $inbound, string $channel, array $event): void
    {
        // Read receipt: lead membaca pesan kita (sampai watermark).
        if (isset($event['read']['watermark'])) {
            $inbound->markRead($channel, (string) ($event['sender']['id'] ?? ''), (int) $event['read']['watermark']);

            return;
        }

        // Delivery receipt: pesan kita sampai ke lead (sampai watermark).
        if (isset($event['delivery']['watermark'])) {
            $inbound->markDelivered($channel, (string) ($event['sender']['id'] ?? ''), (int) $event['delivery']['watermark']);

            return;
        }

        $message = $event['message'] ?? null;
        if (! is_array($message)) {
            return; // postback / event lain yang belum ditangani
        }

        // Gema pesan keluar (termasuk balasan manual dari Page Inbox). recipient = lead.
        if ($message['is_echo'] ?? false) {
            $inbound->handleEcho(
                $channel,
                trim((string) ($event['recipient']['id'] ?? '')),
                trim((string) ($message['text'] ?? '')),
                trim((string) ($message['mid'] ?? '')),
            );

            return;
        }

        // Pesan masuk dari lead.
        $psid = trim((string) ($event['sender']['id'] ?? ''));
        $text = trim((string) ($message['text'] ?? ''));

        // Lampiran/stiker belum didukung (TODO) — lewati bila tanpa teks.
        if ($psid === '' || $text === '') {
            return;
        }

        // Dedup: Meta bisa mengirim ulang event yang sama (mid).
        $mid = trim((string) ($message['mid'] ?? ''));
        $dedupKey = 'meta:msg:'.($mid !== '' ? $mid : md5($channel.'|'.$psid.'|'.$text));

        if (! Cache::add($dedupKey, 1, now()->addMinutes(3))) {
            return;
        }

        $inbound->handleIncoming($channel, $psid, $text);
    }
}
