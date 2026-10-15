{{--
    Single-series column chart. Pass `data` (PHP array of points) for a static chart, or
    `source` / `title-expr` / `unit-expr` (JavaScript expressions) to follow a parent Alpine component.
--}}
@props(['data' => null, 'title' => null, 'unit' => 'kunjungan', 'source' => null, 'titleExpr' => null, 'unitExpr' => null, 'dark' => true, 'height' => 220])
@php
    $dataJs = $source ?? \Illuminate\Support\Js::from($data ?? []);
    $titleJs = $titleExpr ?? \Illuminate\Support\Js::from($title ?? '');
    $unitJs = $unitExpr ?? \Illuminate\Support\Js::from($unit);
@endphp
<figure {{ $attributes->class(['dk-chart', 'dk-chart--dark' => $dark]) }}
    x-data="visitorChart({ data: () => {{ $dataJs }}, title: () => {{ $titleJs }}, unit: () => {{ $unitJs }}, height: {{ (int) $height }}, dark: {{ $dark ? 'true' : 'false' }} })">
    <figcaption class="dk-chart__title" x-text="titleText">{{ $title }}</figcaption>
    <div class="dk-chart__plot" x-ref="plot" style="min-height: {{ (int) $height }}px">
        <div x-html="svg()" @mousemove="over($event)" @mouseleave="hover = -1"></div>
        <template x-if="tip">
            <div class="dk-chart__tip" :style="{ left: tip.left, top: tip.top }">
                <strong x-text="tip.value"></strong> <span x-text="unitText"></span><small x-text="tip.sub"></small>
            </div>
        </template>
    </div>
    <table class="dk-sr">
        <caption x-text="titleText"></caption>
        <tbody>
            <template x-for="d in points" :key="d.label">
                <tr><th scope="row" x-text="d.label"></th><td x-text="d.value"></td></tr>
            </template>
        </tbody>
    </table>
</figure>
