@props(['icon' => 'book-marked', 'dark' => false])
<span {{ $attributes->class(['dk-eyebrow', 'dk-eyebrow--dark' => $dark]) }}>
    <x-dk.icon :name="$icon" :size="14" :stroke="2.4" />
    {{ $slot }}
</span>
