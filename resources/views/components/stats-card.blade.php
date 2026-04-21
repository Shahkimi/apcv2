@props(['label', 'value', 'hint' => null])

<div class="rounded-xl border border-border bg-card p-4 shadow-sm">
    <p class="text-sm font-medium text-muted-foreground">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-card-foreground">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-muted-foreground">{{ $hint }}</p>
    @endif
</div>
