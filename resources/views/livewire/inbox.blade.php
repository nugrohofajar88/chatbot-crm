@php
    $tempColor = ['hot' => '#C0562C', 'warm' => '#C79A3A', 'cold' => '#9A958A'];
    $tempLabel = ['hot' => 'Hot', 'warm' => 'Warm', 'cold' => 'Cold'];
    $avBg = ['hot' => '#F3DED2', 'warm' => '#F2E6CE', 'cold' => '#E2E1DA'];
    $avFg = ['hot' => '#9C4A24', 'warm' => '#9C7A2E', 'cold' => '#6E6A5E'];
    $badgeBg = ['hot' => '#F3DED2', 'warm' => '#F4E9D0', 'cold' => '#E8E6DE'];
    $badgeFg = ['hot' => '#9C4A24', 'warm' => '#8C6E26', 'cold' => '#6E6A5E'];
    $stageLabel = ['baru' => 'Baru', 'terkualifikasi' => 'Terkualifikasi', 'viewing' => 'Viewing', 'negosiasi' => 'Negosiasi', 'closing' => 'Closing'];
    $rupiah = function ($n) {
        if ($n >= 1_000_000_000) return 'Rp '.rtrim(rtrim(number_format($n / 1_000_000_000, 1, ',', '.'), '0'), ',').' M';
        if ($n >= 1_000_000) return 'Rp '.round($n / 1_000_000).' jt';
        return 'Rp '.number_format($n, 0, ',', '.');
    };
    $sel = $this->selected;
@endphp

