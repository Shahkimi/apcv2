@props(['label', 'value', 'hint' => null, 'icon' => null, 'statKey' => null])

@php
    $baseCard =
        'rounded-xl border border-border bg-card p-4 shadow-sm transition-shadow transition-colors duration-200 hover:border-primary/20 hover:shadow-md';
@endphp

<div {{ $attributes->class($baseCard) }}>
    @if ($icon)
        <div class="flex items-start gap-4">
            <span
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground ring-1 ring-border/60 dark:bg-muted/80"
                aria-hidden="true"
            >
                <i class="{{ $icon }} text-xl leading-none"></i>
            </span>
            <div class="min-w-0 flex-1">
    @endif
    <p class="text-sm font-medium text-muted-foreground">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-card-foreground tabular-nums">
        @if ($statKey)
            <span data-stat="{{ $statKey }}">{{ $value }}</span>
        @else
            {{ $value }}
        @endif
    </p>
    @if ($hint)
        <p class="mt-1 text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
    @if ($icon)
            </div>
        </div>
    @endif
</div>
