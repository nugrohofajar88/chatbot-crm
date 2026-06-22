<div class="flex h-full flex-col">
    <x-page-header title="Katalog Produk" subtitle="Kelola produk/layanan yang ditawarkan" />

    <div class="flex-1 overflow-y-auto p-[26px]">
        <div class="mx-auto max-w-[780px]">

            {{-- Toolbar --}}
            <div class="mb-4 flex items-center gap-3">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari produk atau kategori…"
                       class="flex-1 rounded-[12px] border border-line-2 bg-white px-4 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent">
                <button wire:click="create"
                        class="rounded-[11px] bg-accent px-5 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110">+ Produk</button>
            </div>

            @if ($toast)
                <div class="mb-4 rounded-[11px] border border-success/30 bg-success/10 px-4 py-2.5 text-[13px] text-success">{{ $toast }}</div>
            @endif

            {{-- List --}}
            <div class="overflow-hidden rounded-[16px] border border-line bg-panel">
                <table class="w-full text-[13.5px]">
                    <thead class="bg-panel-2 text-left text-[11.5px] uppercase tracking-wide text-ink-muted">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Produk</th>
                            <th class="px-4 py-3 text-right font-semibold">Harga</th>
                            <th class="px-4 py-3 text-right font-semibold">Stok</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($this->items as $p)
                            <tr class="hover:bg-panel-2/50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($p->coverUrl())
                                            <img src="{{ $p->coverUrl() }}" class="h-11 w-11 flex-none rounded-[8px] border border-line object-cover" alt="">
                                        @else
                                            <div class="flex h-11 w-11 flex-none items-center justify-center rounded-[8px] border border-line bg-panel-2 text-ink-muted">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-semibold text-ink-strong">{{ $p->name }}</div>
                                            <div class="text-[11.5px] text-ink-muted">{{ $p->category ?: 'Tanpa kategori' }}@if (count($p->media ?? [])) &middot; {{ count($p->media) }} file @endif</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-ink whitespace-nowrap">Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-ink">{{ $p->stock }}</td>
                                <td class="px-4 py-3">
                                    @php $st = [
                                        'tersedia' => ['Tersedia', 'bg-success/10 text-success'],
                                        'habis' => ['Habis', 'bg-hot/10 text-hot'],
                                        'nonaktif' => ['Nonaktif', 'bg-ink-muted/10 text-ink-muted'],
                                    ][$p->status] ?? ['—', 'bg-ink-muted/10 text-ink-muted']; @endphp
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $st[1] }}">{{ $st[0] }}</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button wire:click="edit({{ $p->id }})" class="font-semibold text-accent hover:underline">Edit</button>
                                    <button wire:click="confirmDelete({{ $p->id }})" class="ml-3 font-semibold text-hot hover:underline">Hapus</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-12 text-center text-ink-muted">
                                <div class="font-serif text-[18px] text-ink-strong">Belum ada produk</div>
                                <div class="mt-1 text-[13px]">Klik "+ Produk" untuk menambah item katalog pertama.</div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal form --}}
    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-6" wire:key="form">
            <div class="flex max-h-[88vh] w-full max-w-[460px] flex-col overflow-hidden rounded-[18px] border border-line bg-panel shadow-xl">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <span class="font-serif text-[19px] font-semibold text-ink-strong">{{ $editingId ? 'Edit Produk' : 'Tambah Produk' }}</span>
                    <button wire:click="$set('showForm', false)" class="text-ink-muted hover:text-ink">✕</button>
                </div>

                <div class="flex-1 space-y-3.5 overflow-y-auto px-5 py-4">
                    <div>
                        <label class="mb-1 block text-[12.5px] font-semibold text-ink">Nama produk</label>
                        <input wire:model="name" type="text" class="w-full rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent">
                        @error('name')<span class="text-[12px] text-hot">{{ $message }}</span>@enderror
                    </div>

                    {{-- Media (banyak gambar / file) --}}
                    <div>
                        <label class="mb-1 block text-[12.5px] font-semibold text-ink">Gambar & File <span class="font-normal text-ink-muted">(boleh banyak, termasuk PDF)</span></label>

                        @if (count($media) || count($newFiles))
                            <div class="mb-2 flex flex-wrap gap-2">
                                @foreach ($media as $i => $m)
                                    <div class="relative">
                                        @if ($m['is_image'] ?? false)
                                            <img src="{{ Storage::disk('public_uploads')->url($m['path']) }}" class="h-16 w-16 rounded-[9px] border border-line object-cover">
                                        @else
                                            <div class="flex h-16 w-16 flex-col items-center justify-center rounded-[9px] border border-line bg-panel-2 px-1 text-center">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-hot"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                                <span class="mt-0.5 line-clamp-1 text-[8px] text-ink-muted">{{ \Illuminate\Support\Str::limit($m['name'] ?? 'file', 8) }}</span>
                                            </div>
                                        @endif
                                        <button type="button" wire:click="removeMedia({{ $i }})" class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-hot text-[11px] text-white">✕</button>
                                    </div>
                                @endforeach

                                @foreach ($newFiles as $i => $f)
                                    @php $isImg = in_array(strtolower($f->getClientOriginalExtension()), ['jpg','jpeg','png','webp']); @endphp
                                    <div class="relative">
                                        @if ($isImg)
                                            <img src="{{ $f->temporaryUrl() }}" class="h-16 w-16 rounded-[9px] border border-line object-cover">
                                        @else
                                            <div class="flex h-16 w-16 flex-col items-center justify-center rounded-[9px] border border-line bg-panel-2 px-1 text-center text-[8px] text-ink-muted">{{ \Illuminate\Support\Str::limit($f->getClientOriginalName(), 10) }}</div>
                                        @endif
                                        <button type="button" wire:click="removeNewFile({{ $i }})" class="absolute -right-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-hot text-[11px] text-white">✕</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <label class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-[12px] border-2 border-dashed border-line-2 bg-white px-4 py-6 text-center transition-colors hover:border-accent hover:bg-panel-2">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-ink-muted"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                            <span class="text-[13px] font-semibold text-ink">Klik untuk unggah di sini</span>
                            <span class="text-[11px] text-ink-muted">Gambar atau file &middot; JPG/PNG/WebP/PDF &middot; maks 8MB per file</span>
                            <input wire:model="newFiles" type="file" multiple accept="image/*,.pdf" class="hidden">
                        </label>
                        <div wire:loading wire:target="newFiles" class="mt-1 text-[11.5px] text-ink-muted">Mengunggah…</div>
                        @error('newFiles.*')<span class="text-[12px] text-hot">{{ $message }}</span>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-[12.5px] font-semibold text-ink">Kategori</label>
                        <input wire:model="category" type="text" placeholder="mis. Makanan, Jasa" class="w-full rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div x-data="{ price: @entangle('price') }">
                            <label class="mb-1 block text-[12.5px] font-semibold text-ink">Harga (Rp)</label>
                            <input type="text" inputmode="numeric"
                                   x-init="$el.value = price ? Number(price).toLocaleString('id-ID') : ''"
                                   x-on:input="let r = $el.value.replace(/[^0-9]/g,''); price = (r === '' ? 0 : parseInt(r)); $el.value = (r ? Number(r).toLocaleString('id-ID') : '')"
                                   class="w-full rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent">
                            @error('price')<span class="text-[12px] text-hot">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-[12.5px] font-semibold text-ink">Stok</label>
                            <input wire:model="stock" type="number" min="0" class="w-full rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent">
                            @error('stock')<span class="text-[12px] text-hot">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-[12.5px] font-semibold text-ink">Status</label>
                        <select wire:model="status" class="w-full rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent">
                            <option value="tersedia">Tersedia</option>
                            <option value="habis">Habis</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[12.5px] font-semibold text-ink">Deskripsi</label>
                        <textarea wire:model="description" rows="3" class="w-full resize-y rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-line px-5 py-4">
                    <button wire:click="$set('showForm', false)" class="rounded-[11px] border border-line-2 bg-white px-4 py-2.5 text-[13px] font-semibold text-ink-muted hover:border-accent/40">Batal</button>
                    <button wire:click="save" wire:loading.attr="disabled" class="rounded-[11px] bg-accent px-5 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110">Simpan</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal konfirmasi hapus --}}
    @if ($confirmingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-6" wire:key="confirm-delete">
            <div class="w-full rounded-[16px] border border-line bg-panel p-6 text-center" style="max-width:360px">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-hot/10 text-hot">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                </div>
                <div class="font-serif text-[18px] font-semibold text-ink-strong">Hapus produk?</div>
                <p class="mt-1 text-[13px] text-ink-muted">"{{ $confirmingDeleteName }}" akan dihapus permanen beserta file-nya. Tindakan ini tidak bisa dibatalkan.</p>
                <div class="mt-5 flex justify-center gap-2">
                    <button wire:click="$set('confirmingDeleteId', null)" class="rounded-[11px] border border-line-2 bg-white px-4 py-2.5 text-[13px] font-semibold text-ink-muted hover:border-accent/40">Batal</button>
                    <button wire:click="delete({{ $confirmingDeleteId }})" class="rounded-[11px] bg-hot px-5 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
