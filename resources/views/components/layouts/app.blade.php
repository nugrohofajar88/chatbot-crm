@php
    use App\Models\Conversation;
    $navItems = [
        ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
        ['route' => 'inbox',     'label' => 'Kotak Masuk', 'icon' => 'chat'],
        ['route' => 'pipeline',  'label' => 'Pipeline', 'icon' => 'bars'],
        ['route' => 'listings',  'label' => 'Listing Properti', 'icon' => 'home'],
        ['route' => 'contacts',  'label' => 'Profil Kontak', 'icon' => 'user'],
    ];
    $unreadTotal = (int) Conversation::sum('unread');
    $aiToday = Conversation::where('ai_enabled', true)->count();
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Aterra Realty CRM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-bg font-sans text-ink antialiased">
<div class="flex h-screen w-full overflow-hidden">

    {{-- ===== SIDEBAR ===== --}}
    <aside class="flex w-[248px] flex-none flex-col bg-sidebar px-4 py-[22px] text-sidebar-ink">
        <div class="flex items-center gap-3 px-2 pb-[22px] pt-1.5">
            <div class="flex h-[34px] w-[34px] items-center justify-center rounded-[9px] bg-accent font-serif text-[22px] font-bold leading-none text-white">A</div>
            <div>
                <div class="font-serif text-[23px] font-bold leading-none text-[#F4EFE7]">Aterra</div>
                <div class="mt-0.5 text-[10px] uppercase tracking-[2.5px] text-sidebar-muted">Realty CRM</div>
            </div>
        </div>

        <div class="px-2.5 pb-2 pt-1.5 text-[10px] uppercase tracking-[2px] text-[#6F6557]">Ruang Kerja</div>
        <nav class="flex flex-col gap-[3px]">
            @foreach ($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-sm font-medium transition-colors {{ $active ? 'bg-accent/15 text-[#F4EFE7]' : 'text-[#B7AD9E] hover:bg-white/5' }}">
                    <x-nav-icon :name="$item['icon']" />
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if ($item['route'] === 'inbox' && $unreadTotal > 0)
                        <span class="rounded-full bg-accent px-[7px] py-px text-[11px] font-bold text-white">{{ $unreadTotal }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="mt-6 rounded-[14px] border border-accent/20 bg-accent/[0.14] p-3.5">
            <div class="mb-1.5 flex items-center gap-2">
                <span class="h-[7px] w-[7px] rounded-full bg-accent shadow-[0_0_0_3px_rgba(176,85,47,0.25)]"></span>
                <span class="text-[11px] font-semibold uppercase tracking-[1.5px] text-accent-soft">Aterra AI</span>
            </div>
            <div class="text-[12.5px] leading-relaxed text-[#B7AD9E]">Asisten aktif menangani <b class="text-[#EBE3D6]">{{ $aiToday }} chat</b> secara otomatis.</div>
        </div>

        <div class="mt-auto flex items-center gap-3 border-t border-white/[0.07] px-2 pt-3.5">
            <div class="flex h-[38px] w-[38px] items-center justify-center rounded-[10px] bg-[#3A322B] text-sm font-semibold text-[#E7DCCB]">DP</div>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[13.5px] font-semibold text-[#F0E9DD]">Dimas Prakoso</div>
                <div class="text-[11px] text-sidebar-muted">Operator</div>
            </div>
        </div>
    </aside>

    {{-- ===== MAIN ===== --}}
    <section class="flex min-w-0 flex-1 flex-col">
        {{ $slot }}
    </section>
</div>
@livewireScripts
</body>
</html>
