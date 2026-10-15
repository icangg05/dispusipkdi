@props(['items', 'title' => 'Yang sering ditanyakan', 'openFirst' => true])
<div {{ $attributes->class('dk-faq') }}>
    @if ($title)
        <h3 class="dk-faq__title">{{ $title }}</h3>
    @endif
    @foreach ($items as $faq)
        <details class="dk-faq__item" @if ($openFirst && $loop->first) open @endif>
            <summary><span>{{ $faq->question }}</span><x-dk.icon name="plus" :size="20" class="dk-faq__icon" /></summary>
            <p>{{ $faq->answer }}</p>
        </details>
    @endforeach
</div>
