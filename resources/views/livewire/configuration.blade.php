<div class="flex h-full flex-col">
    <x-page-header title="Konfigurasi" subtitle="Pengaturan teknis (admin/developer) — kunci AI, gateway, & Meta" />

    <div class="flex-1 overflow-y-auto p-[26px]">
        <div class="mx-auto max-w-[760px]">

            @php
                $groups = [
                    'ai' => ['Penyedia AI', 'API key & model untuk balasan AI'],
                    'whatsapp' => ['WhatsApp Gateway', 'Driver & kredensial Fonnte / Wablas'],
                    'meta' => ['Meta — Messenger & Instagram', 'Token, secret, & saklar channel'],
                ];
                $inputClass = 'w-full rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent';
            @endphp

            <div class="mb-4 rounded-[14px] border border-accent/20 bg-accent/[0.06] p-4 text-[12.5px] leading-relaxed text-[#6B6253]">
                Nilai disimpan di database &mdash; perubahan <b class="text-ink">langsung berlaku</b> tanpa redeploy/clear cache.
                Kolom rahasia hanya menampilkan 4 karakter terakhir; biarkan kosong jika tak ingin mengubahnya.
            </div>

            @foreach ($groups as $key => $meta)
                <div class="mb-4 rounded-[16px] border border-line bg-panel p-6">
                    <div class="mb-4">
                        <div class="font-serif text-[19px] font-semibold text-ink-strong">{{ $meta[0] }}</div>
                        <div class="text-[12.5px] text-ink-muted">{{ $meta[1] }}</div>
                    </div>

                    <div class="space-y-3.5">
                        @foreach ($grouped[$key] as $f)
                            @php $code = $f['code']; @endphp
                            <div>
                                <label class="mb-1 flex items-baseline gap-2">
                                    <span class="text-[13px] font-medium text-ink">{{ $f['label'] }}</span>
                                    <span class="text-[10.5px] font-mono text-ink-muted">{{ $code }}</span>
                                </label>

                                @switch($f['type'])
                                    @case('select')
                                        <select wire:model="values.{{ $code }}" class="{{ $inputClass }}">
                                            @foreach ($f['options'] as $val => $lbl)
                                                <option value="{{ $val }}">{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                        @break

                                    @case('bool')
                                        <label class="inline-flex cursor-pointer items-center gap-2.5">
                                            <input type="checkbox" wire:model="values.{{ $code }}"
                                                   class="h-[18px] w-[18px] rounded border-line-2 text-accent focus:ring-accent">
                                            <span class="text-[13px] text-ink-muted">Aktifkan</span>
                                        </label>
                                        @break

                                    @case('secret')
                                        @php $hint = $this->secretHint($code); @endphp
                                        <input type="password" wire:model="secrets.{{ $code }}" autocomplete="new-password"
                                               placeholder="{{ $hint ? 'Tersimpan '.$hint.' — isi untuk mengganti' : 'Belum diisi' }}"
                                               class="{{ $inputClass }}">
                                        @break

                                    @default
                                        <input type="text" wire:model="values.{{ $code }}" class="{{ $inputClass }}">
                                @endswitch
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex items-center gap-3 pb-4">
                <button wire:click="save" wire:loading.attr="disabled"
                        class="rounded-[11px] bg-accent px-5 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110 disabled:opacity-50">
                    Simpan Konfigurasi
                </button>
                <span class="text-[12px] text-ink-muted">Berlaku langsung untuk balasan & webhook berikutnya.</span>
            </div>
        </div>
    </div>

    @if ($toast)
        <div wire:key="toast-{{ md5($toast) }}" x-data="{s:true}" x-init="setTimeout(() => s = false, 2600)" x-show="s" x-transition
             class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-[12px] bg-sidebar px-5 py-3 text-[13px] font-medium text-[#F4EFE7] shadow-[0_12px_30px_rgba(0,0,0,0.25)]">
            {{ $toast }}
        </div>
    @endif
</div>
