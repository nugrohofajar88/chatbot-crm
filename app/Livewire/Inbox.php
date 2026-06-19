<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Support\AiPersona;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

use function Laravel\Ai\agent;

#[Layout('components.layouts.app')]
class Inbox extends Component
{
    public ?int $selectedId = null;

    public string $draft = '';

    public string $busy = '';   // '', 'draft'

    public string $toast = '';

    public function mount(): void
    {
        $this->selectedId = Conversation::query()
            ->orderByDesc('last_message_at')
            ->value('id');
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
        Conversation::whereKey($id)->update(['unread' => 0]);
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

    public function sendReply(): void
    {
        $conv = $this->selected();
        $body = trim($this->draft);
        if (! $conv || $body === '') {
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
