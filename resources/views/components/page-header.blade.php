@props(['title' => 'Aterra Realty CRM', 'subtitle' => ''])
<header class="flex h-[64px] flex-none items-center gap-3 border-b border-line bg-header px-4 md:h-[72px] md:gap-[18px] md:px-[26px]">
    {{-- hamburger (mobile) --}}
    <button type="button" @click="sidebar = true"
        class="-ml-1 flex h-9 w-9 flex-none items-center justify-center rounded-[10px] text-ink-muted hover:bg-line/60 md:hidden">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="min-w-0">
        <h1 class="truncate font-serif text-[19px] font-semibold leading-tight text-ink-strong md:text-[27px] md:leading-[1.05]">{{ $title }}</h1>
        <div class="mt-px hidden text-[12.5px] text-ink-muted md:block">{{ $subtitle }}</div>
    </div>
    <div class="flex-1"></div>
    <div class="hidden w-[240px] items-center gap-2.5 rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 md:flex">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#A89C8C" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.7" y2="16.7"/></svg>
        <input placeholder="Cari leads, properti…" class="w-full border-none bg-transparent text-[13.5px] text-[#3A332B] outline-none">
    </div>
    <button class="flex flex-none items-center gap-2 rounded-[11px] bg-accent px-3 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110 md:px-4">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span class="hidden sm:inline">Leads Baru</span>
    </button>
</header>
