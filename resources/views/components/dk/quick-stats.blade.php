@props(['items' => null])
@php($items ??= site()->angka())
@if (count($items))
    <div class="dk-container">
        <ul class="dk-qstats" role="list">
            @foreach ($items as $item)
                <li class="dk-qstat">
                    <span class="dk-tile"><x-dk.icon :name="$item['icon']" :size="22" /></span>
                    <span><strong>{{ $item['value'] }}</strong><small>{{ $item['label'] }}</small></span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
