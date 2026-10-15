<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
</head>
<body>
    {{ $slot }}
    <x-dk.a11y-menu />
    @livewireScriptConfig
</body>
</html>
