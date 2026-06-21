<div x-data="{ confirmPublish: false }" class="flex h-full flex-col">
    <x-page-header title="Buat Postingan" subtitle="AI bantu tulis caption → publish ke Facebook & Instagram" />

    <div class="flex-1 overflow-y-auto p-[26px]">
        <div class="mx-auto max-w-[760px] space-y-4">

            {{-- ===== Komposer ===== --}}
            <div class="rounded-[16px] border border-line bg-panel p-6">
                {{-- Prompt --}}
                <label class="mb-1.5 block text-[13px] font-medium text-ink">Prompt untuk AI</label>
                <div class="flex gap-2">
                    <textarea wire:model="prompt" rows="2" placeholder="mis. Buat promo villa tepi sawah di Canggu, gaya santai & ramah"
                        class="flex-1 resize-y rounded-[12px] border border-line-2 bg-white p-3.5 text-[13.5px] leading-relaxed text-ink outline-none focus:border-accent"></textarea>
                    <button wire:click="generate" wire:loading.attr="disabled" wire:target="generate"
                        class="flex-none self-stretch rounded-[12px] bg-accent px-4 text-[13px] font-semibold text-white hover:brightness-110 disabled:opacity-50">
                        <span wire:loading.remove wire:target="generate">Buat Caption</span>
                        <span wire:loading wire:target="generate">Membuat…</span>
                    </button>
                </div>

                {{-- Caption --}}
                <label class="mb-1.5 mt-4 block text-[13px] font-medium text-ink">Caption</label>
                <textarea wire:model="caption" rows="7" placeholder="Caption akan muncul di sini, atau tulis manual…"
                    class="w-full resize-y rounded-[12px] border border-line-2 bg-white p-4 text-[13.5px] leading-relaxed text-ink outline-none focus:border-accent">{{ $caption }}</textarea>

                {{-- Gambar --}}
                <label class="mb-1.5 mt-4 block text-[13px] font-medium text-ink">Gambar
                    <span class="text-[11px] text-ink-muted">(opsional untuk Facebook, wajib untuk Instagram)</span></label>
                <div class="flex items-center gap-3">
                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}" class="h-16 w-16 flex-none rounded-[10px] object-cover" alt="preview">
                    @endif
                    <label class="cursor-pointer rounded-[10px] border border-line-2 bg-white px-3.5 py-2 text-[12.5px] font-medium text-ink-muted hover:border-accent/40"
                           wire:loading.class="opacity-50" wire:target="image">
                        {{ $image ? 'Ganti gambar' : 'Pilih gambar' }}
                        <input type="file" wire:model="image" class="hidden" accept=".jpg,.jpeg,.png,.webp">
                    </label>
                    @if ($image)
                        <button wire:click="$set('image', null)" class="text-[12px] text-ink-muted hover:text-accent">Hapus</button>
                    @endif
                </div>

                {{-- Platform --}}
                <label class="mb-2 mt-4 block text-[13px] font-medium text-ink">Posting ke</label>
                <div class="flex flex-wrap gap-2.5">
                    <label class="flex cursor-pointer items-center gap-2 rounded-[10px] border border-line-2 bg-white px-3.5 py-2 text-[13px]">
                        <input type="checkbox" wire:model.live="platforms" value="facebook" class="h-[16px] w-[16px] rounded text-accent focus:ring-accent">
                        Facebook Page
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-[10px] border border-line-2 bg-white px-3.5 py-2 text-[13px]">
                        <input type="checkbox" wire:model.live="platforms" value="instagram" class="h-[16px] w-[16px] rounded text-accent focus:ring-accent">
                        Instagram
                    </label>
                </div>

                {{-- Publish --}}
                <div class="mt-5 flex items-center gap-3">
                    <button type="button" x-on:click="confirmPublish = true" wire:loading.attr="disabled" wire:target="publish"
                        class="rounded-[11px] bg-accent px-5 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110 disabled:opacity-50">
                        <span wire:loading.remove wire:target="publish">Publish Sekarang</span>
                        <span wire:loading wire:target="publish">Mempublikasikan…</span>
                    </button>
                    <span class="text-[12px] text-ink-muted">Postingan langsung tampil publik di akun terpilih.</span>
                </div>
            </div>

            {{-- ===== Riwayat ===== --}}
            <div class="rounded-[16px] border border-line bg-panel p-6">
                <div class="mb-3 font-serif text-[18px] font-semibold text-ink-strong">Riwayat Postingan</div>
                @forelse ($this->recent as $post)
                    @php
                        $st = ['published' => ['Terbit', '#3DA35D'], 'partial' => ['Sebagian', '#C79A3A'], 'failed' => ['Gagal', '#C0562C'], 'draft' => ['Draft', '#9A958A']][$post->status] ?? ['—', '#9A958A'];
                    @endphp
                    <div class="flex items-start gap-3 border-t border-line py-3 first:border-t-0">
                        @if ($post->imageUrl())
                            <img src="{{ $post->imageUrl() }}" class="h-11 w-11 flex-none rounded-[8px] object-cover" alt="">
                        @else
                            <div class="flex h-11 w-11 flex-none items-center justify-center rounded-[8px] bg-panel-2 text-ink-muted">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-[12.5px] leading-snug text-ink">{{ $post->caption }}</p>
                            <div class="mt-1 flex items-center gap-2 text-[11px] text-ink-muted">
                                <span class="rounded-full px-2 py-0.5 font-semibold" style="background: {{ $st[1] }}1a; color: {{ $st[1] }}">{{ $st[0] }}</span>
                                <span>{{ implode(' · ', array_map('ucfirst', $post->platforms ?? [])) }}</span>
                                <span>{{ $post->created_at->format('d/m H:i') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-[13px] text-ink-muted">Belum ada postingan.</p>
                @endforelse
            </div>

        </div>
    </div>

    {{-- modal konfirmasi publish --}}
    <div x-cloak x-show="confirmPublish" @keydown.escape.window="confirmPublish = false"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div x-show="confirmPublish" x-transition.opacity @click="confirmPublish = false"
             class="absolute inset-0 bg-[#2A241E]/45 backdrop-blur-[2px]"></div>
        <div x-show="confirmPublish"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-[400px] rounded-[18px] border border-line bg-panel p-6 shadow-[0_20px_50px_rgba(60,40,20,0.28)]">
            <div class="mb-3.5 flex h-11 w-11 items-center justify-center rounded-[12px]" style="background:rgba(176,85,47,0.12);color:#B0552F">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </div>
            <h3 class="font-serif text-[19px] font-semibold text-ink-strong">Publikasikan postingan?</h3>
            <p class="mt-1.5 text-[13px] leading-relaxed text-ink-muted">
                Postingan akan <b class="text-ink">langsung tampil publik</b> di:
                <b class="text-ink">{{ collect($platforms)->map(fn ($p) => ucfirst($p))->implode(', ') ?: '— belum dipilih' }}</b>.
            </p>
            <div class="mt-5 flex justify-end gap-2.5">
                <button type="button" @click="confirmPublish = false"
                    class="rounded-[11px] border border-line-2 bg-white px-4 py-2.5 text-[13px] font-semibold text-ink-muted hover:border-accent/40">
                    Batal
                </button>
                <button type="button" wire:click="publish" @click="confirmPublish = false"
                    class="rounded-[11px] bg-accent px-5 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110">
                    Ya, Publish
                </button>
            </div>
        </div>
    </div>

    @if ($toast)
        <div wire:key="toast-{{ md5($toast) }}" x-data="{s:true}" x-init="setTimeout(() => s = false, 3000)" x-show="s" x-transition
             class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-[12px] bg-sidebar px-5 py-3 text-[13px] font-medium text-[#F4EFE7] shadow-[0_12px_30px_rgba(0,0,0,0.25)]">
            {{ $toast }}
        </div>
    @endif
</div>
