<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Support\AiPersona;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Settings extends Component
{
    public string $persona = '';

    public int $scoringInterval = 3;

    public string $toast = '';

    public function mount(): void
    {
        $this->persona = AiPersona::instructions();
        $this->scoringInterval = (int) Setting::get('scoring_interval', (string) config('aterra.scoring_interval', 3));
    }

    public function save(): void
    {
        Setting::put(AiPersona::KEY, trim($this->persona));
        $this->toast = 'Persona AI disimpan';
    }

    public function resetDefault(): void
    {
        $this->persona = AiPersona::DEFAULT;
        $this->toast = 'Dikembalikan ke default (belum disimpan)';
    }

    public function saveScoring(): void
    {
        $this->scoringInterval = max(0, min(50, (int) $this->scoringInterval));
        Setting::put('scoring_interval', (string) $this->scoringInterval);

        $this->toast = $this->scoringInterval === 0
            ? 'Auto-scoring dimatikan (skor hanya manual)'
            : "Auto-scoring tiap {$this->scoringInterval} pesan disimpan";
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
