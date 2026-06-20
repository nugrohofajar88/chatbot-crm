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
        Log::info('meta.webhook.received', ['payload' => $payload]);

        $channel = match ((string) ($payload['object'] ?? '')) {
            'instagram' => 'instagram',
            'page' => 'messenger',
            default => null,
        };

        if ($channel === null) {
            return response()->json(['status' => 'ignored']);
        }

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['messaging'] ?? []) as $event) {
                $this->handleEvent($inbound, $channel, $event);
            }
        }

        // Selalu balas 200 cepat supaya Meta tidak menonaktifkan webhook.
        return response()->json(['status' => 'ok']);
    }

    /** Proses satu event messaging: hanya pesan teks dari pengguna. */
    private function handleEvent(MetaInboundService $inbound, string $channel, array $event): void
    {
        $message = $event['message'] ?? null;

        // Abaikan echo (balasan kita sendiri), delivery, read, postback, dll.
        if (! is_array($message) || ($message['is_echo'] ?? false)) {
            return;
        }

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