<div x-data="{ pane: 'list' }" class="flex h-full flex-col">
    <x-page-header title="Kotak Masuk" subtitle="{{ $this->conversations->count() }} percakapan WhatsApp" />

    <div class="flex min-h-0 flex-1">

        {{-- ===== KOLOM 1: DAFTAR PERCAKAPAN ===== --}}
        <div class="w-full flex-none flex-col border-r border-line bg-panel-2 md:w-[330px]"
             :class="pane === 'list' ? 'flex' : 'hidden md:flex'">
            <div class="flex flex-none items-center px-4 py-3.5">
                <span class="text-[11px] font-semibold uppercase tracking-[1.5px] text-ink-muted">{{ $this->conversations->count() }} Percakapan</span>
            </div>
            <div class="flex-1 overflow-y-auto px-2.5 pb-3.5">
                @foreach ($this->conversations as $c)
                    @php $active = $c->id === $selectedId; $temp = $c->temperature; @endphp
                    <button wire:click="selectConversation({{ $c->id }})" x-on:click="pane = 'thread'" wire:key="conv-{{ $c->id }}"
                        class="mb-[7px] block w-full rounded-[13px] p-[13px] text-left {{ $active ? 'bg-white shadow-[0_4px_14px_rgba(80,55,30,0.08)]' : 'hover:bg-white/40' }}">
                        <div class="flex items-center gap-2.5">
                            <div class="relative flex-none">
                                <div class="flex h-10 w-10 items-center justify-center rounded-[11px] text-sm font-semibold"
                                     style="background: {{ $avBg[$temp] }}; color: {{ $avFg[$temp] }}">{{ $c->contact->initials() }}</div>
                                <span class="absolute -bottom-0.5 -right-0.5 h-[15px] w-[15px] rounded-full border-2 border-panel-2" style="background: {{ $tempColor[$temp] }}"></span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="flex-1 truncate text-[13.5px] font-semibold text-[#2C2620]">{{ $c->contact->name }}</span>
                                    <span class="flex-none text-[11px] text-[#A89C8C]">{{ optional($c->last_message_at)->format('H:i') }}</span>
                                </div>
                                <div class="mt-px text-[11px] text-[#9C8F7E]">WhatsApp &middot; {{ $stageLabel[$c->stage] ?? $c->stage }}</div>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <p class="m-0 line-clamp-1 flex-1 text-[12.5px] leading-snug text-[#766B5C]">{{ optional($c->latestMessage)->body }}</p>
                            @if ($c->unread > 0)
                                <span class="flex h-[18px] min-w-[18px] flex-none items-center justify-center rounded-[9px] bg-accent px-[5px] text-[11px] font-bold text-white">{{ $c->unread }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ===== KOLOM 2: THREAD ===== --}}
        <div class="min-w-0 flex-1 flex-col bg-header" :class="pane === 'thread' ? 'flex' : 'hidden md:flex'">
            @if ($sel)
                {{-- header thread --}}
                <div class="flex h-[60px] flex-none items-center gap-2.5 border-b border-line px-4 md:h-[66px] md:gap-3 md:px-[22px]">
                    <button type="button" @click="pane = 'list'" class="-ml-1 flex h-8 w-8 flex-none items-center justify-center rounded-[9px] text-ink-muted hover:bg-line/60 md:hidden">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <div class="flex h-[40px] w-[40px] flex-none items-center justify-center rounded-[12px] text-[15px] font-semibold md:h-[42px] md:w-[42px]"
                         style="background: {{ $avBg[$sel->temperature] }}; color: {{ $avFg[$sel->temperature] }}">{{ $sel->contact->initials() }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-[15px] font-semibold text-[#2A241E] md:text-[16px]">{{ $sel->contact->name }}</span>
                            <span class="flex-none rounded-full px-2 py-0.5 text-[10.5px] font-semibold uppercase tracking-wide"
                                  style="background: {{ $badgeBg[$sel->temperature] }}; color: {{ $badgeFg[$sel->temperature] }}">{{ $tempLabel[$sel->temperature] }}</span>
                        </div>
                        <div class="mt-px truncate text-[12px] text-[#9C8F7E]">{{ $sel->contact->phone }} &middot; WhatsApp</div>
                    </div>
                    <button wire:click="toggleAi"
                        class="flex flex-none items-center gap-1.5 rounded-[10px] border border-line-2 px-2.5 py-2 text-[12px] font-semibold md:px-3"
                        style="{{ $sel->ai_enabled ? 'background:rgba(176,85,47,0.12);color:#B0552F' : 'background:#EFE9DF;color:#9C8F7E' }}">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/></svg>
                        <span class="hidden sm:inline">AI Otomatis:&nbsp;</span>{{ $sel->ai_enabled ? 'Aktif' : 'Mati' }}
                    </button>
                    <button type="button" @click="pane = 'copilot'" title="Asisten AI"
                        class="flex h-9 w-9 flex-none items-center justify-center rounded-[10px] border border-line-2 text-accent md:hidden">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/></svg>
                    </button>
                </div>

                {{-- daftar pesan (auto-refresh) --}}
                <div wire:poll.5s class="flex-1 space-y-3 overflow-y-auto px-4 py-4 md:px-[26px] md:py-[22px]">
                    <div class="mx-auto w-fit rounded-full bg-[#EDE6DA] px-3 py-1 text-[11px] text-[#A89C8C]">Percakapan</div>
                    @foreach ($sel->messages as $m)
                        @php $isLead = $m->sender === 'lead'; $isAi = $m->sender === 'ai'; @endphp
                        <div class="max-w-[85%] {{ $isLead ? 'mr-auto' : 'ml-auto' }} md:max-w-[74%]" wire:key="msg-{{ $m->id }}">
                            @if ($isAi)
                                <div class="mb-1 flex items-center gap-1.5">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="#B0552F"><path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/></svg>
                                    <span class="text-[11px] font-semibold text-accent">Aterra AI</span>
                                </div>
                            @endif
                            <div class="px-[15px] py-[11px] text-[13.5px] leading-relaxed
                                {{ $isLead ? 'rounded-[4px_16px_16px_16px] border border-[#ECE3D4] bg-white text-[#3A332B]' : '' }}
                                {{ $isAi ? 'rounded-[16px_16px_4px_16px] border border-accent/25 bg-accent/10 text-[#5A3A28]' : '' }}
                                {{ ! $isLead && ! $isAi ? 'rounded-[16px_16px_4px_16px] bg-accent text-white' : '' }}">
                                @if ($m->type === 'image' && $m->mediaUrl())
                                    <a href="{{ $m->mediaUrl() }}" target="_blank">
                                        <img src="{{ $m->mediaUrl() }}" class="max-h-[220px] max-w-full rounded-[10px]" alt="lampiran">
                                    </a>
                                    @if (filled($m->body))<div class="mt-1.5">{{ $m->body }}</div>@endif
                                @elseif ($m->type === 'document' && $m->mediaUrl())
                                    <a href="{{ $m->mediaUrl() }}" target="_blank" class="flex items-center gap-2 underline">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        {{ data_get($m->payload, 'name', 'Dokumen') }}
                                    </a>
                                    @if (filled($m->body))<div class="mt-1.5">{{ $m->body }}</div>@endif
                                @else
                                    {{ $m->body }}
                                @endif
                            </div>
                            <div class="mt-1 text-[10.5px] text-[#B0A493] {{ $isLead ? 'text-left' : 'text-right' }}">{{ $m->created_at->format('H:i') }}</div>
                        </div>
                    @endforeach
                </div>

                {{-- input balasan --}}
                <div class="flex-none border-t border-line px-4 pb-[18px] pt-3 md:px-[22px]">
                    {{-- preview lampiran --}}
                    @if ($attachment)
                        @php $ext = strtolower($attachment->getClientOriginalExtension()); @endphp
                        <div class="mb-2 flex items-center gap-3 rounded-[12px] border border-line-2 bg-panel-2 p-2.5">
                            @if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                                <img src="{{ $attachment->temporaryUrl() }}" class="h-12 w-12 flex-none rounded-[8px] object-cover" alt="preview">
                            @else
                                <div class="flex h-12 w-12 flex-none items-center justify-center rounded-[8px] border border-line-2 bg-white text-ink-muted">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1 truncate text-[12.5px] text-ink">{{ $attachment->getClientOriginalName() }}</div>
                            <button wire:click="$set('attachment', null)" class="flex-none rounded-[8px] px-2 py-1 text-[12px] text-ink-muted hover:text-accent">Batal</button>
                            <button wire:click="sendAttachment" wire:loading.attr="disabled" wire:target="sendAttachment"
                                class="flex-none rounded-[9px] bg-accent px-3 py-1.5 text-[12px] font-semibold text-white disabled:opacity-50">
                                <span wire:loading.remove wire:target="sendAttachment">Kirim</span>
                                <span wire:loading wire:target="sendAttachment">Mengirim&hellip;</span>
                            </button>
                        </div>
                    @endif

                    <div class="mb-2.5 flex gap-2">
                        <button wire:click="generateAiDraft" wire:loading.attr="disabled" wire:target="generateAiDraft"
                            class="flex items-center gap-1.5 rounded-[9px] border border-accent/30 bg-accent/[0.08] px-3 py-1.5 text-[12px] font-semibold text-accent hover:bg-accent/15 disabled:opacity-50">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/></svg>
                            <span wire:loading.remove wire:target="generateAiDraft">Buat balasan AI</span>
                            <span wire:loading wire:target="generateAiDraft">Membuat&hellip;</span>
                        </button>
                    </div>
                    <div class="flex items-end gap-2 rounded-[15px] border border-line-2 bg-white py-2 pl-2 pr-2">
                        {{-- tombol lampiran --}}
                        <label wire:loading.class="opacity-50" wire:target="attachment"
                            class="flex h-10 w-10 flex-none cursor-pointer items-center justify-center rounded-[10px] text-ink-muted hover:bg-line/50">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                            <input type="file" wire:model="attachment" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </label>
                        <textarea wire:model="draft" rows="1" placeholder="Tulis balasan, atau gunakan saran AI&hellip;"
                            wire:keydown.enter.prevent="sendReply"
                            class="max-h-[90px] flex-1 resize-none border-none bg-transparent py-2.5 text-[13.5px] leading-relaxed text-[#3A332B] outline-none"></textarea>
                        <button wire:click="sendReply"
                            class="flex h-10 w-10 flex-none items-center justify-center rounded-[11px] bg-accent text-white hover:brightness-110">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </button>
                    </div>
                </div>
            @else
                <div class="flex flex-1 items-center justify-center text-ink-muted">Pilih percakapan</div>
            @endif
        </div>

        {{-- ===== KOLOM 3: COPILOT AI ===== --}}
        @if ($sel)
            <div class="w-full flex-none flex-col overflow-y-auto border-l border-line bg-panel md:w-[312px]"
                 :class="pane === 'copilot' ? 'flex' : 'hidden md:flex'">
                <div class="flex items-center gap-2 px-[18px] pb-3.5 pt-[18px]">
                    <button type="button" @click="pane = 'thread'" class="-ml-1 flex h-8 w-8 flex-none items-center justify-center rounded-[9px] text-ink-muted hover:bg-line/60 md:hidden">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <div class="flex h-[26px] w-[26px] items-center justify-center rounded-[8px] bg-accent">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="#fff"><path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/></svg>
                    </div>
                    <span class="font-serif text-[19px] font-semibold text-[#2A241E]">Asisten AI</span>
                </div>

                {{-- skor lead --}}
                <div class="px-[18px] pb-1.5">
                    <div class="rounded-[14px] border border-[#EBE2D2] bg-white p-3.5">
                        <div class="mb-2.5 flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-[1px] text-ink-muted">Skor Lead</span>
                            <div class="flex items-center gap-2.5">
                                <button wire:click="recomputeScore" wire:loading.attr="disabled" wire:target="recomputeScore"
                                    class="flex items-center gap-1 text-[11px] font-semibold text-accent hover:underline disabled:opacity-50">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.5 9a9 9 0 0 1 14.8-3.4L23 10M1 14l4.7 4.4A9 9 0 0 0 20.5 15"/></svg>
                                    <span wire:loading.remove wire:target="recomputeScore">Hitung ulang</span>
                                    <span wire:loading wire:target="recomputeScore">Menghitung&hellip;</span>
                                </button>
                                <span class="font-serif text-[22px] font-bold" style="color: {{ $tempColor[$sel->temperature] }}">{{ $sel->score }}</span>
                            </div>
                        </div>
                        @php $sb = $sel->latestScore; @endphp
                        @foreach (['budget' => 'Kesesuaian Budget', 'engagement' => 'Tingkat Engagement', 'urgency' => 'Urgensi'] as $key => $lbl)
                            @php $val = $sb?->{$key} ?? 0; @endphp
                            <div class="mb-2.5">
                                <div class="mb-1 flex justify-between text-[11.5px] text-[#7C7164]"><span>{{ $lbl }}</span><span class="font-semibold">{{ $val }}</span></div>
                                <div class="h-1.5 overflow-hidden rounded-[6px] bg-[#EDE6DA]"><div class="h-full rounded-[6px] bg-accent" style="width: {{ $val }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ringkasan --}}
                <div class="px-[18px] pb-1.5 pt-3.5">
                    <span class="text-[11px] font-semibold uppercase tracking-[1px] text-ink-muted">Ringkasan Percakapan</span>
                    <p class="mt-2 text-[12.5px] leading-relaxed text-[#5E5648]">{{ $sel->summary }}</p>
                </div>

                {{-- preferensi --}}
                @if (!empty($sel->contact->prefs))
                    <div class="px-[18px] pb-1.5 pt-3.5">
                        <span class="text-[11px] font-semibold uppercase tracking-[1px] text-ink-muted">Preferensi</span>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($sel->contact->prefs as $p)
                                <span class="rounded-full border border-[#E6DCCB] bg-[#F0E8DB] px-3 py-1 text-[12px] text-[#6B6253]">{{ $p }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- rekomendasi properti --}}
                <div class="px-[18px] pb-[22px] pt-3.5">
                    <span class="text-[11px] font-semibold uppercase tracking-[1px] text-ink-muted">Rekomendasi Properti</span>
                    <div class="mt-2 flex flex-col gap-2">
                        @foreach ($this->recommendations as $r)
                            <div class="flex items-center gap-3 rounded-[13px] border border-[#EBE2D2] bg-white p-2.5">
                                <div class="h-[46px] w-[46px] flex-none rounded-[9px]" style="background: linear-gradient(135deg,#D9CBB6,#B89B7C)"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-[12.5px] font-semibold text-[#2C2620]">{{ $r->title }}</div>
                                    <div class="mt-px text-[11px] text-[#9C8F7E]">{{ $r->beds }} KT &middot; {{ $r->area }} m&sup2;</div>
                                    <div class="mt-0.5 text-[12px] font-bold text-accent">{{ $rupiah($r->price) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- toast --}}
    @if ($toast)
        <div wire:key="toast-{{ md5($toast) }}" x-data="{s:true}" x-init="setTimeout(() => s = false, 2600)" x-show="s" x-transition
             class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-[12px] bg-sidebar px-5 py-3 text-[13px] font-medium text-[#F4EFE7] shadow-[0_12px_30px_rgba(0,0,0,0.25)]">
            {{ $toast }}
        </div>
    @endif
</div>
