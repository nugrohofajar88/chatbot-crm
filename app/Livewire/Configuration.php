<?php

namespace App\Livewire;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Konfigurasi teknis (level admin/developer): kunci AI, gateway WhatsApp, & Meta.
 * Terpisah dari /settings (level user: persona & auto-scoring).
 *
 * Secret tidak di-prefill ke HTML — hanya petunjuk (4 char terakhir); diperbarui
 * hanya bila operator mengetik nilai baru. Perubahan langsung berlaku (cache
 * settings otomatis dibuang oleh model Setting).
 */
#[Layout('components.layouts.app')]
class Configuration extends Component
{
    /** Nilai non-secret (text/select/bool), di-prefill & editable. */
    public array $values = [];

    /** Input secret — kosong artinya "jangan ubah". */
    public array $secrets = [];

    public string $toast = '';

    /** type: text | select | bool | secret */
    public const FIELDS = [
        // ===== AI =====
        ['code' => 'AI_DEFAULT_PROVIDER', 'label' => 'Provider AI default', 'group' => 'ai', 'type' => 'select', 'options' => ['gemini' => 'Gemini', 'openai' => 'OpenAI', 'openrouter' => 'OpenRouter']],
        ['code' => 'GEMINI_API_KEY', 'label' => 'Gemini API Key', 'group' => 'ai', 'type' => 'secret'],
        ['code' => 'GEMINI_MODEL', 'label' => 'Gemini Model', 'group' => 'ai', 'type' => 'text'],
        ['code' => 'OPENAI_API_KEY', 'label' => 'OpenAI API Key', 'group' => 'ai', 'type' => 'secret'],
        ['code' => 'OPENAI_MODEL', 'label' => 'OpenAI Model', 'group' => 'ai', 'type' => 'text'],
        ['code' => 'OPENROUTER_API_KEY', 'label' => 'OpenRouter API Key', 'group' => 'ai', 'type' => 'secret'],
        ['code' => 'OPENROUTER_MODEL', 'label' => 'OpenRouter Model (vendor/model)', 'group' => 'ai', 'type' => 'text'],
        ['code' => 'IMAGE_PROVIDER', 'label' => 'Provider Gambar AI', 'group' => 'ai', 'type' => 'select', 'options' => ['gemini' => 'Gemini', 'openrouter' => 'OpenRouter', 'openai' => 'OpenAI']],
        ['code' => 'IMAGE_MODEL', 'label' => 'Model Gambar (kosong = default provider)', 'group' => 'ai', 'type' => 'text'],
        ['code' => 'IMAGE_ASPECT', 'label' => 'Rasio Gambar', 'group' => 'ai', 'type' => 'select', 'options' => ['square' => 'Square (1:1)', 'portrait' => 'Portrait', 'landscape' => 'Landscape']],

        // ===== WhatsApp =====
        ['code' => 'WHATSAPP_DRIVER', 'label' => 'Driver WhatsApp', 'group' => 'whatsapp', 'type' => 'select', 'options' => ['fonnte' => 'Fonnte', 'wablas' => 'Wablas']],
        ['code' => 'FONNTE_TOKEN', 'label' => 'Fonnte Token', 'group' => 'whatsapp', 'type' => 'secret'],
        ['code' => 'FONNTE_WEBHOOK_SECRET', 'label' => 'Fonnte Webhook Secret', 'group' => 'whatsapp', 'type' => 'secret'],
        ['code' => 'WABLAS_BASE_URL', 'label' => 'Wablas Base URL', 'group' => 'whatsapp', 'type' => 'text'],
        ['code' => 'WABLAS_TOKEN', 'label' => 'Wablas Token', 'group' => 'whatsapp', 'type' => 'secret'],
        ['code' => 'WABLAS_SECRET_KEY', 'label' => 'Wablas Secret Key', 'group' => 'whatsapp', 'type' => 'secret'],
        ['code' => 'WABLAS_WEBHOOK_SECRET', 'label' => 'Wablas Webhook Secret', 'group' => 'whatsapp', 'type' => 'secret'],

        // ===== Meta =====
        ['code' => 'META_APP_ID', 'label' => 'Meta App ID', 'group' => 'meta', 'type' => 'text'],
        ['code' => 'META_APP_SECRET', 'label' => 'Facebook App Secret', 'group' => 'meta', 'type' => 'secret'],
        ['code' => 'META_VERIFY_TOKEN', 'label' => 'Verify Token', 'group' => 'meta', 'type' => 'secret'],
        ['code' => 'META_PAGE_ACCESS_TOKEN', 'label' => 'Page Access Token (Messenger)', 'group' => 'meta', 'type' => 'secret'],
        ['code' => 'META_IG_ACCESS_TOKEN', 'label' => 'Instagram Access Token', 'group' => 'meta', 'type' => 'secret'],
        ['code' => 'META_IG_APP_SECRET', 'label' => 'Instagram App Secret', 'group' => 'meta', 'type' => 'secret'],
        ['code' => 'META_GRAPH_VERSION', 'label' => 'Graph API Version', 'group' => 'meta', 'type' => 'text'],
        ['code' => 'META_MESSENGER_ENABLED', 'label' => 'Messenger DM aktif', 'group' => 'meta', 'type' => 'bool'],
        ['code' => 'META_MESSENGER_COMMENTS_ENABLED', 'label' => 'Komentar FB/iklan → DM (lead)', 'group' => 'meta', 'type' => 'bool'],
        ['code' => 'META_MESSENGER_COMMENT_PUBLIC_REPLY', 'label' => 'Balas komentar FB publik (butuh pages_manage_engagement)', 'group' => 'meta', 'type' => 'bool'],
        ['code' => 'META_INSTAGRAM_ENABLED', 'label' => 'Instagram DM aktif', 'group' => 'meta', 'type' => 'bool'],
        ['code' => 'META_INSTAGRAM_COMMENTS_ENABLED', 'label' => 'Auto-reply komentar IG (publik)', 'group' => 'meta', 'type' => 'bool'],
    ];

    public function mount(): void
    {
        foreach (self::FIELDS as $f) {
            $code = $f['code'];

            match ($f['type']) {
                'secret' => $this->secrets[$code] = '',
                'bool' => $this->values[$code] = filter_var(Setting::get($code, 'true'), FILTER_VALIDATE_BOOL),
                default => $this->values[$code] = (string) Setting::get($code, ''),
            };
        }
    }

    public function save(): void
    {
        foreach (self::FIELDS as $f) {
            $code = $f['code'];

            if ($f['type'] === 'secret') {
                $new = trim((string) ($this->secrets[$code] ?? ''));
                if ($new !== '') {
                    Setting::put($code, $new);
                    $this->secrets[$code] = '';
                }
            } elseif ($f['type'] === 'bool') {
                Setting::put($code, ! empty($this->values[$code]) ? 'true' : 'false');
            } else {
                Setting::put($code, (string) ($this->values[$code] ?? ''));
            }
        }

        $this->toast = 'Konfigurasi disimpan & langsung berlaku';
    }

    /** Petunjuk secret tersimpan (4 char terakhir) tanpa membocorkan nilai penuh. */
    public function secretHint(string $code): ?string
    {
        $value = (string) Setting::get($code, '');

        if ($value === '') {
            return null;
        }

        return strlen($value) > 4 ? '••••'.substr($value, -4) : '••••';
    }

    public function render()
    {
        return view('livewire.configuration', [
            'grouped' => collect(self::FIELDS)->groupBy('group'),
        ]);
    }
}
