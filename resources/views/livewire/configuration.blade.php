<div x-data="{ tab: 'ai' }" class="flex h-full flex-col">
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

            {{-- tab nav --}}
            <div class="mb-4 flex gap-1 rounded-[13px] border border-line bg-panel-2 p-1">
                @foreach ($groups as $key => $meta)
                    <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'bg-white text-ink-strong shadow-[0_2px_8px_rgba(80,55,30,0.08)]' : 'text-ink-muted hover:text-ink'"
                        class="flex-1 rounded-[10px] px-3 py-2 text-[12.5px] font-semibold transition-colors">
                        {{ $meta[0] }}
                    </button>
                @endforeach
            </div>

            <div class="mb-4 rounded-[14px] border border-accent/20 bg-accent/[0.06] p-4 text-[12.5px] leading-relaxed text-[#6B6253]">
                Nilai disimpan di database &mdash; perubahan <b class="text-ink">langsung berlaku</b> tanpa redeploy/clear cache.
                Kolom rahasia hanya menampilkan 4 karakter terakhir; biarkan kosong jika tak ingin mengubahnya.
            </div>

            @foreach ($groups as $key => $meta)
                <div x-show="tab === '{{ $key }}'" x-cloak class="mb-4 rounded-[16px] border border-line bg-panel p-6">
                    <div class="mb-4 text-[12.5px] text-ink-muted">{{ $meta[1] }}</div>

                    <div class="space-y-3.5">
                        @php $currentSection = null; $sectionIdx = 0; @endphp
                        @foreach ($grouped[$key] as $f)
                            @php $code = $f['code']; @endphp

                            @if (($f['section'] ?? null) && ($f['section'] !== $currentSection))
                                @php $currentSection = $f['section']; $sectionIdx++; @endphp
                                <div class="{{ $sectionIdx > 1 ? 'border-t border-line pt-4' : '' }}">
                                    <div class="text-[12px] font-semibold uppercase tracking-[0.05em] text-accent">{{ $currentSection }}</div>
                                    @if (! empty($f['section_desc']))
                                        <div class="mt-0.5 text-[11.5px] leading-snug text-ink-muted">{{ $f['section_desc'] }}</div>
                                    @endif
                                </div>
                            @endif

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

                                    @case('logo')
                                        @php $curLogo = \App\Models\Setting::get('BRAND_LOGO'); @endphp
                                        <div class="flex items-center gap-4">
                                            <div class="flex h-[52px] w-[52px] flex-none items-center justify-center overflow-hidden rounded-[10px] border border-line-2 bg-white">
                                                @if ($logoUpload)
                                                    <img src="{{ $logoUpload->temporaryUrl() }}" class="h-full w-full object-cover">
                                                @elseif ($curLogo)
                                                    <img src="{{ Storage::disk('public_uploads')->url($curLogo) }}" class="h-full w-full object-cover">
                                                @else
                                                    <span class="font-serif text-[22px] font-bold text-accent">{{ mb_strtoupper(mb_substr($brandShort ?? 'A', 0, 1)) }}</span>
                                                @endif
                                            </div>
                                            <div class="flex-1">
                                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-[10px] border border-dashed border-line-2 bg-white px-4 py-2 text-[12.5px] font-semibold text-ink transition-colors hover:border-accent">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                                                    Pilih logo
                                                    <input type="file" wire:model="logoUpload" accept="image/*" class="hidden">
                                                </label>
                                                <div wire:loading wire:target="logoUpload" class="mt-1 text-[11px] text-ink-muted">Mengunggah…</div>
                                                @error('logoUpload')<span class="text-[12px] text-hot">{{ $message }}</span>@enderror
                                                @if ($curLogo)
                                                    <label class="mt-1.5 flex items-center gap-1.5 text-[11.5px] text-ink-muted"><input type="checkbox" wire:model="removeLogo"> Hapus logo (kembali ke inisial)</label>
                                                @endif
                                                <p class="mt-1 text-[11px] text-ink-muted">PNG/JPG/WebP, maks 2MB. Tampil di sidebar.</p>
                                            </div>
                                        </div>
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
                <span class="text-[12px] text-ink-muted">Menyimpan semua tab sekaligus &middot; langsung berlaku.</span>
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
