@php
    $baseColumns = ['#', __('Nama'), __('PTJ')];
    $baseCentered = [0];
    if ($isJasamu) {
        $baseColumns[] = __('Tarikh Bersara');
        $baseCentered[] = count($baseColumns) - 1;
        $baseColumns[] = __('Jenis Persaraan');
        $baseColumns[] = __('Tempoh (thn)');
        $baseCentered[] = count($baseColumns) - 1;
    }

    // Attended sections get a Status (tepat masa / lewat) column; Tidak Hadir does not.
    $columnsFor = function (bool $withStatus) use ($baseColumns, $baseCentered): array {
        $columns = $baseColumns;
        $centered = $baseCentered;
        if ($withStatus) {
            $columns[] = __('Status');
            $centered[] = count($columns) - 1;
        }
        $columns[] = __('No. Kerusi / No. Sijil');
        $centered[] = count($columns) - 1;
        $columns[] = __('No. Meja');
        $centered[] = count($columns) - 1;

        return [$columns, array_fill_keys($centered, 'text-center')];
    };

    // Full class strings so Tailwind picks them up.
    $sectionTheme = [
        'rsvp' => [
            'section' => 'border-sky-200/70 bg-sky-50/50 dark:border-sky-900/60 dark:bg-sky-950/25',
            'title' => 'text-sky-900 dark:text-sky-100',
            'badge' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-100',
            'button' => 'bg-sky-600 hover:bg-sky-500',
            'pill' => 'bg-sky-500/10 text-sky-700 dark:text-sky-300',
            'card' => 'border-sky-200/70 bg-sky-50/60 dark:border-sky-900/60 dark:bg-sky-950/25',
            'cardLabel' => 'text-sky-700 dark:text-sky-200',
            'cardValue' => 'text-sky-800 dark:text-sky-100',
        ],
        'walkin' => [
            'section' => 'border-amber-200/70 bg-amber-50/50 dark:border-amber-900/60 dark:bg-amber-950/25',
            'title' => 'text-amber-900 dark:text-amber-100',
            'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-100',
            'button' => 'bg-amber-600 hover:bg-amber-500',
            'pill' => 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
            'card' => 'border-amber-200/70 bg-amber-50/60 dark:border-amber-900/60 dark:bg-amber-950/25',
            'cardLabel' => 'text-amber-700 dark:text-amber-200',
            'cardValue' => 'text-amber-800 dark:text-amber-100',
        ],
        'oku' => [
            'section' => 'border-violet-200/70 bg-violet-50/50 dark:border-violet-900/60 dark:bg-violet-950/25',
            'title' => 'text-violet-900 dark:text-violet-100',
            'badge' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/50 dark:text-violet-100',
            'button' => 'bg-violet-600 hover:bg-violet-500',
            'pill' => 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
            'card' => 'border-violet-200/70 bg-violet-50/60 dark:border-violet-900/60 dark:bg-violet-950/25',
            'cardLabel' => 'text-violet-700 dark:text-violet-200',
            'cardValue' => 'text-violet-800 dark:text-violet-100',
        ],
        \App\Services\Report\ReportPreviewTableService::SECTION_NOTATTEND => [
            'section' => 'border-slate-200/70 bg-slate-50/50 dark:border-slate-700/60 dark:bg-slate-950/25',
            'title' => 'text-slate-900 dark:text-slate-100',
            'badge' => 'bg-slate-200/80 text-slate-800 dark:bg-slate-800/50 dark:text-slate-100',
            'button' => 'bg-slate-700 hover:bg-slate-600 dark:bg-slate-600 dark:hover:bg-slate-500',
            'pill' => 'bg-slate-500/10 text-slate-700 dark:text-slate-300',
            'card' => 'border-slate-200/70 bg-slate-50/60 dark:border-slate-700/60 dark:bg-slate-950/25',
            'cardLabel' => 'text-slate-700 dark:text-slate-200',
            'cardValue' => 'text-slate-800 dark:text-slate-100',
        ],
    ];

    $attendedTotal = collect($sections)->filter(fn ($s) => $s['attended'])->keys()->sum(fn ($key) => $reportCounts[$key] ?? 0);
    $mcTotal = collect($mcGroups)->sum(fn ($g) => $g['rows']->count());
    $mcPrintConfig = [
        'previewUrl' => route('admin.report.mc-preview'),
        'downloadUrl' => route('admin.report.download', ['sesi_id' => $sesi->id, 'export_type' => 'mc']),
        'sesiId' => (int) $sesi->id,
        'snapshotTotal' => $mcTotal,
    ];
    $mcAnnouncedTotal = collect($mcGroups)->sum(fn ($g) => $g['rows']->filter(fn ($row) => isset($announcedIds[$row['pegawai']->id]))->count());
    $priorityShort = collect($sections)->filter(fn ($s) => $s['attended'])->pluck('short')->all();
@endphp

