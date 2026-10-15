{{-- Accordion: one answer open at a time, sliding open and closed. --}}
@props(['items', 'title' => 'Yang sering ditanyakan', 'openFirst' => true])
@php($prefix = $attributes->get('id', 'faq'))
<div {{ $attributes->class('dk-faq') }} x-data="{ open: {{ $openFirst && $items->isNotEmpty() ? 0 : 'null' }} }">
    @if ($title)
        <h3 class="dk-faq__title">{{ $title }}</h3>
    @endif
    @foreach ($items as $faq)
        @php($isOpen = $openFirst && $loop->first)
        <div @class(['dk-faq__item', 'is-open' => $isOpen]) :class="{ 'is-open': open === {{ $loop->index }} }" wire:key="faq-{{ $faq->id }}">
            <h4 class="dk-faq__q">
                <button type="button" class="dk-faq__btn" id="{{ $prefix }}-q{{ $loop->index }}" aria-controls="{{ $prefix }}-a{{ $loop->index }}"
                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}" :aria-expanded="open === {{ $loop->index }} ? 'true' : 'false'"
                    @click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}">
                    <span>{{ $faq->question }}</span><x-dk.icon name="plus" :size="20" class="dk-faq__icon" />
                </button>
            </h4>
            <div class="dk-faq__a" id="{{ $prefix }}-a{{ $loop->index }}" x-show="open === {{ $loop->index }}" x-collapse.duration.300ms @unless ($isOpen) hidden @endunless>
                <p>{{ $faq->answer }}</p>
            </div>
        </div>
    @endforeach
</div>
