<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => $title])
    <meta name="robots" content="noindex">
</head>
<body>
    <div class="dk-blank">
        <div class="dk-blank__box">
            <x-dk.brand />
            <span class="dk-blank__code">{{ $code }}</span>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <x-dk.button variant="primary" icon="house" :href="route('beranda')" wire:navigate>Kembali ke beranda</x-dk.button>
        </div>
    </div>
    @livewireScriptConfig
</body>
</html>
