@props([
    'title',
    'subtitle' => null,
    'role',
    'layout',
    'payload',
    'allSesis',
    'selectedSesiId' => null,
    'pollUrl',
])

@php
    $toggleTrackClass =
        'peer h-5 w-9 rounded-full bg-muted ring-1 ring-border/60 transition-all after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-background after:shadow-sm after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-ring peer-focus-visible:ring-offset-2';

    $chartSpans = [
        'lg' => 'md:col-span-12 xl:col-span-8',
        'md' => 'md:col-span-6 xl:col-span-6',
        'sm' => 'md:col-span-6 xl:col-span-4',
    ];

    $cards = $layout['cards'];
    $lastCard = count($cards) - 1;
    $oddCards = count($cards) % 2 === 1;

    // Stagger index: toolbar 0, hero 1, then each tile in reading order.
    $tile = 1;
@endphp

<div class="dash-bento mx-auto max-w-[1600px]">
    <x-page-header :title="$title" :subtitle="$subtitle" />

    <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
        {{-- Toolbar --}}
        <x-bento-tile :index="0" :accent="2" class="flex flex-col gap-4 p-4 sm:flex-row sm:items-end sm:justify-between md:col-span-12">
            <form method="get" action="{{ url()->current() }}" class="group/toolbar flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:min-w-[13rem]">
                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground" for="dash-sesi">
                    {{ __('Tapis mengikut sesi') }}
                </label>
                <div class="relative w-full sm:w-auto">
                    <span class="pointer-events-none absolute left-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground transition-colors group-focus-within/toolbar:text-primary" aria-hidden="true">
                        <i class="ri-filter-3-line text-lg leading-none"></i>
                    </span>
                    <span class="pointer-events-none absolute right-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground" aria-hidden="true">
                        <i class="ri-arrow-down-s-line text-lg leading-none"></i>
                    </span>
                    <select
                        id="dash-sesi"
                        name="sesi_id"
                        onchange="this.form.submit()"
                        class="h-11 w-full min-w-0 cursor-pointer appearance-none rounded-lg border border-input bg-background py-2 pl-10 pr-10 text-sm font-medium text-foreground shadow-sm ring-offset-background transition-all duration-200 hover:border-primary/30 hover:bg-muted/40 hover:shadow-md focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/40 focus:ring-offset-2 dark:hover:bg-muted/20 sm:min-w-[14rem]"
                    >
                        <option value="">{{ __('Semua sesi') }}</option>
                        @foreach ($allSesis as $sesi)
                            <option value="{{ $sesi->id }}" @selected($selectedSesiId === $sesi->id)>{{ $sesi->sesi }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="flex flex-wrap items-center gap-3">
                <span class="flex items-center gap-2 text-xs text-muted-foreground">
                    <span class="relative flex h-2 w-2" aria-hidden="true">
                        <span id="dash-live-ping" class="bento-ping absolute inline-flex h-full w-full rounded-full bg-emerald-500"></span>
                        <span id="dash-live-dot" class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <span id="dash-updated-at">{{ __('Belum disegerakkan') }}</span>
                </span>

                <label class="flex items-center gap-2 text-xs font-medium text-muted-foreground" for="dash-auto-refresh">
                    <span>{{ __('Auto-segar') }}</span>
                    <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                        <input id="dash-auto-refresh" type="checkbox" class="peer sr-only" checked />
                        <span class="{{ $toggleTrackClass }}"></span>
                    </span>
                </label>

                <button type="button" id="dash-reload" class="btn btn-outline btn-sm">
                    <i id="dash-reload-icon" class="ri-refresh-line"></i>
                    <span>{{ __('Muat semula') }}</span>
                </button>
            </div>
        </x-bento-tile>

        {{-- Hero tile --}}
        <x-bento-hero
            :hero="$layout['hero']"
            :stat="$payload['stats'][$layout['hero']['key']]"
            :sesi="$payload['status']['sesi'] ?? '—'"
            :index="$tile++"
            class="md:col-span-12 xl:col-span-5"
        />

        {{-- KPI tiles: stretch to match the hero height --}}
        <section
            class="grid auto-rows-fr grid-cols-1 gap-4 sm:grid-cols-2 md:col-span-12 xl:col-span-7"
            aria-label="{{ __('Ringkasan') }}"
            aria-live="polite"
            aria-atomic="false"
        >
            @foreach ($cards as $i => $card)
                <x-bento-stat
                    :stat="$payload['stats'][$card['key']]"
                    :label="$card['label']"
                    :icon="$card['icon']"
                    :stat-key="$card['key']"
                    :index="$tile++"
                    :accent="$i + 2"
                    :class="$oddCards && $i === $lastCard ? 'sm:col-span-2' : ''"
                />
            @endforeach
        </section>

        {{-- Charts --}}
        @foreach ($layout['charts'] as $chart)
            <x-bento-tile :index="$tile++" :accent="$loop->index + 1" class="{{ $chartSpans[$chart['size'] ?? 'md'] }}">
                <x-chart-panel bare :title="$chart['title']" :id="'dash-chart-'.$chart['key']" :chart-key="$chart['key']" />
            </x-bento-tile>
        @endforeach

        {{-- Tables --}}
        <div class="space-y-4 md:col-span-12 xl:col-span-8">
            @foreach ($layout['tables'] as $table)
                <x-bento-tile :index="$tile++" :accent="4">
                    <x-recent-table
                        bare
                        :title="$table['title']"
                        :columns="$table['columns']"
                        :rows="$payload['tables'][$table['key']]"
                        :table-key="$table['key']"
                    />
                </x-bento-tile>
            @endforeach
        </div>

        {{-- Status and actions --}}
        <div class="space-y-4 md:col-span-12 xl:col-span-4">
            <x-bento-tile :index="$tile++" :accent="3" class="p-4">
                <h3 class="mb-3 text-sm font-semibold text-card-foreground">{{ __('Status semasa') }}</h3>
                <dl class="divide-y divide-border text-sm">
                    @foreach ($layout['statusRows'] as $row)
                        <div class="flex items-start justify-between gap-4 py-2">
                            <dt class="text-muted-foreground">{{ $row['label'] }}</dt>
                            <dd class="bento-pill text-right font-medium text-card-foreground tabular-nums" data-status="{{ $row['key'] }}">{{ $payload['status'][$row['key']] ?? '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-bento-tile>

            <x-bento-tile :index="$tile++" :accent="5" class="p-4">
                <x-quick-actions bare :actions="$layout['actions']" />
            </x-bento-tile>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.__dashboard = {
            role: @json($role),
            pollUrl: @json($pollUrl),
            sesiId: @json($selectedSesiId),
            payload: @json($payload),
            labels: {
                noData: @json(__('Tiada data')),
                updated: @json(__('Dikemas kini')),
                offline: @json(__('Sambungan terputus')),
            },
        };
    </script>
@endpush
