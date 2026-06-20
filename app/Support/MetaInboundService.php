<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Setting;
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

        if ($conv->ai_enabled) {
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

        if ($this->meta->sendMessage($psid, $reply)) {
            $conv->messages()->create([
                'direction' => 'out',
                'sender' => 'ai',
                'body' => $reply,
                'type' => 'text',
            ]);
            $conv->update(['last_message_at' => now()]);
        }
    }
}
