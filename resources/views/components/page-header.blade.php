@props(['title' => 'Aterra Realty CRM', 'subtitle' => ''])
<header class="flex h-[72px] flex-none items-center gap-[18px] border-b border-line bg-header px-[26px]">
    <div class="min-w-0">
        <h1 class="font-serif text-[27px] font-semibold leading-[1.05] text-ink-strong">{{ $title }}</h1>
        <div class="mt-px text-[12.5px] text-ink-muted">{{ $subtitle }}</div>
    </div>
    <div class="flex-1"></div>
    <div class="flex w-[240px] items-center gap-2.5 rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#A89C8C" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.7" y2="16.7"/></svg>
        <input placeholder="Cari leads, properti…" class="w-full border-none bg-transparent text-[13.5px] text-[#3A332B] outline-none">
    </div>
    <button class="flex items-center gap-2 rounded-[11px] bg-accent px-4 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Leads Baru
    </button>
</header>
