@php
    $themeKey = \App\Models\Setting::get('THEME', config('themes.default'));
    $themeTokens = config('themes.presets.'.$themeKey.'.tokens')
        ?? config('themes.presets.'.config('themes.default').'.tokens', []);
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ $brandName ?? 'Aterra Realty' }} CRM</title>
    @if (! empty($brandLogo))<link rel="icon" href="{{ $brandLogo }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @if (! empty($themeTokens))
        <style>:root{@foreach ($themeTokens as $name => $hex)--color-{{ $name }}:{{ $hex }};@endforeach}</style>
    @endif
</head>
<body class="h-full bg-bg font-sans text-ink antialiased">
<div class="flex min-h-screen items-center justify-center p-4">
    {{ $slot }}
</div>
@livewireScripts
</body>
</html>
