@props(['icon' => 'inbox', 'title', 'text' => null])
<div class="ad-empty">
    <span class="dk-tile"><x-dk.icon :name="$icon" :size="26" /></span>
    <strong>{{ $title }}</strong>
    @if ($text)<span>{{ $text }}</span>@endif
    {{ $slot }}
</div>
