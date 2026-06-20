<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Memproses pesan masuk dari Meta (Messenger / Instagram) untuk CRM.
 * Identitas pengguna = PSID/IGSID (bukan nomor HP). Alurnya sama seperti
 * WaInboundService, tapi balasan dikirim via MetaService.
 */
class MetaInboundService
{
    public function __construct(
        private readonly MetaService $meta,
        private readonly LeadScoringService $scoring,
    ) {
    }

    /** @param  string  $channel  'messenger' | 'instagram' */
    public function handleIncoming(string $channel, string $psid, string $text, ?string $name = null): void
    {
        $placeholder = ucfirst($channel).' User';

        $contact = Contact::firstOrCreate(
            ['channel' => $channel, 'psid' => $psid],
            ['name' => $name ?: $placeholder, 'lead_since' => now()],
        );

        // Lengkapi nama dari profil Meta bila masih placeholder (sekali per kontak baru).
        if (in_array($contact->name, ['', $placeholder], true)) {
            $resolved = $name ?: $this->meta->fetchProfileName($psid, $channel);
            if ($resolved) {
                $contact->update(['name' => $resolved]);
            }
        }

        $conv = $contact->conversations()->firstOrCreate(
            ['channel' => $channel],
            ['stage' => 'baru', 'temperature' => 'cold', 'score' => 0, 'ai_enabled' => true],
        );

        $conv->messages()->create([
            'direction' => 'in',
            'sender' => 'lead',
            'body' => $text,
            'type' => 'text',
        ]);
        $conv->increment('unread');
        $conv->update(['last_message_at' => now()]);

        if ($conv->ai_enabled && ! AiReply::paused()) {
            $this->autoReply($conv, $psid);
        }

        // Auto-scoring di-throttle, sama seperti WhatsApp.
        $interval = (int) Setting::get('scoring_interval', (string) config('aterra.scoring_interval', 3));
        $leadCount = $conv->messages()->where('sender', 'lead')->count();
        if ($interval >= 1 && ($leadCount === 1 || $leadCount % $interval === 0)) {
            try {
                $this->scoring->score($conv);
            } catch (\Throwable $e) {
                Log::warning('meta.autoscore.failed', ['conversation' => $conv->id, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function autoReply(Conversation $conv, string $psid): void
    {
        $reply = AiReply::generate($conv);

        if ($reply === '') {
            return;
        }

        $mid = $this->meta->sendMessage($psid, $reply, $conv->channel);

        if ($mid !== null) {
            $conv->messages()->create([
                'direction' => 'out',
                'sender' => 'ai',
                'body' => $reply,
                'type' => 'text',
                'wa_message_id' => $mid,   // untuk dedup gema (echo) balasan ini
            ]);
            $conv->update(['last_message_at' => now()]);
        }
    }

    /**
     * Gema (echo) pesan keluar atas nama Page — termasuk balasan yang dikirim
     * manual dari Facebook Page Inbox / Business Suite. Disimpan ke thread agar
     * CRM tetap sinkron. Echo dari balasan kita sendiri di-skip lewat dedup mid.
     */
    public function handleEcho(string $channel, string $leadPsid, string $text, string $mid): void
    {
        if ($leadPsid === '' || $text === '') {
            return;
        }

        // Balasan kita sendiri sudah tersimpan (wa_message_id = mid) -> lewati.
        if ($mid !== '' && Message::where('wa_message_id', $mid)->exists()) {
            return;
        }

        $conv = $this->findConversation($channel, $leadPsid);
        if (! $conv) {
            return;
        }

        $conv->messages()->create([
            'direction' => 'out',
            'sender' => 'operator',   // dikirim manusia dari tool Meta
            'body' => $text,
            'type' => 'text',
            'wa_message_id' => $mid ?: null,
        ]);
        $conv->update(['last_message_at' => now()]);
    }

    /** Tandai pesan keluar s.d. watermark sebagai sudah dibaca lead. */
    public function markRead(string $channel, string $leadPsid, ?int $watermarkMs): void
    {
        if (! $watermarkMs || ! $conv = $this->findConversation($channel, $leadPsid)) {
            return;
        }

        $conv->update(['last_read_at' => Carbon::createFromTimestampMs($watermarkMs)]);
    }

    /** Tandai pesan keluar s.d. watermark sebagai sudah sampai (delivered). */
    public function markDelivered(string $channel, string $leadPsid, ?int $watermarkMs): void
    {
        if (! $watermarkMs || ! $conv = $this->findConversation($channel, $leadPsid)) {
            return;
        }

        $conv->update(['last_delivered_at' => Carbon::createFromTimestampMs($watermarkMs)]);
    }

    /** Komentar masuk (Instagram) -> balasan AI publik singkat via Graph. */
    public function handleComment(string $channel, string $commentId, string $text, ?string $username = null): void
    {
        $reply = AiReply::comment($text, $username);

        if ($reply !== '') {
            $this->meta->replyToComment($commentId, $reply, $channel);
        }
    }

    /**
     * Komentar Facebook (postingan/iklan) -> balas publik + private reply (DM) +
     * rekam lead ke inbox CRM agar bisa di-follow-up seperti percakapan Messenger biasa.
     */
    public function handleFacebookComment(string $commentId, string $text, string $psid, ?string $name = null): void
    {
        $channel = 'messenger';

        // 1. Rekam lead + komentar masuk (selalu) — buat thread dulu agar
        //    balasan publik & DM bisa ikut dicatat ke percakapan yang sama.
        $placeholder = ucfirst($channel).' User';
        $contact = Contact::firstOrCreate(
            ['channel' => $channel, 'psid' => $psid],
            ['name' => $name ?: $placeholder, 'lead_since' => now()],
        );
        if ($name && in_array($contact->name, ['', $placeholder], true)) {
            $contact->update(['name' => $name]);
        }

        $conv = $contact->conversations()->firstOrCreate(
            ['channel' => $channel],
            ['stage' => 'baru', 'temperature' => 'cold', 'score' => 0, 'ai_enabled' => true],
        );
        $conv->messages()->create(['direction' => 'in', 'sender' => 'lead', 'body' => '💬 [Komentar] '.$text, 'type' => 'text']);
        $conv->increment('unread');

        // Jeda global AI: lead tetap tercatat, tapi tidak ada balasan (hemat token).
        if (AiReply::paused()) {
            $conv->update(['last_message_at' => now()]);

            return;
        }

        // 2. Balas komentar PUBLIK — opsional (butuh pages_manage_engagement).
        //    Bila terkirim, catat juga ke thread.
        if (config('services.meta.messenger_comment_public_reply', false)) {
            $public = AiReply::comment($text, $name);
            if ($public !== '' && $this->meta->replyToComment($commentId, $public, $channel) !== null) {
                $conv->messages()->create(['direction' => 'out', 'sender' => 'ai', 'body' => '↩️ [Balas komentar] '.$public, 'type' => 'text']);
            }
        }

        // 3. Private reply -> DM ke pengomentar. Bila sukses, catat DM-nya.
        //    (Gagal wajar untuk komentar pemilik Page sendiri / user non-tester di dev.)
        $dm = AiReply::commentToDm($text, $name);
        if ($dm !== '') {
            $mid = $this->meta->privateReply($commentId, $dm, $channel);
            if ($mid !== null) {
                $conv->messages()->create(['direction' => 'out', 'sender' => 'ai', 'body' => $dm, 'type' => 'text', 'wa_message_id' => $mid]);
            }
        }

        $conv->update(['last_message_at' => now()]);
    }

    private function findConversation(string $channel, string $leadPsid): ?Conversation
    {
        if ($leadPsid === '') {
            return null;
        }

        $contact = Contact::where('channel', $channel)->where('psid', $leadPsid)->first();

        return $contact?->conversations()->where('channel', $channel)->first();
    }
}