<x-dashboard-layout :title="__('Pratonton Laporan Kehadiran')" role="admin">
    <x-kawalan-shell>
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">
                    {{ __('Pratonton Laporan Kehadiran') }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ __('Sesi dipilih: :sesi', ['sesi' => $sesi->sesi]) }}
                    <span class="mx-1 text-border">·</span>
                    {{ __('Susunan panggilan:') }}
                    <span class="font-medium text-foreground">{{ implode(' → ', $priorityShort) }}</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.report.download', ['sesi_id' => $sesi->id, 'export_type' => 'all']) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-sm font-medium text-primary-foreground shadow-sm transition hover:bg-primary/90"
                >
                    <i class="ri-download-2-line"></i>
                    {{ __('Export PDF (Semua)') }}
                </a>
                <a
                    href="{{ route('admin.report.index') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-background px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted/60"
                >
                    <i class="ri-arrow-left-line"></i>
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <section class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl border border-border/70 bg-card px-4 py-3 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ __('Jumlah Hadir') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-foreground">{{ number_format($attendedTotal) }}</p>
            </div>
            @foreach ($sections as $key => $section)
                <div class="rounded-xl border px-4 py-3 shadow-sm {{ $sectionTheme[$key]['card'] }}">
                    <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider {{ $sectionTheme[$key]['cardLabel'] }}">
                        <i class="{{ $section['icon'] }} text-sm" aria-hidden="true"></i>
                        @if ($section['attended'])
                            <span class="tabular-nums">{{ $loop->iteration }}.</span>
                        @endif
                        {{ $section['short'] }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums {{ $sectionTheme[$key]['cardValue'] }}">{{ number_format($reportCounts[$key] ?? 0) }}</p>
                </div>
            @endforeach
        </section>

        <section class="mb-6 rounded-2xl border border-border/70 bg-card p-4 shadow-sm ring-1 ring-border/40 sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label for="report-search" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        {{ __('Carian pantas') }}
                    </label>
                    <div class="relative w-full sm:w-80">
                        <i class="ri-search-line pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i>
                        <input
                            id="report-search"
                            type="text"
                            placeholder="{{ __('Cari nama, no. KP atau PTJ...') }}"
                            class="h-10 w-full rounded-xl border border-input bg-background pl-9 pr-3 text-sm text-foreground shadow-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring"
                        >
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="{{ __('Tapis bahagian laporan') }}">
                    <button type="button" class="js-section-filter rounded-full bg-foreground px-3 py-1.5 text-xs font-semibold text-background" data-filter="all">
                        {{ __('Semua') }}
                    </button>
                    @foreach ($sections as $key => $section)
                        <button type="button" class="js-section-filter rounded-full px-3 py-1.5 text-xs font-semibold {{ $sectionTheme[$key]['pill'] }}" data-filter="{{ $key }}">
                            {{ $section['short'] }}
                        </button>
                    @endforeach
                    <button type="button" class="js-section-filter rounded-full bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary" data-filter="mc">
                        <i class="ri-mic-line" aria-hidden="true"></i>
                        {{ __('Senarai MC') }}
                    </button>
                </div>
            </div>
        </section>

        <div class="space-y-6">
            @foreach ($sections as $key => $section)
                @php([$sectionColumns, $sectionHeaderClasses] = $columnsFor($section['attended']))
                <section data-section="{{ $key }}" class="rounded-2xl border p-4 shadow-sm sm:p-5 {{ $sectionTheme[$key]['section'] }}">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div class="min-w-0">
                            <h2 class="flex items-center gap-2 text-base font-semibold {{ $sectionTheme[$key]['title'] }}">
                                <i class="{{ $section['icon'] }}" aria-hidden="true"></i>
                                @if ($section['attended'])
                                    <span class="tabular-nums">{{ $loop->iteration }}.</span>
                                @endif
                                {{ $section['label'] }}
                            </h2>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ $section['description'] }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $sectionTheme[$key]['badge'] }}">
                                {{ number_format($reportCounts[$key] ?? 0) }} {{ __('orang') }}
                            </span>
                            <a
                                href="{{ route('admin.report.download', ['sesi_id' => $sesi->id, 'export_type' => $key]) }}"
                                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition {{ $sectionTheme[$key]['button'] }}"
                            >
                                <i class="ri-download-2-line text-xs"></i>
                                {{ __('Export PDF') }}
                            </a>
                        </div>
                    </div>
                    <x-data-table
                        :table-id="'report-'.$key.'-table'"
                        :columns="$sectionColumns"
                        :column-header-classes="$sectionHeaderClasses"
                        class="shadow-sm ring-1 ring-border/30"
                    />
                </section>
            @endforeach

            {{-- Senarai MC: full call-out order in one continuous list (same order as Layar Utama). --}}
            <section
                data-section="mc"
                aria-labelledby="mc-title"
                class="rounded-2xl border border-primary/30 bg-card p-4 shadow-sm ring-1 ring-primary/10 sm:p-5"
                x-data="mcPrint(@js($mcPrintConfig))"
            >
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h2 id="mc-title" class="flex items-center gap-2 text-base font-semibold text-foreground">
                            <i class="ri-mic-line text-primary" aria-hidden="true"></i>
                            {{ __('Senarai Nama untuk MC') }}
                        </h2>
                        <p class="mt-0.5 max-w-2xl text-xs text-muted-foreground">
                            {{ __('Semua pegawai hadir mengikut susunan panggilan. Nombor Bil. sama dengan kedudukan slaid di Layar Utama. Klik nombor Bil. untuk cetak dari pegawai itu.') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                            {{ number_format($mcTotal) }} {{ __('orang') }}
                        </span>
                        @if ($mcAnnouncedTotal > 0)
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                <i class="ri-check-double-line" aria-hidden="true"></i>
                                {{ number_format($mcAnnouncedTotal) }} {{ __('diumumkan') }}
                            </span>
                        @endif
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            x-on:click="toggle()"
                            :aria-expanded="open.toString()"
                            aria-controls="mc-print-panel"
                        >
                            <i class="ri-printer-line text-sm" aria-hidden="true"></i>
                            {{ __('Cetak Senarai MC') }}
                            <i class="ri-arrow-down-s-line text-sm transition-transform" :class="open && 'rotate-180'" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                {{-- Print panel: whole list or a Bil range, optionally without officers already announced. --}}
                <div id="mc-print-panel" x-ref="panel" x-show="open" x-collapse x-cloak class="mb-4">
                    <div class="grid gap-4 rounded-xl border border-primary/25 bg-primary/[0.04] p-4 md:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                        <div class="space-y-4">
                            <fieldset>
                                <legend class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Skop cetakan') }}</legend>
                                <div class="grid grid-cols-2 gap-1 rounded-xl bg-muted/50 p-1">
                                    <label class="flex cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground has-[:checked]:bg-card has-[:checked]:text-foreground has-[:checked]:shadow-sm has-[:checked]:ring-1 has-[:checked]:ring-border/60 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring">
                                        <input type="radio" value="all" x-model="scope" class="sr-only">
                                        <i class="ri-list-check-2" aria-hidden="true"></i>{{ __('Semua senarai') }}
                                    </label>
                                    <label class="flex cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground has-[:checked]:bg-card has-[:checked]:text-foreground has-[:checked]:shadow-sm has-[:checked]:ring-1 has-[:checked]:ring-border/60 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring">
                                        <input type="radio" value="range" x-model="scope" class="sr-only">
                                        <i class="ri-split-cells-vertical" aria-hidden="true"></i>{{ __('Julat Bil.') }}
                                    </label>
                                </div>
                            </fieldset>

                            <div x-show="scope === 'range'" x-collapse class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ __('Dari Bil.') }}</span>
                                        <input
                                            type="number"
                                            min="1"
                                            inputmode="numeric"
                                            x-ref="fromInput"
                                            x-model.number="from"
                                            class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm tabular-nums text-foreground shadow-sm focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/40"
                                        >
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-muted-foreground">{{ __('Hingga Bil.') }}</span>
                                        <input
                                            type="number"
                                            min="1"
                                            inputmode="numeric"
                                            x-model.number="to"
                                            :disabled="toEnd"
                                            :placeholder="toEnd ? @js(__('Akhir senarai')) : ''"
                                            class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm tabular-nums text-foreground shadow-sm focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/40 disabled:cursor-not-allowed disabled:bg-muted/50"
                                        >
                                    </label>
                                </div>
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-foreground">
                                    <input type="checkbox" x-model="toEnd" class="h-4 w-4 rounded border-input text-primary focus:ring-ring">
                                    {{ __('Hingga akhir senarai') }}
                                </label>
                                <p x-show="clientError" x-text="clientError" class="flex items-start gap-1 text-xs font-medium text-red-600 dark:text-red-400" role="alert"></p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" x-on:click="jumpTo(1)" class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-foreground transition hover:bg-muted/70">
                                        <i class="ri-skip-back-line" aria-hidden="true"></i>{{ __('Dari awal') }}
                                    </button>
                                    <button
                                        type="button"
                                        x-show="data && data.next_start"
                                        x-on:click="jumpTo(data.next_start)"
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800 transition hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200"
                                    >
                                        <i class="ri-play-line" aria-hidden="true"></i>
                                        {{ __('Selepas pengumuman terakhir') }} ·
                                        <span class="tabular-nums" x-text="'Bil ' + (data ? data.next_start : '')"></span>
                                    </button>
                                    <button
                                        type="button"
                                        x-show="earlierUnannounced"
                                        x-on:click="jumpTo(data.first_unannounced)"
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-900 transition hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-100"
                                        title="{{ __('Ada pegawai belum diumumkan sebelum pengumuman terakhir (contohnya pegawai baharu yang tersisip).') }}"
                                    >
                                        <i class="ri-error-warning-line" aria-hidden="true"></i>
                                        {{ __('Belum diumumkan pertama') }} ·
                                        <span class="tabular-nums" x-text="'Bil ' + (data ? data.first_unannounced : '')"></span>
                                    </button>
                                    <span
                                        x-show="data && data.all_announced"
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800/60"
                                    >
                                        <i class="ri-check-double-line" aria-hidden="true"></i>{{ __('Semua telah diumumkan') }}
                                    </span>
                                </div>
                            </div>

                            <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-border/70 bg-background px-3 py-2.5 text-sm text-foreground">
                                <input type="checkbox" x-model="skipAnnounced" class="mt-0.5 h-4 w-4 rounded border-input text-primary focus:ring-ring">
                                <span>
                                    {{ __('Kecualikan pegawai yang telah diumumkan di Layar Utama') }}
                                    <span class="tabular-nums text-muted-foreground" x-text="'(' + (data ? data.announced_count : {{ $mcAnnouncedTotal }}) + ')'"></span>
                                    <span class="mt-0.5 block text-xs text-muted-foreground">{{ __('Mulakan julat lebih awal dengan pilihan ini supaya pegawai baharu yang tersisip sebelum Bil. mula tidak tertinggal.') }}</span>
                                </span>
                            </label>
                        </div>

                        {{-- Live summary --}}
                        <div class="flex min-h-[19rem] flex-col rounded-xl border border-border/70 bg-card p-4 shadow-sm" aria-live="polite">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Ringkasan cetakan') }}</h3>
                                <i class="ri-loader-4-line animate-spin text-primary" x-show="loading" aria-hidden="true"></i>
                            </div>

                            {{-- Layar Utama progress --}}
                            <div x-show="data && data.total > 0" class="mt-3">
                                <div class="flex items-center justify-between text-[11px] text-muted-foreground">
                                    <span>{{ __('Diumumkan di Layar Utama') }}</span>
                                    <span class="font-medium tabular-nums text-foreground" x-text="data ? data.announced_count + ' / ' + data.total : ''"></span>
                                </div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-muted" role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="announcedPercent">
                                    <span class="block h-full rounded-full bg-emerald-500 transition-[width] duration-500" :style="'width: ' + announcedPercent + '%'"></span>
                                </div>
                            </div>

                            {{-- First load --}}
                            <div x-show="state === 'loading'" class="mt-4 space-y-2.5 animate-pulse" aria-hidden="true">
                                <div class="h-7 w-28 rounded-md bg-muted"></div>
                                <div class="h-3 w-52 rounded bg-muted"></div>
                                <div class="h-14 rounded-lg bg-muted/70"></div>
                            </div>

                            {{-- Range not complete yet (the field message says what to fix) --}}
                            <div x-show="state === 'invalid'" class="mt-4 flex flex-1 flex-col items-center justify-center gap-1.5 rounded-lg border border-dashed border-border px-4 py-6 text-center">
                                <i class="ri-edit-2-line text-2xl text-muted-foreground" aria-hidden="true"></i>
                                <p class="text-sm font-medium text-foreground">{{ __('Lengkapkan julat Bil.') }}</p>
                                <p class="text-xs text-muted-foreground">{{ __('Ringkasan dipaparkan sebaik julat sah.') }}</p>
                            </div>

                            {{-- Nothing to print: say why and offer the next step --}}
                            <template x-if="emptyState">
                                <div class="mt-4 flex flex-1 flex-col items-center justify-center gap-1.5 rounded-lg px-4 py-6 text-center" :class="tones[emptyState.tone].panel">
                                    <span class="mb-1 flex h-11 w-11 items-center justify-center rounded-full" :class="tones[emptyState.tone].icon">
                                        <i :class="emptyState.icon" class="text-xl" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-sm font-semibold text-foreground" x-text="emptyState.title"></p>
                                    <p class="max-w-xs text-xs leading-relaxed text-muted-foreground" x-text="emptyState.text"></p>
                                    <div class="mt-2 flex flex-wrap justify-center gap-2" x-show="emptyState.actions.length">
                                        <template x-for="action in emptyState.actions" :key="action.label">
                                            <button
                                                type="button"
                                                x-on:click="action.run()"
                                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                :class="action.primary ? 'bg-primary text-primary-foreground hover:bg-primary/90' : 'border border-border bg-background text-foreground hover:bg-muted/60'"
                                            >
                                                <i :class="action.icon" aria-hidden="true"></i>
                                                <span x-text="action.label"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <template x-if="state === 'ok'">
                                <div class="mt-3 space-y-2.5 text-sm">
                                    <p class="flex flex-wrap items-baseline gap-x-2">
                                        <span class="text-2xl font-bold tabular-nums text-foreground" x-text="data.count.toLocaleString()"></span>
                                        <span class="text-muted-foreground">{{ __('nama akan dicetak') }}</span>
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        <span class="font-medium text-foreground" x-text="'Bil ' + data.from + ' → ' + data.to"></span>
                                        <span x-text="' · ' + @js(__('daripada')) + ' ' + data.total"></span>
                                        <span x-show="data.pages_estimate > 0" x-text="' · ~' + data.pages_estimate + ' ' + @js(__('muka surat'))"></span>
                                    </p>
                                    <dl class="space-y-1 rounded-lg bg-muted/40 px-3 py-2 text-xs" x-show="data.first">
                                        <div class="flex gap-2">
                                            <dt class="w-12 shrink-0 text-muted-foreground">{{ __('Mula') }}</dt>
                                            <dd class="min-w-0 truncate font-medium text-foreground" x-text="data.first ? 'Bil ' + data.first.bil + ' — ' + data.first.nama : ''"></dd>
                                        </div>
                                        <div class="flex gap-2">
                                            <dt class="w-12 shrink-0 text-muted-foreground">{{ __('Akhir') }}</dt>
                                            <dd class="min-w-0 truncate font-medium text-foreground" x-text="data.last ? 'Bil ' + data.last.bil + ' — ' + data.last.nama : ''"></dd>
                                        </div>
                                    </dl>
                                    <p class="text-xs text-emerald-700 dark:text-emerald-300" x-show="skipAnnounced && data.skipped > 0" x-text="data.skipped + ' ' + @js(__('telah diumumkan dilangkau'))"></p>
                                    <p class="text-xs text-muted-foreground" x-show="data.last_announced">
                                        <i class="ri-slideshow-3-line" aria-hidden="true"></i>
                                        {{ __('Pengumuman terakhir:') }}
                                        <span class="font-medium text-foreground" x-text="data.last_announced ? 'Bil ' + data.last_announced.bil + ' — ' + data.last_announced.nama : ''"></span>
                                    </p>
                                </div>
                            </template>

                            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:bg-amber-950/40 dark:text-amber-100" x-show="data && data.total !== snapshotTotal">
                                <i class="ri-refresh-line" aria-hidden="true"></i>
                                <span x-text="data ? @js(__('Senarai terkini ada')) + ' ' + data.total + ' ' + @js(__('nama (halaman:')) + ' ' + snapshotTotal + ').' : ''"></span>
                                <button type="button" class="font-semibold underline underline-offset-2" x-on:click="window.location.reload()">{{ __('Muat semula') }}</button>
                                {{ __('untuk kemas kini senarai di bawah. PDF sentiasa guna data terkini.') }}
                            </p>

                            <div class="mt-auto flex items-center justify-end gap-2 pt-4">
                                <a
                                    :href="canDownload ? downloadHref : null"
                                    :aria-disabled="(!canDownload).toString()"
                                    :class="canDownload ? 'hover:bg-primary/90' : 'pointer-events-none opacity-50'"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition"
                                >
                                    <i class="ri-download-2-line" aria-hidden="true"></i>
                                    {{ __('Muat turun PDF') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($mcGroups === [])
                    <p class="rounded-xl border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">
                        {{ __('Tiada pegawai hadir untuk sesi ini.') }}
                    </p>
                @else
                    <div class="max-h-[36rem] overflow-auto rounded-xl ring-1 ring-border/40">
                        <table id="mc-table" class="w-full min-w-[40rem] border-collapse text-sm" x-on:click="onTableClick($event)">
                            <thead class="sticky top-0 z-[1] bg-muted text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th scope="col" class="w-20 px-3 py-2 text-center">{{ __('Bil.') }}</th>
                                    <th scope="col" class="px-3 py-2">{{ __('Nama') }}</th>
                                    <th scope="col" class="px-3 py-2">{{ __('PTJ') }}</th>
                                    <th scope="col" class="w-24 px-3 py-2 text-center">{{ __('Kerusi') }}</th>
                                    <th scope="col" class="w-20 px-3 py-2 text-center">{{ __('Meja') }}</th>
                                    <th scope="col" class="w-28 px-3 py-2 text-center">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            @foreach ($mcGroups as $group)
                                <tbody class="js-mc-group">
                                    <tr class="bg-muted/50">
                                        <th scope="colgroup" colspan="6" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide {{ $sectionTheme[$group['group']]['title'] }}">
                                            {{ $group['label'] }}
                                            <span class="ml-1 font-normal normal-case text-muted-foreground">({{ $group['rows']->count() }} {{ __('orang') }})</span>
                                        </th>
                                    </tr>
                                    @foreach ($group['rows'] as $row)
                                        @php($pegawai = $row['pegawai'])
                                        @php($announced = isset($announcedIds[$pegawai->id]))
                                        <tr
                                            class="js-mc-row border-t border-border/40 transition-colors {{ $announced ? 'bg-muted/20 text-muted-foreground' : '' }}"
                                            data-bil="{{ $row['bil'] }}"
                                            data-search="{{ mb_strtolower($pegawai->nama.' '.$pegawai->no_kp.' '.($pegawai->ptj?->nama_ptj ?? ''), 'UTF-8') }}"
                                        >
                                            <td class="px-2 py-1.5 text-center">
                                                <button
                                                    type="button"
                                                    data-print-from="{{ $row['bil'] }}"
                                                    class="group/bil inline-flex items-center gap-1 rounded-md px-2 py-1 tabular-nums text-muted-foreground transition hover:bg-primary/10 hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                    title="{{ __('Cetak dari sini (Bil :bil)', ['bil' => $row['bil']]) }}"
                                                    aria-label="{{ __('Cetak dari Bil :bil', ['bil' => $row['bil']]) }}"
                                                >
                                                    <i class="ri-printer-line text-xs opacity-0 transition group-hover/bil:opacity-100 group-focus-visible/bil:opacity-100" aria-hidden="true"></i>
                                                    {{ $row['bil'] }}
                                                </button>
                                            </td>
                                            <td class="px-3 py-2 font-semibold {{ $announced ? '' : 'text-foreground' }}">
                                                {{ $pegawai->nama }}
                                                @if ($announced)
                                                    <span class="ml-1 inline-flex items-center gap-0.5 rounded-full bg-emerald-100 px-1.5 py-0.5 align-middle text-[0.65rem] font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                                        <i class="ri-check-line" aria-hidden="true"></i>{{ __('Diumumkan') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-muted-foreground">{{ $pegawai->ptj?->nama_ptj ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center tabular-nums">{{ $pegawai->no_kerusi ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center tabular-nums">{{ $pegawai->no_meja ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                @if ($pegawai->is_late)
                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ __('Lewat') }}</span>
                                                @else
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ __('Tepat masa') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                    <p id="mc-empty" class="mt-3 hidden text-center text-sm text-muted-foreground">{{ __('Tiada padanan carian.') }}</p>
                @endif
            </section>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('alpine:init', function() {
                    // MC print panel: whole list or Bil range (+ skip announced), live summary from mc-preview.
                    window.Alpine.data('mcPrint', function(config) {
                        return {
                            open: false,
                            scope: 'all',
                            from: 1,
                            to: '',
                            toEnd: true,
                            skipAnnounced: false,
                            loading: false,
                            data: null,
                            serverError: null,
                            snapshotTotal: config.snapshotTotal,
                            timer: null,
                            controller: null,

                            init() {
                                ['scope', 'from', 'to', 'toEnd', 'skipAnnounced'].forEach((key) => {
                                    this.$watch(key, () => this.queueRefresh());
                                });
                                this.$watch('from', () => this.highlightStart());
                                this.$watch('scope', () => this.highlightStart());
                            },

                            toggle() {
                                this.open = !this.open;
                                if (this.open && this.data === null) {
                                    this.refresh();
                                }
                            },

                            startFrom(bil) {
                                this.scope = 'range';
                                this.from = bil;
                                this.toEnd = true;
                                this.open = true;
                                this.$nextTick(() => {
                                    this.$refs.panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    this.$refs.fromInput?.focus({ preventScroll: true });
                                });
                            },

                            onTableClick(event) {
                                const button = event.target.closest('[data-print-from]');
                                if (button) {
                                    this.startFrom(parseInt(button.dataset.printFrom, 10));
                                }
                            },

                            jumpTo(bil) {
                                if (!bil) {
                                    return;
                                }
                                this.scope = 'range';
                                this.from = bil;
                                this.toEnd = true;
                            },

                            printFull() {
                                this.scope = 'all';
                                this.skipAnnounced = false;
                            },

                            // Full class strings so Tailwind keeps them.
                            tones: {
                                success: {
                                    panel: 'bg-emerald-50/70 dark:bg-emerald-950/30',
                                    icon: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
                                },
                                warning: {
                                    panel: 'bg-amber-50/70 dark:bg-amber-950/30',
                                    icon: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                                },
                                danger: {
                                    panel: 'bg-red-50/70 dark:bg-red-950/30',
                                    icon: 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                },
                                neutral: {
                                    panel: 'bg-muted/40',
                                    icon: 'bg-muted text-muted-foreground',
                                },
                            },

                            get announcedPercent() {
                                if (!this.data || this.data.total === 0) {
                                    return 0;
                                }
                                return Math.round((this.data.announced_count / this.data.total) * 100);
                            },

                            // An officer before the last announced one is still pending (e.g. inserted late).
                            get earlierUnannounced() {
                                if (!this.data || !this.data.first_unannounced || !this.data.last_announced) {
                                    return false;
                                }
                                return this.data.first_unannounced < this.data.last_announced.bil;
                            },

                            highlightStart() {
                                const start = this.scope === 'range' && Number.isInteger(this.from) ? this.from : null;
                                this.$root.querySelectorAll('.js-mc-row').forEach((row) => {
                                    const active = start !== null && parseInt(row.dataset.bil, 10) === start;
                                    row.classList.toggle('bg-primary/10', active);
                                    row.classList.toggle('ring-1', active);
                                    row.classList.toggle('ring-inset', active);
                                    row.classList.toggle('ring-primary/40', active);
                                });
                            },

                            get clientError() {
                                if (this.scope !== 'range') {
                                    return null;
                                }
                                if (!Number.isInteger(this.from) || this.from < 1) {
                                    return @json(__('Masukkan Bil. mula yang sah (1 atau lebih).'));
                                }
                                if (!this.toEnd) {
                                    if (!Number.isInteger(this.to) || this.to < 1) {
                                        return @json(__('Masukkan Bil. akhir, atau pilih "Hingga akhir senarai".'));
                                    }
                                    if (this.to < this.from) {
                                        return @json(__('Bil. akhir mesti sama atau lebih besar daripada Bil. mula.'));
                                    }
                                }
                                return null;
                            },

                            get state() {
                                if (this.clientError) {
                                    return 'invalid';
                                }
                                if (this.serverError) {
                                    return 'error';
                                }
                                if (!this.data) {
                                    return 'loading';
                                }
                                if (this.data.total === 0) {
                                    return 'no-attendance';
                                }
                                if (this.scope === 'range' && this.from > this.data.total) {
                                    return 'beyond';
                                }
                                if (this.data.count === 0) {
                                    if (!this.skipAnnounced) {
                                        return 'empty';
                                    }
                                    return this.data.all_announced ? 'all-announced' : 'range-announced';
                                }
                                return 'ok';
                            },

                            // Why nothing can be printed, and the next step for the admin.
                            get emptyState() {
                                const d = this.data;
                                switch (this.state) {
                                    case 'error':
                                        return {
                                            tone: 'danger',
                                            icon: 'ri-error-warning-line',
                                            title: @json(__('Ringkasan gagal dimuatkan')),
                                            text: this.serverError,
                                            actions: [{ label: @json(__('Cuba lagi')), icon: 'ri-refresh-line', primary: true, run: () => this.refresh() }],
                                        };
                                    case 'no-attendance':
                                        return {
                                            tone: 'neutral',
                                            icon: 'ri-user-search-line',
                                            title: @json(__('Belum ada pegawai hadir')),
                                            text: @json(__('Senarai MC akan terisi apabila pegawai disahkan hadir untuk sesi ini.')),
                                            actions: [],
                                        };
                                    case 'beyond':
                                        return {
                                            tone: 'warning',
                                            icon: 'ri-arrow-go-back-line',
                                            title: @json(__('Bil. mula melebihi senarai')),
                                            text: @json(__('Senarai terkini hanya ada :n nama.')).replace(':n', d.total),
                                            actions: [
                                                { label: @json(__('Mula dari Bil. terakhir')) + ' (' + d.total + ')', icon: 'ri-skip-forward-line', primary: true, run: () => this.jumpTo(d.total) },
                                                { label: @json(__('Dari awal')), icon: 'ri-skip-back-line', primary: false, run: () => this.jumpTo(1) },
                                            ],
                                        };
                                    case 'all-announced':
                                        return {
                                            tone: 'success',
                                            icon: 'ri-check-double-line',
                                            title: @json(__('Semua pegawai telah diumumkan')),
                                            text: @json(__(':done daripada :total nama sudah dipaparkan di Layar Utama. Tiada nama baharu untuk dicetak.'))
                                                .replace(':done', d.announced_count).replace(':total', d.total),
                                            actions: [
                                                { label: @json(__('Cetak semula senarai penuh')), icon: 'ri-printer-line', primary: true, run: () => this.printFull() },
                                                { label: @json(__('Sertakan yang telah diumumkan')), icon: 'ri-eye-line', primary: false, run: () => { this.skipAnnounced = false; } },
                                            ],
                                        };
                                    case 'range-announced': {
                                        const actions = [];
                                        if (d.first_unannounced) {
                                            actions.push({ label: @json(__('Mula dari Bil.')) + ' ' + d.first_unannounced, icon: 'ri-play-line', primary: true, run: () => this.jumpTo(d.first_unannounced) });
                                        }
                                        actions.push({ label: @json(__('Sertakan yang telah diumumkan')), icon: 'ri-eye-line', primary: actions.length === 0, run: () => { this.skipAnnounced = false; } });

                                        return {
                                            tone: 'success',
                                            icon: 'ri-check-line',
                                            title: @json(__('Semua dalam julat ini telah diumumkan')),
                                            text: d.first_unannounced
                                                ? @json(__(':n nama belum diumumkan, bermula Bil. :bil.')).replace(':n', d.remaining).replace(':bil', d.first_unannounced)
                                                : @json(__('Tiada nama belum diumumkan.')),
                                            actions: actions,
                                        };
                                    }
                                    case 'empty':
                                        return {
                                            tone: 'neutral',
                                            icon: 'ri-inbox-line',
                                            title: @json(__('Tiada nama dalam julat ini')),
                                            text: @json(__('Ubah julat Bil. untuk memilih pegawai.')),
                                            actions: [{ label: @json(__('Dari awal')), icon: 'ri-skip-back-line', primary: true, run: () => this.jumpTo(1) }],
                                        };
                                    default:
                                        return null;
                                }
                            },

                            get params() {
                                const params = new URLSearchParams({ sesi_id: config.sesiId });
                                if (this.scope === 'range') {
                                    params.set('from', this.from);
                                    if (!this.toEnd) {
                                        params.set('to', this.to);
                                    }
                                }
                                if (this.skipAnnounced) {
                                    params.set('skip_announced', '1');
                                }
                                return params;
                            },

                            get canDownload() {
                                return !this.loading && this.state === 'ok';
                            },

                            get downloadHref() {
                                const params = this.params;
                                params.delete('sesi_id');
                                const query = params.toString();
                                return config.downloadUrl + (query ? '&' + query : '');
                            },

                            queueRefresh() {
                                clearTimeout(this.timer);
                                if (!this.open) {
                                    return;
                                }
                                this.timer = setTimeout(() => this.refresh(), 300);
                            },

                            refresh() {
                                if (this.clientError) {
                                    return;
                                }
                                this.controller?.abort();
                                const controller = new AbortController();
                                this.controller = controller;
                                this.loading = true;

                                fetch(config.previewUrl + '?' + this.params.toString(), {
                                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                        signal: controller.signal,
                                    })
                                    .then((response) => response.json().then((body) => ({ ok: response.ok, body })))
                                    .then(({ ok, body }) => {
                                        if (!ok) {
                                            this.serverError = body.errors
                                                ? Object.values(body.errors).flat().join(' ')
                                                : @json(__('Gagal memuatkan ringkasan. Sila cuba lagi.'));
                                            return;
                                        }
                                        this.serverError = null;
                                        this.data = body;
                                    })
                                    .catch((error) => {
                                        if (error.name !== 'AbortError') {
                                            this.serverError = @json(__('Gagal memuatkan ringkasan. Sila cuba lagi.'));
                                        }
                                    })
                                    .finally(() => {
                                        if (this.controller === controller) {
                                            this.loading = false;
                                        }
                                    });
                            },
                        };
                    });
                });
            </script>
            <script>
                $(function() {
                    const datatableUrl = @json(route('admin.report.datatable'));
                    const sesiId = {{ (int) $sesi->id }};
                    const isJasamu = @json($isJasamu);
                    const reportSections = @json(collect($sections)->map(fn ($s) => ['attended' => $s['attended']]));

                    const statusPill = function(data) {
                        const late = data === @json(__('Lewat'));
                        const cls = late
                            ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'
                            : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200';

                        return '<span class="rounded-full px-2 py-0.5 text-xs font-semibold ' + cls + '">' + data + '</span>';
                    };

                    const columnsFor = function(withStatus) {
                        return [{
                                data: null,
                                name: 'index',
                                orderable: false,
                                searchable: false,
                                className: 'text-center tabular-nums text-muted-foreground',
                                render: function(data, type, row, meta) {
                                    return meta.row + meta.settings._iDisplayStart + 1;
                                },
                            },
                            {
                                data: 'nama',
                                name: 'nama',
                            },
                            {
                                data: 'ptj_name',
                                name: 'ptj_name',
                            },
                            ...(isJasamu ? [
                                {
                                    data: 'tarikh_bersara',
                                    name: 'tarikh_bersara',
                                    className: 'text-center tabular-nums',
                                },
                                {
                                    data: 'bersara_name',
                                    name: 'bersara_name',
                                },
                                {
                                    data: 'tempoh_berkhidmat',
                                    name: 'tempoh_berkhidmat',
                                    className: 'text-center tabular-nums',
                                },
                            ] : []),
                            ...(withStatus ? [{
                                data: 'status_label',
                                name: 'status_label',
                                searchable: false,
                                className: 'text-center',
                                render: statusPill,
                            }] : []),
                            {
                                data: 'no_kerusi',
                                name: 'no_kerusi',
                                className: 'text-center tabular-nums',
                            },
                            {
                                data: 'no_meja',
                                name: 'no_meja',
                                className: 'text-center tabular-nums',
                            },
                        ];
                    };

                    const tables = Object.keys(reportSections).map(function(key) {
                        return $('#report-' + key + '-table').DataTable({
                            ...(window.kawalanDataTableDefaults || {}),
                            processing: true,
                            serverSide: true,
                            ordering: false,
                            pageLength: 5,
                            lengthMenu: [
                                [5, 10, 25],
                                [5, 10, 25],
                            ],
                            ajax: {
                                url: datatableUrl,
                                data: function(d) {
                                    d.sesi_id = sesiId;
                                    d.section = key;
                                },
                            },
                            columns: columnsFor(reportSections[key].attended),
                        });
                    });

                    const searchInput = document.getElementById('report-search');
                    const filterButtons = Array.from(document.querySelectorAll('.js-section-filter'));
                    const sections = Array.from(document.querySelectorAll('[data-section]'));
                    const mcGroups = Array.from(document.querySelectorAll('.js-mc-group'));
                    const mcEmpty = document.getElementById('mc-empty');

                    const filterMcList = function(value) {
                        const needle = value.trim().toLowerCase();
                        let visible = 0;

                        mcGroups.forEach(function(group) {
                            let groupVisible = 0;
                            group.querySelectorAll('.js-mc-row').forEach(function(row) {
                                const match = needle === '' || row.dataset.search.includes(needle);
                                row.classList.toggle('hidden', !match);
                                groupVisible += match ? 1 : 0;
                            });
                            group.classList.toggle('hidden', groupVisible === 0);
                            visible += groupVisible;
                        });

                        if (mcEmpty) {
                            mcEmpty.classList.toggle('hidden', visible > 0 || mcGroups.length === 0);
                        }
                    };

                    if (searchInput) {
                        searchInput.addEventListener('input', function() {
                            const v = searchInput.value;
                            tables.forEach(function(t) {
                                t.search(v).draw();
                            });
                            filterMcList(v);
                        });
                    }

                    const applySectionFilter = function(target) {
                        sections.forEach(function(section) {
                            const shouldShow = target === 'all' || section.dataset.section === target;
                            section.classList.toggle('hidden', !shouldShow);
                        });

                        filterButtons.forEach(function(button) {
                            const active = button.dataset.filter === target;
                            button.classList.toggle('ring-2', active);
                            button.classList.toggle('ring-offset-2', active);
                            button.classList.toggle('ring-ring', active);
                            button.setAttribute('aria-pressed', active ? 'true' : 'false');
                        });

                        requestAnimationFrame(function() {
                            tables.forEach(function(t) {
                                t.columns.adjust();
                            });
                        });
                    };

                    filterButtons.forEach(function(button) {
                        button.addEventListener('click', function() {
                            applySectionFilter(button.dataset.filter || 'all');
                        });
                    });

                    applySectionFilter('all');
                });
            </script>
        @endpush
    </x-kawalan-shell>
</x-dashboard-layout>
