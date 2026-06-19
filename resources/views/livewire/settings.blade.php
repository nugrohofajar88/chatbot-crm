<div class="flex h-full flex-col">
    <x-page-header title="Persona AI" subtitle="Atur kepribadian & aturan balasan asisten AI" />

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
                    class="w-full resize-y rounded-[12px] border border-line-2 bg-white p-4 text-[13.5px] leading-relaxed text-ink outline-none focus:border-accent"></textarea>

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
