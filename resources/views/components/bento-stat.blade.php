@props(['stat', 'label', 'icon', 'statKey', 'index' => 0, 'accent' => 1])

<x-bento-tile :index="$index" :accent="$accent" data-stat-tile="{{ $statKey }}" {{ $attributes->class('flex flex-col justify-between gap-4 p-4') }}>
    <div class="flex items-start justify-between gap-3">
        <span class="bento-chip" aria-hidden="true">
            <i class="{{ $icon }} text-xl leading-none"></i>
        </span>
        <span class="bento-delta" data-stat-delta="{{ $statKey }}" aria-hidden="true"></span>
    </div>

    <div class="min-w-0">
        <p class="text-sm font-medium text-muted-foreground">{{ $label }}</p>
        <p class="mt-1 text-3xl font-semibold tracking-tight text-card-foreground tabular-nums">
            <span data-stat="{{ $statKey }}">{{ $stat['value'] }}</span>
        </p>
        <p class="mt-1 text-xs text-muted-foreground" data-stat-hint="{{ $statKey }}">{{ $stat['hint'] }}</p>
    </div>

    @if ($stat['ratio'] !== null)
        <div class="bento-bar" role="presentation">
            <span data-stat-bar="{{ $statKey }}" style="width: {{ $stat['ratio'] }}%"></span>
        </div>
    @endif
</x-bento-tile>
