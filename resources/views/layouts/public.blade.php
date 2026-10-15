<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body id="top">
    <a class="dk-skip" href="#konten">Langsung ke konten</a>
    <div class="dk-page">
        <main id="konten">
            {{ $slot }}
        </main>
        <x-dk.footer />
    </div>
    <x-dk.wa-fab />
    <x-dk.a11y-menu />
    @livewireScriptConfig
</body>
</html>
