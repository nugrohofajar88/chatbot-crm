<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Support\AiPersona;
use App\Support\Contracts\WhatsappGateway;
use App\Support\LeadScoringService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

use function Laravel\Ai\agent;

#[Layout('components.layouts.app')]
class Inbox extends Component
{
    use WithFileUploads;

    public ?int $selectedId = null;

    public string $draft = '';

    public $attachment = null;

    public string $busy = '';   // '', 'draft'

    public string $toast = '';

    public function mount(): void
    {
        $this->selectedId = Conversation::query()
            ->orderByDesc('last_message_at')
            ->value('id');

        $this->markRead();
    }

    /** Tandai percakapan yang sedang dibuka sebagai sudah dibaca. */
    private function markRead(): void
    {
        if ($this->selectedId) {
            Conversation::whereKey($this->selectedId)->where('unread', '>', 0)->update(['unread' => 0]);
        }
    }

    /** Dipanggil tiap polling: jaga percakapan terbuka tetap 0 unread. */
    public function pollThread(): void
    {
        $this->markRead();
    }

    #[Computed]
    public function conversations()
    {
        return Conversation::query()
            ->with(['contact', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->get();
    }

    #[Computed]
    public function selected(): ?Conversation
    {
        if (! $this->selectedId) {
            return null;
        }

        return Conversation::query()
            ->with(['contact', 'messages' => fn ($q) => $q->orderBy('id'), 'latestScore'])
            ->find($this->selectedId);
    }

    #[Computed]
    public function recommendations()
    {
        return \App\Models\Listing::query()
            ->whereIn('status', ['available', 'hot'])
            ->orderByDesc('price')
            ->limit(2)
            ->get();
    }

    public function selectConversation(int $id): void
    {
        $this->selectedId = $id;
        $this->draft = '';
        $this->markRead();
    }

    public function toggleAi(): void
    {
        $conv = $this->selected();
        if (! $conv) {
            return;
        }
        $conv->update(['ai_enabled' => ! $conv->ai_enabled]);
        $this->toast = $conv->ai_enabled ? 'AI Otomatis diaktifkan' : 'AI dimatikan, Anda mengambil alih';
    }

    /** Hapus percakapan terpilih (pesan & skor lead ikut terhapus via cascade). */
    public function deleteConversation(): void
    {
        $conv = $this->selected();
        if (! $conv) {
            return;
        }

        $name = $conv->contact->name;
        $conv->delete();

        // Pindah ke percakapan terbaru berikutnya (atau kosong bila habis).
        $this->selectedId = Conversation::query()
            ->orderByDesc('last_message_at')
            ->value('id');
        $this->draft = '';
        unset($this->selected, $this->conversations);
        $this->markRead();

        $this->toast = 'Percakapan dengan '.$name.' dihapus';
    }

    public function sendReply(WhatsappGateway $wa): void
    {
        $conv = $this->selected();
        $body = trim($this->draft);
        if (! $conv || $body === '') {
            return;
        }

        $mid = $wa->sendMessage($conv->contact->phone, $body);

        if ($mid === null) {
            $this->toast = 'Gagal mengirim, coba lagi';

            return;
        }

        $conv->messages()->create([
            'direction' => 'out',
            'sender' => 'operator',
            'body' => $body,
            'type' => 'text',
            'wa_message_id' => $mid,
        ]);
        $conv->update(['last_message_at' => now(), 'unread' => 0]);

        $this->draft = '';
        $this->toast = 'Balasan terkirim ke '.$conv->contact->name;
    }

    public function sendAttachment(WhatsappGateway $wa): void
    {
        $conv = $this->selected();
        if (! $conv || ! $this->attachment) {
            return;
        }

        $this->validate([
            'attachment' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $path = $this->attachment->store('wa', 'public_uploads');  // public/uploads/wa/...
        $publicUrl = url('uploads/'.$path);                       // URL absolut publik
        $filename = $this->attachment->getClientOriginalName();
        $caption = trim($this->draft);

        // 1. Coba kirim sebagai MEDIA (berfungsi di Fonnte berbayar).
        $delivery = 'failed';
        $mid = $wa->sendMedia($conv->contact->phone, $publicUrl, $filename, $caption);
        if ($mid !== null) {
            $delivery = 'media';
        } else {
            // 2. Fallback: kirim LINK sebagai teks (andal di plan gratis / semua gateway).
            //    Pola sama seperti larashop-be (link dimasukkan ke pesan teks).
            $linkText = ($caption !== '' ? $caption."\n\n" : '').'📎 Lihat lampiran: '.$publicUrl;
            $mid = $wa->sendMessage($conv->contact->phone, $linkText);
            if ($mid !== null) {
                $delivery = 'link';
            }
        }

        $ext = strtolower((string) $this->attachment->getClientOriginalExtension());
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);

        // Selalu simpan pesan ke thread (tampil di CRM) apa pun hasil pengirimannya.
        $conv->messages()->create([
            'direction' => 'out',
            'sender' => 'operator',
            'body' => $caption,
            'type' => $isImage ? 'image' : 'document',
            'payload' => ['path' => $path, 'name' => $filename, 'delivery' => $delivery],
            'wa_message_id' => $mid,
        ]);
        $conv->update(['last_message_at' => now(), 'unread' => 0]);

        $this->reset('attachment');
        $this->draft = '';
        $this->toast = match ($delivery) {
            'media' => 'Lampiran terkirim ke '.$conv->contact->name,
            'link' => 'Terkirim sebagai link (Fonnte gratis tak dukung media)',
            default => 'Lampiran tersimpan, tapi gagal terkirim ke WA',
        };
    }

    public function recomputeScore(LeadScoringService $scoring): void
    {
        $conv = $this->selected();
        if (! $conv) {
            return;
        }

        $result = $scoring->score($conv);
        unset($this->selected, $this->conversations);
        $this->toast = $result ? 'Skor lead diperbarui' : 'AI sedang sibuk, coba lagi';
    }

    public function generateAiDraft(): void
    {
        $conv = $this->selected();
        if (! $conv) {
            return;
        }

        $this->busy = 'draft';

        $history = $conv->messages
            ->map(fn ($m) => ($m->sender === 'lead' ? 'Pengguna' : 'Asisten').': '.$m->body)
            ->implode("\n");

        $instructions = AiPersona::instructions();

        $prompt = "Riwayat percakapan:\n{$history}\n\n"
            .'Tulis SATU balasan terbaik untuk pesan terakhir pengguna, sesuai peranmu. Hanya teks balasannya, tanpa label.';

        try {
            $res = agent(instructions: $instructions)->prompt($prompt);
            $this->draft = trim(preg_replace('/^["\']|["\']$/', '', $res->text));
            $this->toast = 'Balasan AI dimuat ke kolom';
        } catch (\Throwable $e) {
            Log::warning('inbox.ai_draft_failed', ['error' => $e->getMessage()]);
            $this->toast = 'AI sedang sibuk, coba lagi sebentar';
        } finally {
            $this->busy = '';
        }
    }

    public function render()
    {
        return view('livewire.inbox');
    }
}
