@props(['hero', 'stat', 'sesi' => '—', 'index' => 0])

@php
    $circumference = 2 * M_PI * 54;
    $ratio = max(0, min(100, (float) ($stat['ratio'] ?? 0)));
    $offset = $circumference * (1 - $ratio / 100);
@endphp

<x-bento-tile :index="$index" :accent="1" data-stat-tile="{{ $hero['key'] }}" {{ $attributes->class('bento-hero relative flex flex-col justify-between gap-6 overflow-hidden p-6') }}>
    <span class="bento-blob" aria-hidden="true"></span>

    <div class="relative flex items-start justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="bento-chip" aria-hidden="true">
                <i class="{{ $hero['icon'] }} text-xl leading-none"></i>
            </span>
            <p class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ $hero['label'] }}</p>
        </div>
        <span class="bento-live-pill">
            <span class="relative flex h-2 w-2">
                <span class="bento-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            {{ __('LIVE') }}
        </span>
    </div>

    <div class="relative flex flex-wrap items-center gap-6">
        <div class="relative h-36 w-36 shrink-0">
            <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" aria-hidden="true">
                <circle cx="60" cy="60" r="54" fill="none" stroke-width="10" class="bento-ring-track" />
                <circle
                    cx="60" cy="60" r="54" fill="none" stroke-width="10" stroke-linecap="round"
                    class="bento-ring"
                    data-ring="{{ $hero['key'] }}"
                    data-circ="{{ $circumference }}"
                    style="--circ: {{ $circumference }}; stroke-dasharray: {{ $circumference }}; stroke-dashoffset: {{ $offset }};"
                />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center text-center">
                <span class="text-2xl font-semibold tabular-nums text-card-foreground">
                    <span data-ring-label="{{ $hero['key'] }}">{{ number_format($ratio, 1) }}%</span>
                </span>
            </div>
        </div>

        <div class="min-w-0">
            <p class="text-5xl font-semibold tracking-tight text-card-foreground tabular-nums">
                <span data-stat="{{ $hero['key'] }}">{{ $stat['value'] }}</span>
            </p>
            <p class="mt-2 text-sm text-muted-foreground" data-stat-hint="{{ $hero['key'] }}">{{ $stat['hint'] }}</p>
            <span class="bento-delta mt-2" data-stat-delta="{{ $hero['key'] }}" aria-hidden="true"></span>
        </div>
    </div>

    <div class="relative flex items-center justify-between gap-3 rounded-xl bg-background/60 px-4 py-3 text-sm ring-1 ring-border/60 backdrop-blur">
        <span class="text-muted-foreground">{{ __('Sesi di udara') }}</span>
        <span class="font-semibold text-card-foreground" data-status="sesi">{{ $sesi }}</span>
    </div>
</x-bento-tile>
