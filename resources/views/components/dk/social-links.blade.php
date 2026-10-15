@props(['items' => null, 'variant' => 'icons', 'dark' => false, 'size' => 'md'])
@php($items ??= site()->sosmed())
@if ($variant === 'cards')
    <ul class="dk-soc-cards" role="list">
        @foreach ($items as $s)
            <li>
                <a class="dk-soc-card" href="{{ $s['href'] }}" target="_blank" rel="noopener">
                    <span class="dk-soc-card__icon"><x-dk.icon :name="$s['platform']" :size="24" /></span>
                    <span class="dk-soc-card__text">
                        <strong>{{ $s['label'] }}</strong>
                        <span>{{ $s['handle'] }}</span>
                    </span>
                    <span class="dk-soc-card__cta">Ikuti <x-dk.icon name="arrow-up-right" :size="16" /></span>
                </a>
            </li>
        @endforeach
    </ul>
@else
    <ul {{ $attributes->class(['dk-soc', 'dk-soc--dark' => $dark, 'dk-soc--sm' => $size === 'sm']) }} role="list">
        @foreach ($items as $s)
            <li>
                <a href="{{ $s['href'] }}" target="_blank" rel="noopener" aria-label="{{ $s['label'] }} {{ $s['handle'] }}" title="{{ $s['label'] }}">
                    <x-dk.icon :name="$s['platform']" :size="$size === 'sm' ? 15 : 18" />
                </a>
            </li>
        @endforeach
    </ul>
@endif
