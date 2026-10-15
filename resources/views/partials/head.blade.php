@php
    $identitas = site()->identitas();
    $siteName = $identitas['singkatan'].' '.$identitas['kota'];
    $pageTitle = filled($title ?? null) ? $title.' | '.$siteName : $identitas['nama'];
    $pageDescription = $description ?? $identitas['deskripsi'];
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit($pageDescription, 160) }}">
<meta name="theme-color" content="#06292e">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit($pageDescription, 200) }}">
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:url" content="{{ url()->current() }}">
@if (! empty($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
@endif
<link rel="icon" href="{{ $identitas['logo_url'] ?? asset('favicon.svg') }}">
<script>
    {{-- Apply saved accessibility choices before the first paint. --}}
    (function () {
        try {
            var s = JSON.parse(localStorage.getItem('dispusip-a11y') || 'null');
            if (!s) return;
            var c = document.documentElement.classList;
            if (s.teks === 1) c.add('dk-a11y-teks-1');
            if (s.teks === 2) c.add('dk-a11y-teks-2');
            ['abu', 'tautan', 'spasi', 'diam', 'kursor', 'suara'].forEach(function (k) { if (s[k]) c.add('dk-a11y-' + k); });
            if (s.kontras) document.documentElement.setAttribute('data-theme', 'kontras');
        } catch (e) {}
    })();
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
