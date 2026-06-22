<div class="w-full" style="max-width:380px">
    <div class="mb-6 flex flex-col items-center text-center">
        @if (! empty($brandLogo))
            <img src="{{ $brandLogo }}" class="mb-3 h-14 w-14 rounded-[12px] object-cover">
        @else
            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-[12px] bg-accent font-serif text-[28px] font-bold text-white">{{ mb_strtoupper(mb_substr($brandShort ?? 'A', 0, 1)) }}</div>
        @endif
        <div class="font-serif text-[24px] font-bold text-ink-strong">{{ $brandName ?? 'Aterra Realty' }}</div>
        <div class="text-[13px] text-ink-muted">Masuk untuk mengelola CRM</div>
    </div>

    <div class="rounded-[16px] border border-line bg-panel p-6">
        @error('email')
            <div class="mb-4 rounded-[11px] border border-hot/30 bg-hot/10 px-4 py-2.5 text-[13px] text-hot">{{ $message }}</div>
        @enderror

        <form wire:submit="login" class="space-y-4">
            <div>
                <label class="mb-1 block text-[12.5px] font-semibold text-ink">Email</label>
                <input wire:model="email" type="email" autofocus placeholder="nama@perusahaan.com"
                       class="w-full rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent">
            </div>
            <div>
                <label class="mb-1 block text-[12.5px] font-semibold text-ink">Kata sandi</label>
                <input wire:model="password" type="password" placeholder="••••••••"
                       class="w-full rounded-[11px] border border-line-2 bg-white px-3.5 py-2.5 text-[13.5px] text-ink outline-none focus:border-accent">
            </div>
            <label class="flex items-center gap-2 text-[12.5px] text-ink-muted">
                <input wire:model="remember" type="checkbox" class="rounded border-line-2 text-accent focus:ring-accent"> Ingat saya
            </label>
            <button type="submit" wire:loading.attr="disabled"
                    class="w-full rounded-[11px] bg-accent px-5 py-2.5 text-[13.5px] font-semibold text-white hover:brightness-110 disabled:opacity-50">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span wire:loading wire:target="login">Memproses…</span>
            </button>
        </form>
    </div>
</div>
