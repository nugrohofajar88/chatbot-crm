<x-layouts.app :title="$title">
    <x-page-header :title="$title" subtitle="Segera hadir" />
    <div class="flex flex-1 flex-col items-center justify-center gap-2 text-ink-muted">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
        <div class="font-serif text-[22px] text-ink-strong">Modul {{ $title }}</div>
        <div class="text-[13px]">Sedang dibangun &mdash; akan menyusul setelah Inbox.</div>
    </div>
</x-layouts.app>
