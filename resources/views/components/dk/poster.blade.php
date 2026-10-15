@props(['line1', 'line2' => null, 'sub' => null, 'tone' => 'laut'])
<span {{ $attributes->class(['dk-poster', 'dk-poster--'.($tone ?: 'laut')]) }}>
    <span class="dk-poster__k">{{ site()->get('identitas.singkatan') }} {{ site()->get('identitas.kota') }}</span>
    <span class="dk-poster__t"><span>{{ $line1 }}</span>@if ($line2)<em>{{ $line2 }}</em>@endif</span>
    <span class="dk-poster__s">{{ $sub }}</span>
</span>
