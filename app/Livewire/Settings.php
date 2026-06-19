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

    public string $toast = '';

    public function mount(): void
    {
        $this->persona = AiPersona::instructions();
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

    public function render()
    {
        return view('livewire.settings');
    }
}
