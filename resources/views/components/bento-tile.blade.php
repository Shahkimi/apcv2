@props(['index' => 0, 'accent' => 1])

<div
    {{ $attributes->class('bento-tile') }}
    style="--i: {{ (int) $index }}; --tile-accent: var(--chart-{{ ((int) $accent - 1) % 5 + 1 }});"
>
    {{ $slot }}
</div>
