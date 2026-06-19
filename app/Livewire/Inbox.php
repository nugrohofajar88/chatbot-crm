<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Support\AiPersona;
use App\Support\FonnteService;
use App\Support\LeadScoringService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    public function sendReply(FonnteService $fonnte): void
    {
        $conv = $this->selected();
        $body = trim($this->draft);
        if (! $conv || $body === '') {
            return;
        }

        if (! $fonnte->sendMessage($conv->contact->phone, $body)) {
            $this->toast = 'Gagal mengirim, coba lagi';

            return;
        }

        $conv->messages()->create([
            'direction' => 'out',
            'sender' => 'operator',
            'body' => $body,
            'type' => 'text',
        ]);
        $conv->update(['last_message_at' => now(), 'unread' => 0]);

        $this->draft = '';
        $this->toast = 'Balasan terkirim ke '.$conv->contact->name;
    }

    public function sendAttachment(FonnteService $fonnte): void
    {
        $conv = $this->selected();
        if (! $conv || ! $this->attachment) {
            return;
        }

        $this->validate([
            'attachment' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $path = $this->attachment->store('wa', 'public_uploads');  // public/uploads/wa/...
        $url = url('uploads/'.$path);                             // URL absolut publik untuk Fonnte
        $filename = $this->attachment->getClientOriginalName();
        $caption = trim($this->draft);

        if (! $fonnte->sendMedia($conv->contact->phone, $url, $filename, $caption)) {
            Storage::disk('public_uploads')->delete($path);
            $this->toast = 'Gagal mengirim lampiran (pastikan URL publik & nomor valid)';

            return;
        }

        $ext = strtolower((string) $this->attachment->getClientOriginalExtension());
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);

        $conv->messages()->create([
            'direction' => 'out',
            'sender' => 'operator',
            'body' => $caption,
            'type' => $isImage ? 'image' : 'document',
            'payload' => ['path' => $path, 'name' => $filename],
        ]);
        $conv->update(['last_message_at' => now(), 'unread' => 0]);

        $this->reset('attachment');
        $this->draft = '';
        $this->toast = 'Lampiran terkirim ke '.$conv->contact->name;
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
            ->map(fn ($m) => ($m->sender === 'lead' ? 'Calon pembeli' : 'Agen').': '.$m->body)
            ->implode("\n");

        $instructions = AiPersona::instructions();

        $prompt = "Riwayat percakapan:\n{$history}\n\n"
            .'Tulis SATU balasan terbaik sebagai agen untuk pesan terakhir calon pembeli. Hanya teks balasannya, tanpa label.';

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
