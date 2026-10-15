@php
    $identitas = site()->identitas();
    $kontak = site()->kontak();
    $layanan = \App\Models\Service::query()->active()->limit(6)->get(['id', 'title', 'href']);
    $web = app(\App\Services\StatistikService::class)->all()['web'];
@endphp
<footer class="dk-footer">
    <div class="dk-container dk-footer__grid">
        <div class="dk-footer__brand">
            <x-dk.brand dark />
            <p>{{ $identitas['deskripsi'] }}</p>
            <x-dk.social-links dark />
        </div>
        <div>
            <h2 class="dk-footer__h">Menu</h2>
            <ul class="dk-footer__links" role="list">
                @foreach (site()->nav() as $item)
                    <li><a href="{{ $item['href'] }}" wire:navigate>{{ $item['label'] }}</a></li>
                @endforeach
                <li><a href="{{ route('buku-tamu') }}" wire:navigate>Buku Tamu</a></li>
            </ul>
        </div>
        <div>
            <h2 class="dk-footer__h">Layanan</h2>
            <ul class="dk-footer__links" role="list">
                @foreach ($layanan as $service)
                    <li><a href="{{ $service->href ?: route('beranda').'#layanan' }}" @if (is_internal_page($service->href ?: route('beranda'))) wire:navigate @endif>{{ $service->title }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h2 class="dk-footer__h">Kontak</h2>
            <ul class="dk-footer__contact" role="list">
                @if ($kontak['whatsapp'])<li><x-dk.icon name="whatsapp" :size="16" /><span>{{ $kontak['whatsapp'] }}</span></li>@endif
                @if ($kontak['email'])<li><x-dk.icon name="mail" :size="16" /><span>{{ $kontak['email'] }}</span></li>@endif
                <li><x-dk.icon name="map-pin" :size="16" /><span>{{ $kontak['alamat'] }}</span></li>
                <li><x-dk.icon name="clock" :size="16" /><span>{{ \App\Support\Jadwal::ringkas(site()->jadwal()) }}</span></li>
            </ul>
        </div>
    </div>
    <div class="dk-container dk-footer__bottom">
        <span>© {{ now()->year }} {{ $identitas['nama'] }}</span>
        <span class="dk-footer__counter"><x-dk.icon name="chart-column" :size="14" /> Pengunjung hari ini <strong>{{ \App\Support\Format::number($web['hariIni']) }}</strong> · Total <strong>{{ \App\Support\Format::number($web['total']) }}</strong></span>
    </div>
</footer>
