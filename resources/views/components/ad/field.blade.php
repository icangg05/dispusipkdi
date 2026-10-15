@props(['label' => null, 'for' => null, 'error' => null, 'help' => null, 'optional' => false])
<div {{ $attributes->class(['ad-field', 'has-error' => $error && $errors->has($error)]) }}>
    @if ($label)
        <label class="ad-label" @if ($for) for="{{ $for }}" @endif>{{ $label }}@if ($optional) <small>(opsional)</small>@endif</label>
    @endif
    {{ $slot }}
    @if ($help)
        <span class="ad-help">{!! $help !!}</span>
    @endif
    @if ($error)
        @error($error)<span class="ad-error"><x-dk.icon name="circle-alert" :size="15" /> {{ $message }}</span>@enderror
    @endif
</div>
