@props(['id' => null, 'tone' => null, 'tight' => false])
<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class(['dk-section', 'dk-section--'.$tone => $tone, 'dk-section--tight' => $tight]) }}>
    <div class="dk-container">{{ $slot }}</div>
</section>
