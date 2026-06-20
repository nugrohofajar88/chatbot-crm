<?php

namespace App\Support;

use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Setting;
use App\Support\Contracts\WhatsappGateway;
use Illuminate\Support\Facades\Log;

/**
 * Memproses pesan WhatsApp masuk untuk CRM:
 *   1. Cari/buat Contact + Conversation.
 *   2. Simpan pesan (sender: lead).
 *   3. Jika ai_enabled: AI membalas otomatis lalu kirim via gateway aktif.
 */
class WaInboundService
{
    public function __construct(
        private readonly WhatsappGateway $wa,
        private readonly LeadScoringService $scoring,
    ) {
    }

    public function handleIncoming(string $phone, string $text, ?string $name = null): void
    {
        $phone = $this->wa->normalize($phone);

        $contact = Contact::firstOrCreate(
            ['phone' => $phone],
            ['name' => $name ?: $phone, 'channel' => 'whatsapp', 'lead_since' => now()],
        );

        // Lengkapi nama bila sebelumnya hanya nomor.
        if ($name && $contact->name === $phone) {
            $contact->update(['name' => $name]);
        }

        $conv = $contact->conversations()->firstOrCreate(
            ['channel' => 'whatsapp'],
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
            $this->autoReply($conv);
        }

        // Auto-scoring di-throttle: saat lead pertama, lalu tiap kelipatan
        // 'scoring_interval' (diatur operator). Nilai 0 = nonaktif (manual saja).
        $interval = (int) Setting::get('scoring_interval', (string) config('aterra.scoring_interval', 3));
        $leadCount = $conv->messages()->where('sender', 'lead')->count();
        if ($interval >= 1 && ($leadCount === 1 || $leadCount % $interval === 0)) {
            try {
                $this->scoring->score($conv);
            } catch (\Throwable $e) {
                Log::warning('wa.autoscore.failed', ['conversation' => $conv->id, 'error' => $e->getMessage()]);
            }
        }
    }

    protected function autoReply(Conversation $conv): void
    {
        $conv->loadMissing('contact');

        $reply = AiReply::generate($conv);

        if ($reply === '') {
            return;
        }

        $mid = $this->wa->sendMessage($conv->contact->phone, $reply);

        if ($mid !== null) {
            $conv->messages()->create([
                'direction' => 'out',
                'sender' => 'ai',
                'body' => $reply,
                'type' => 'text',
                'wa_message_id' => $mid,
            ]);
            $conv->update(['last_message_at' => now()]);
        }
    }
}
