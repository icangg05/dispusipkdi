<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
</head>
<body>
    {{ $slot }}
    @livewireScriptConfig
</body>
</html>
