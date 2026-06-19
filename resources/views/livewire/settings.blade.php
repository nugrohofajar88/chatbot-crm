<div class="flex h-full flex-col">
    <x-page-header title="Pengaturan AI" subtitle="Atur persona & perilaku asisten AI" />

    <div class="flex-1 overflow-y-auto p-[26px]">
        <div class="mx-auto max-w-[760px]">

            <div class="rounded-[16px] border border-line bg-panel p-6">
                <div class="mb-4 flex items-center gap-2">
                    <div class="flex h-[26px] w-[26px] items-center justify-center rounded-[8px] bg-accent">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fff"><path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/></svg>
                    </div>
                    <span class="font-serif text-[20px] font-semibold text-ink-strong">Instruksi Asisten AI</span>
                </div>

                <p class="mb-4 text-[13px] leading-relaxed text-ink-muted">
                    Teks ini menjadi <b class="text-ink">instruksi sistem</b> yang dipakai AI saat membalas WhatsApp otomatis
                    <i>maupun</i> saat tombol "Buat balasan AI" di Inbox. Ubah nama agensi, gaya bahasa, atau aturan
                    (mis. jangan sebut harga sebelum viewing) sesuai kebutuhan.
                </p>

                <textarea wire:model="persona" rows="10"
                    class="w-full resize-y rounded-[12px] border border-line-2 bg-white p-4 text-[13.5px] leading-relaxed text-ink outline-none focus:border-accent">{{ $persona }}</textarea>

                <div class="mt-4 flex items-center gap-2.5">
                    <button wire:click="save"
                        class="rounded-[11px] bg-accent px-5 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110">
                        Simpan Persona
                    </button>
                    <button wire:click="resetDefault"
                        class="rounded-[11px] border border-line-2 bg-white px-4 py-2.5 text-[13px] font-semibold text-ink-muted hover:border-accent/40">
                        Kembalikan ke Default
                    </button>
                    <span class="ml-auto text-[12px] text-ink-muted">Perubahan berlaku untuk balasan berikutnya.</span>
                </div>
            </div>

            {{-- ===== Auto-Scoring ===== --}}
            <div class="mt-4 rounded-[16px] border border-line bg-panel p-6">
                <div class="mb-1 flex items-center gap-2">
                    <div class="flex h-[26px] w-[26px] items-center justify-center rounded-[8px] bg-accent">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M23 6l-9.5 9.5-5-5L1 18"/><path d="M17 6h6v6"/></svg>
                    </div>
                    <span class="font-serif text-[20px] font-semibold text-ink-strong">Auto-Scoring Lead</span>
                </div>
                <p class="mb-4 text-[13px] leading-relaxed text-ink-muted">
                    Skor lead dihitung otomatis pada pesan pertama, lalu tiap <b class="text-ink">kelipatan</b> nilai ini.
                    Contoh <b class="text-ink">3</b> = pesan lead ke-1, 3, 6, 9. Isi <b class="text-ink">0</b> untuk mematikan
                    (skor hanya lewat tombol "Hitung ulang" di Inbox).
                </p>
                <div class="flex items-center gap-3">
                    <label class="text-[13px] font-medium text-ink">Hitung tiap</label>
                    <input type="number" min="0" max="50" wire:model="scoringInterval" value="{{ $scoringInterval }}"
                        class="w-20 rounded-[10px] border border-line-2 bg-white px-3 py-2 text-center text-[14px] text-ink outline-none focus:border-accent">
                    <span class="text-[13px] text-ink-muted">pesan lead</span>
                    <button wire:click="saveScoring"
                        class="ml-auto rounded-[11px] bg-accent px-5 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110">
                        Simpan
                    </button>
                </div>
            </div>

            <div class="mt-4 rounded-[14px] border border-accent/20 bg-accent/[0.06] p-4">
                <div class="mb-1 text-[12px] font-semibold uppercase tracking-[1px] text-accent">Tips</div>
                <ul class="list-disc space-y-1 pl-5 text-[12.5px] leading-relaxed text-[#6B6253]">
                    <li>Tetap singkat &amp; jelas &mdash; instruksi panjang membuat balasan lambat.</li>
                    <li>Sebutkan batasan penting (mis. "jangan mengarang harga", "selalu tawarkan viewing").</li>
                    <li>AI tetap membaca riwayat percakapan; persona ini mengatur <i>gaya</i> &amp; <i>aturan</i>-nya.</li>
                </ul>
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
