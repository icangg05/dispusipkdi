@props(['dark' => false, 'compact' => false])
@php($identitas = site()->identitas())
<a {{ $attributes->class(['dk-brand', 'dk-brand--dark' => $dark]) }} href="{{ route('beranda') }}" aria-label="Beranda {{ $identitas['singkatan'] }} {{ $identitas['kota'] }}" wire:navigate>
    @if ($identitas['logo_url'])
        <span class="dk-brand__mark dk-brand__mark--logo" aria-hidden="true"><img src="{{ $identitas['logo_url'] }}" alt=""></span>
    @else
        <span class="dk-brand__mark" aria-hidden="true"><x-dk.icon name="library" :size="$compact ? 20 : 22" :stroke="2.2" /></span>
    @endif
    <span class="dk-brand__text">
        <strong>{{ $identitas['singkatan'] }}</strong>
        <small>{{ $identitas['kota'] }}</small>
    </span>
</a>
