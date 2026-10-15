@props(['kontak' => null, 'jadwal' => null])
@php
    $kontak ??= site()->kontak();
    $items = array_values(array_filter([
        $kontak['whatsappLink'] ? ['icon' => 'whatsapp', 'tone' => 'wa', 'label' => 'WhatsApp', 'value' => $kontak['whatsapp'], 'help' => 'Balasan pada jam layanan', 'href' => $kontak['whatsappLink'], 'cta' => 'Chat sekarang', 'external' => true] : null,
        $kontak['email'] ? ['icon' => 'mail', 'tone' => null, 'label' => 'Email', 'value' => $kontak['email'], 'help' => 'Surat resmi & permohonan data', 'href' => 'mailto:'.$kontak['email'], 'cta' => 'Kirim email', 'external' => false] : null,
        $kontak['telepon'] ? ['icon' => 'phone', 'tone' => null, 'label' => 'Telepon kantor', 'value' => $kontak['telepon'], 'help' => 'Pada jam layanan', 'href' => 'tel:'.preg_replace('/[^\d+]/', '', $kontak['telepon']), 'cta' => 'Telepon', 'external' => false] : null,
        ['icon' => 'map-pin', 'tone' => null, 'label' => 'Alamat kantor', 'value' => $kontak['alamat'], 'help' => $kontak['catatan_alamat'] ?: 'Kantor Dispusip', 'href' => $kontak['maps_link'] ?: '#', 'cta' => 'Petunjuk arah', 'external' => true],
    ]));
@endphp
<div class="dk-contact">
    <div class="dk-contact__list">
        @foreach ($items as $item)
            <a class="dk-ccard" href="{{ $item['href'] }}" @if ($item['external']) target="_blank" rel="noopener" @endif>
                <span @class(['dk-tile', 'dk-tile--wa' => $item['tone'] === 'wa'])><x-dk.icon :name="$item['icon']" :size="22" /></span>
                <span class="dk-ccard__body">
                    <small>{{ $item['label'] }}</small>
                    <strong>{{ $item['value'] }}</strong>
                    <span>{{ $item['help'] }}</span>
                </span>
                <span class="dk-ccard__cta">{{ $item['cta'] }} <x-dk.icon name="arrow-up-right" :size="15" /></span>
            </a>
        @endforeach
        <x-dk.service-hours :jadwal="$jadwal" />
    </div>
    <div class="dk-map">
        @if ($kontak['maps_embed'])
            <iframe src="{{ $kontak['maps_embed'] }}" title="Peta lokasi kantor {{ site()->get('identitas.singkatan') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        @else
            <x-dk.map-illustration />
            <span class="dk-map__pin" aria-hidden="true"><x-dk.icon name="library" :size="22" /></span>
            <div class="dk-map__card">
                <strong>{{ site()->get('identitas.singkatan') }} {{ site()->get('identitas.kota') }}</strong>
                <span>{{ $kontak['alamat'] }}</span>
                @if ($kontak['maps_link'])
                    <x-dk.button variant="primary" size="sm" icon="navigation" :href="$kontak['maps_link']" target="_blank" rel="noopener">Buka di peta</x-dk.button>
                @endif
            </div>
        @endif
    </div>
</div>
