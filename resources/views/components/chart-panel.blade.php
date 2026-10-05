@props(['title', 'id', 'chartKey' => null, 'bare' => false])

<div @class(['rounded-xl border border-border bg-card shadow-sm' => ! $bare, 'h-full' => $bare])>
    <div class="border-b border-border px-4 py-3">
        <h3 class="text-sm font-semibold text-card-foreground">{{ $title }}</h3>
    </div>
    <div class="p-4">
        <div id="{{ $id }}" @if ($chartKey) data-dashboard-chart="{{ $chartKey }}" @endif class="min-h-[260px] w-full"></div>
    </div>
</div>
