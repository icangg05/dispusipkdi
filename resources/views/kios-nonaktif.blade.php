<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Mode kios belum aktif'])
    <meta name="robots" content="noindex">
</head>
<body>
    <div class="dk-blank">
        <div class="dk-blank__box">
            <x-dk.brand />
            <span class="dk-tile"><x-dk.icon name="tablet-smartphone" :size="24" /></span>
            <h1>Perangkat ini belum menjadi kios</h1>
            <p>Mode kios buku tamu hanya terbuka di tablet meja layanan yang sudah diaktifkan petugas. Pengunjung dapat mengisi buku tamu dari ponsel lewat halaman Buku Tamu.</p>
            <div class="flex flex-wrap gap-2">
                <x-dk.button variant="lentera" icon="notebook-pen" :href="route('buku-tamu')" wire:navigate>Isi buku tamu</x-dk.button>
                <x-dk.button variant="secondary" icon="log-in" :href="route('admin.kode')" wire:navigate>Aktifkan sebagai petugas</x-dk.button>
            </div>
        </div>
    </div>
    @livewireScriptConfig
</body>
</html>
