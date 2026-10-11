@php
    $inputClass =
        'min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-base tabular-nums text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0 sm:text-sm';
    $fieldShell =
        'flex h-12 overflow-hidden rounded-xl border border-input/90 bg-background shadow-sm ring-1 ring-border/30 transition-[box-shadow,ring-color,border-color] focus-within:border-ring focus-within:shadow-md focus-within:ring-2 focus-within:ring-ring dark:bg-background/80';
    $toggleTrackClass =
        'peer h-5 w-9 rounded-full bg-muted ring-1 ring-border/60 transition-all after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-background after:shadow-sm after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-ring peer-focus-visible:ring-offset-2';
    $eventModeIcons = ['jasamu' => 'ri-hand-heart-line', 'apc' => 'ri-award-line'];
    $eventModeShort = ['jasamu' => 'Jasamu', 'apc' => 'APC'];
    $kpiCard =
        'flex flex-col rounded-2xl border border-border/70 bg-card p-4 shadow-sm';
@endphp

<x-dashboard-layout :title="__('Sesi Majlis')" role="admin" :fill-height="true">
    <x-kawalan-shell fill>
        <div class="shrink-0 [&>div]:!mb-0">
            <x-crud-header
                :title="__('Sesi Majlis')"
                :description="__('Urus sesi majlis: aktif, lewat, no. kerusi lewat bermula, dan offset kerusi untuk pengiraan no. meja.')"
                :create-label="__('Tambah sesi')"
            />
        </div>

        {{-- KPI row --}}
        <div class="grid shrink-0 gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_minmax(0,1.4fr)]">
            {{-- Sesi aktif --}}
            <section class="{{ $kpiCard }}" aria-labelledby="kpi-active-title">
                <header class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400" aria-hidden="true">
                        <i class="ri-flashlight-line text-lg"></i>
                    </span>
                    <h3 id="kpi-active-title" class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Sesi aktif') }}</h3>
                </header>
                <div class="mt-3 flex min-h-8 flex-wrap items-center gap-1.5" data-stat="active-names" aria-live="polite">
                    <span class="h-7 w-20 animate-pulse rounded-full bg-muted"></span>
                </div>
                <p class="mt-2 hidden items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400" data-stat="active-warning">
                    <i class="ri-error-warning-line"></i>
                    <span>{{ __('Lebih daripada satu sesi aktif') }}</span>
                </p>
                <p class="mt-auto pt-3 text-xs text-muted-foreground" data-stat="active-hint">{{ __('Sesi yang sedang menerima kehadiran.') }}</p>
            </section>

            {{-- Sesi lewat --}}
            <section class="{{ $kpiCard }}" aria-labelledby="kpi-late-title">
                <header class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400" aria-hidden="true">
                        <i class="ri-time-line text-lg"></i>
                    </span>
                    <h3 id="kpi-late-title" class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Sesi lewat') }}</h3>
                </header>
                <div class="mt-3 flex min-h-8 flex-wrap items-center gap-1.5" data-stat="late-names" aria-live="polite">
                    <span class="h-7 w-20 animate-pulse rounded-full bg-muted"></span>
                </div>
                <p class="mt-auto pt-3 text-xs text-muted-foreground">{{ __('Pegawai yang hadir lewat mendapat nombor panggilan lewat.') }}</p>
            </section>

            {{-- Jumlah sesi --}}
            <section class="{{ $kpiCard }}" aria-labelledby="kpi-total-title">
                <header class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary" aria-hidden="true">
                        <i class="ri-stack-line text-lg"></i>
                    </span>
                    <h3 id="kpi-total-title" class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Jumlah sesi') }}</h3>
                </header>
                <p class="mt-3 flex min-h-8 items-baseline gap-1.5">
                    <span class="animate-pulse text-3xl font-bold leading-none tabular-nums text-foreground" data-stat="total-label">…</span>
                    <span class="text-sm text-muted-foreground">{{ __('sesi') }}</span>
                </p>
                <div class="mt-auto pt-3">
                    <div class="flex h-1.5 overflow-hidden rounded-full bg-muted" role="img" data-stat="total-bar" aria-label="{{ __('Pecahan sesi pagi dan petang') }}">
                        <span class="h-full bg-amber-400 transition-[width] duration-300" style="width: 0%" data-stat="bar-pagi"></span>
                        <span class="h-full bg-indigo-500 transition-[width] duration-300" style="width: 0%" data-stat="bar-petang"></span>
                    </div>
                    <p class="mt-1.5 flex items-center gap-3 text-xs text-muted-foreground">
                        <span class="inline-flex items-center gap-1"><i class="ri-sun-line text-amber-500" aria-hidden="true"></i>{{ __('Pagi') }} <b class="tabular-nums text-foreground" data-stat="count-pagi">–</b></span>
                        <span class="inline-flex items-center gap-1"><i class="ri-moon-line text-indigo-500" aria-hidden="true"></i>{{ __('Petang') }} <b class="tabular-nums text-foreground" data-stat="count-petang">–</b></span>
                    </p>
                </div>
            </section>

            {{-- Mod acara --}}
            <section class="{{ $kpiCard }}" aria-labelledby="kpi-mode-title">
                <header class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary" aria-hidden="true">
                            <i class="ri-toggle-line text-lg"></i>
                        </span>
                        <h3 id="kpi-mode-title" class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ __('Mod acara') }}</h3>
                    </div>
                    <i id="event-mode-spinner" class="ri-loader-4-line hidden shrink-0 animate-spin text-primary" aria-hidden="true"></i>
                </header>
                <p class="mt-3 min-h-8 text-sm font-semibold leading-snug text-foreground" data-stat="mode-label" aria-live="polite">
                    {{ $eventModeOptions[$eventMode] ?? '-' }}
                </p>

                <fieldset id="event-mode-choice" class="mt-auto grid grid-cols-2 gap-1 rounded-xl bg-muted/40 p-1">
                    <legend class="sr-only">{{ __('Mod Acara') }}</legend>
                    @foreach ($eventModeOptions as $value => $label)
                        <label
                            class="flex cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-muted-foreground transition hover:text-foreground has-[:checked]:bg-card has-[:checked]:text-foreground has-[:checked]:shadow-sm has-[:checked]:ring-1 has-[:checked]:ring-border/60 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                            title="{{ $label }}"
                        >
                            <input
                                type="radio"
                                name="event_mode"
                                value="{{ $value }}"
                                class="js-event-mode sr-only"
                                @checked($eventMode === $value)
                            />
                            <i class="{{ $eventModeIcons[$value] ?? 'ri-toggle-line' }} shrink-0" aria-hidden="true"></i>
                            <span class="truncate">{{ $eventModeShort[$value] ?? $label }}</span>
                        </label>
                    @endforeach
                </fieldset>
                <p class="mt-2 text-xs text-muted-foreground">{{ __('Menukar mod akan mempengaruhi semua pengguna.') }}</p>
            </section>
        </div>

        {{-- Senarai sesi table (full width) --}}
        <div class="flex min-h-0 h-[36rem] flex-col lg:h-auto lg:flex-1">
            <div class="mb-2 flex shrink-0 items-center justify-between gap-2 px-1">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-foreground">{{ __('Senarai sesi') }}</h2>
                    <span class="rounded-full bg-muted/40 px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                        <span class="animate-pulse" data-stat="total-count">…</span> {{ __('sesi') }}
                    </span>
                </div>

                <button
                    type="button"
                    id="guide-trigger"
                    class="btn btn-ghost btn-sm relative"
                    data-modal-target="guide-modal"
                    aria-haspopup="dialog"
                    aria-label="{{ __('Panduan sesi majlis') }}"
                    title="{{ __('Panduan sesi majlis') }}"
                >
                    <i class="ri-question-line"></i>
                    <span class="hidden sm:inline">{{ __('Panduan') }}</span>
                    <span
                        id="guide-hint-dot"
                        class="absolute -right-0.5 -top-0.5 hidden h-2 w-2 rounded-full bg-primary"
                        aria-hidden="true"
                    >
                        <span class="absolute inset-0 h-full w-full animate-ping rounded-full bg-primary"></span>
                    </span>
                </button>
            </div>

            <x-data-table
                table-id="sesi-majlis-table"
                class="kawalan-dt-card--fill min-h-0 flex-1"
                :columns="['ID', __('Sesi'), __('Aktif'), __('Lewat'), __('No. kerusi lewat'), __('Offset kerusi'), __('Jenis kehadiran'), __('Dicipta'), __('Tindakan')]"
            />
        </div>

        <div class="modal-backdrop"></div>

        <x-crud-modal modal-id="create-modal" size="modal-lg" :title="__('Tambah sesi majlis')">
            <form id="create-sesi-majlis-form" class="space-y-5">
                @csrf
                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="create-sesi">
                            {{ __('Sesi') }}
                        </label>
                        <p class="text-xs text-muted-foreground">
                            {{ __('Nama pengenalan untuk sesi ini, contohnya "Pagi" atau "Petang".') }}
                        </p>
                    </div>
                    <div class="{{ $fieldShell }}">
                        <span
                            class="flex w-12 shrink-0 items-center justify-center border-r border-border/80 bg-muted/35 text-muted-foreground dark:bg-muted/25"
                            aria-hidden="true"
                        >
                            <i class="ri-calendar-event-line text-lg text-primary/90"></i>
                        </span>
                        <input
                            id="create-sesi"
                            type="text"
                            name="sesi"
                            required
                            placeholder="Pagi"
                            autocomplete="off"
                            class="{{ $inputClass }}"
                        />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label
                        class="flex items-center justify-between gap-3 rounded-2xl border border-border/60 bg-card/50 p-4 dark:bg-card/30"
                        for="create-is_active"
                    >
                        <span>
                            <span class="block text-sm font-medium text-foreground">{{ __('Aktif') }}</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ __('Sesi ini sedang berjalan.') }}</span>
                        </span>
                        <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                            <input type="hidden" name="is_active" value="0" />
                            <input id="create-is_active" type="checkbox" name="is_active" value="1" class="peer sr-only" />
                            <span class="{{ $toggleTrackClass }}"></span>
                        </span>
                    </label>
                    <label
                        class="flex items-center justify-between gap-3 rounded-2xl border border-border/60 bg-card/50 p-4 dark:bg-card/30"
                        for="create-is_late"
                    >
                        <span>
                            <span class="block text-sm font-medium text-foreground">{{ __('Lewat') }}</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ __('Sesi untuk kehadiran lewat.') }}</span>
                        </span>
                        <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                            <input type="hidden" name="is_late" value="0" />
                            <input id="create-is_late" type="checkbox" name="is_late" value="1" class="peer sr-only" />
                            <span class="{{ $toggleTrackClass }}"></span>
                        </span>
                    </label>
                </div>

                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="create-countdown_start_late">
                            {{ __('No. kerusi lewat bermula') }}
                        </label>
                        <p class="text-xs text-muted-foreground">
                            {{ __('Nombor kerusi pertama untuk pegawai yang hadir lewat. Kosongkan untuk ikut offset kerusi.') }}
                        </p>
                    </div>
                    <div class="{{ $fieldShell }}">
                        <span
                            class="flex w-12 shrink-0 items-center justify-center border-r border-border/80 bg-muted/35 text-muted-foreground dark:bg-muted/25"
                            aria-hidden="true"
                        >
                            <i class="ri-hashtag text-lg text-primary/90"></i>
                        </span>
                        <input
                            id="create-countdown_start_late"
                            placeholder="{{ __('Auto') }}"
                            type="number"
                            name="countdown_start_late"
                            min="0"
                            step="1"
                            inputmode="numeric"
                            autocomplete="off"
                            class="{{ $inputClass }}"
                        />
                        <span
                            class="flex shrink-0 items-center border-l border-border/80 bg-muted/25 px-4 text-sm font-semibold tracking-wide text-muted-foreground"
                        >
                            {{ __('no. kerusi') }}
                        </span>
                    </div>
                </div>

                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="create-seat_offset">
                            {{ __('Offset kerusi') }}
                        </label>
                        <p class="text-xs text-muted-foreground">
                            {{ __('Nombor asas untuk sesi ini: 0 bermaksud kerusi bermula 1; 700 bermaksud kerusi 701+ dikira meja 1, 2, …') }}
                        </p>
                    </div>
                    <div class="{{ $fieldShell }}">
                        <span
                            class="flex w-12 shrink-0 items-center justify-center border-r border-border/80 bg-muted/35 text-muted-foreground dark:bg-muted/25"
                            aria-hidden="true"
                        >
                            <i class="ri-armchair-line text-lg text-primary/90"></i>
                        </span>
                        <input
                            id="create-seat_offset"
                            type="number"
                            name="seat_offset"
                            min="0"
                            step="1"
                            value="0"
                            required
                            inputmode="numeric"
                            autocomplete="off"
                            class="{{ $inputClass }}"
                        />
                    </div>
                </div>

                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="create-s_kehadiran">
                            {{ __('Jenis kehadiran') }}
                        </label>
                        <p class="text-xs text-muted-foreground">
                            {{ __('Mestilah sepadan dengan jenis sesi pegawai (pagi/petang) untuk pengesahan kehadiran.') }}
                        </p>
                    </div>
                    <div class="{{ $fieldShell }}">
                        <span
                            class="flex w-12 shrink-0 items-center justify-center border-r border-border/80 bg-muted/35 text-muted-foreground dark:bg-muted/25"
                            aria-hidden="true"
                        >
                            <i class="ri-sun-line text-lg text-primary/90"></i>
                        </span>
                        <select id="create-s_kehadiran" name="s_kehadiran" required class="{{ $inputClass }} appearance-none">
                            <option value="0">{{ __('Pagi') }}</option>
                            <option value="1">{{ __('Petang') }}</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                    <button type="submit" class="btn">
                        <i class="js-btn-spinner ri-loader-4-line hidden animate-spin"></i>
                        <span>{{ __('Simpan') }}</span>
                    </button>
                </div>
            </form>
        </x-crud-modal>

        <x-crud-modal modal-id="edit-modal" size="modal-lg" :title="__('Edit sesi majlis')">
            <form id="edit-sesi-majlis-form" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-sesi-majlis-id" />

                {{-- Live context: which session, and what it will look like after saving --}}
                <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-border/60 bg-muted/30 px-4 py-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary" aria-hidden="true">
                        <i class="ri-calendar-event-line text-lg"></i>
                    </span>
                    <div class="min-w-0 flex-1 leading-tight">
                        <p id="edit-context-name" class="truncate text-sm font-semibold text-foreground"></p>
                        <p class="text-xs text-muted-foreground">
                            {{ __('ID sesi') }} <span id="edit-context-id" class="tabular-nums"></span>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5" aria-live="polite">
                        <span id="edit-chip-active" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"></span>
                        <span id="edit-chip-late" class="hidden items-center gap-1 rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                            <i class="ri-time-line"></i>{{ __('Lewat') }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,14rem)]">
                    <div class="space-y-2 rounded-2xl border border-border/60 bg-card/50 p-4 dark:bg-card/30">
                        <label class="text-sm font-medium leading-none text-foreground" for="edit-sesi">{{ __('Sesi') }}</label>
                        <div class="{{ $fieldShell }}">
                            <span class="flex w-11 shrink-0 items-center justify-center border-r border-border/80 bg-muted/35 text-muted-foreground dark:bg-muted/25" aria-hidden="true">
                                <i class="ri-calendar-event-line text-lg text-primary/90"></i>
                            </span>
                            <input
                                id="edit-sesi"
                                type="text"
                                name="sesi"
                                required
                                autocomplete="off"
                                aria-describedby="edit-sesi-help"
                                class="{{ $inputClass }}"
                            />
                        </div>
                        <p id="edit-sesi-help" class="text-xs text-muted-foreground">{{ __('Nama pengenalan, contohnya "Pagi" atau "Petang".') }}</p>
                    </div>

                    <fieldset class="space-y-2 rounded-2xl border border-border/60 bg-card/50 p-4 dark:bg-card/30">
                        <legend class="float-left mb-2 w-full text-sm font-medium leading-none text-foreground">{{ __('Jenis kehadiran') }}</legend>
                        <div class="grid h-12 grid-cols-2 gap-1 rounded-xl bg-muted/40 p-1">
                            @foreach ([['0', __('Pagi'), 'ri-sun-line'], ['1', __('Petang'), 'ri-moon-line']] as [$val, $lbl, $ico])
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="s_kehadiran" value="{{ $val }}" class="peer sr-only" required />
                                    <span class="flex h-full items-center justify-center gap-1.5 rounded-lg text-sm font-medium text-muted-foreground transition peer-checked:bg-card peer-checked:text-foreground peer-checked:shadow-sm peer-checked:ring-1 peer-checked:ring-border/60 peer-focus-visible:ring-2 peer-focus-visible:ring-ring">
                                        <i class="{{ $ico }}" aria-hidden="true"></i>{{ $lbl }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="clear-both text-xs text-muted-foreground">{{ __('Mesti sepadan dengan jenis sesi pegawai.') }}</p>
                    </fieldset>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label
                        class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-border/60 bg-card/50 p-4 transition-colors hover:bg-card dark:bg-card/30"
                        for="edit-is_active"
                    >
                        <span>
                            <span class="block text-sm font-medium text-foreground">{{ __('Aktif') }}</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ __('Sesi ini sedang berjalan.') }}</span>
                        </span>
                        <span class="relative inline-flex shrink-0 items-center">
                            <input type="hidden" name="is_active" value="0" />
                            <input id="edit-is_active" type="checkbox" name="is_active" value="1" class="peer sr-only" />
                            <span class="{{ $toggleTrackClass }}"></span>
                        </span>
                    </label>
                    <label
                        class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-border/60 bg-card/50 p-4 transition-colors hover:bg-card dark:bg-card/30"
                        for="edit-is_late"
                    >
                        <span>
                            <span class="block text-sm font-medium text-foreground">{{ __('Lewat') }}</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ __('Sesi untuk kehadiran lewat.') }}</span>
                        </span>
                        <span class="relative inline-flex shrink-0 items-center">
                            <input type="hidden" name="is_late" value="0" />
                            <input id="edit-is_late" type="checkbox" name="is_late" value="1" class="peer sr-only" />
                            <span class="{{ $toggleTrackClass }}"></span>
                        </span>
                    </label>
                </div>

                <div
                    id="edit-deactivate-warning"
                    role="alert"
                    class="hidden items-start gap-2.5 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-3.5 text-sm text-amber-700 dark:text-amber-300"
                >
                    <i class="ri-alert-line mt-0.5 shrink-0 text-lg" aria-hidden="true"></i>
                    <p>{{ __('Menyahaktifkan sesi lewat akan menetapkan nombor lewat untuk semua pegawai yang hadir dalam sesi ini.') }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 rounded-2xl border border-border/60 bg-card/50 p-4 dark:bg-card/30">
                        <div class="flex items-center justify-between gap-2">
                            <label class="text-sm font-medium leading-none text-foreground" for="edit-countdown_start_late">{{ __('No. kerusi lewat bermula') }}</label>
                            <button
                                type="button"
                                id="edit-late-auto"
                                class="hidden rounded-md px-1.5 py-0.5 text-xs font-medium text-primary hover:bg-primary/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                {{ __('Auto') }}
                            </button>
                        </div>
                        <div class="{{ $fieldShell }}">
                            <span class="flex w-11 shrink-0 items-center justify-center border-r border-border/80 bg-muted/35 text-muted-foreground dark:bg-muted/25" aria-hidden="true">
                                <i class="ri-hashtag text-lg text-primary/90"></i>
                            </span>
                            <input
                                id="edit-countdown_start_late"
                                type="number"
                                name="countdown_start_late"
                                min="1"
                                step="1"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="{{ __('Auto') }}"
                                aria-describedby="edit-late-hint"
                                class="{{ $inputClass }}"
                            />
                            <span class="flex shrink-0 items-center border-l border-border/80 bg-muted/25 px-3 text-xs font-semibold tracking-wide text-muted-foreground">{{ __('no. kerusi') }}</span>
                        </div>
                        <div id="edit-late-preview" class="flex flex-wrap items-center gap-1" aria-hidden="true"></div>
                        <p id="edit-late-hint" class="text-xs text-muted-foreground" aria-live="polite"></p>
                    </div>

                    <div class="space-y-2 rounded-2xl border border-border/60 bg-card/50 p-4 dark:bg-card/30">
                        <label class="text-sm font-medium leading-none text-foreground" for="edit-seat_offset">{{ __('Offset kerusi') }}</label>
                        <div class="{{ $fieldShell }}">
                            <span class="flex w-11 shrink-0 items-center justify-center border-r border-border/80 bg-muted/35 text-muted-foreground dark:bg-muted/25" aria-hidden="true">
                                <i class="ri-armchair-line text-lg text-primary/90"></i>
                            </span>
                            <input
                                id="edit-seat_offset"
                                type="number"
                                name="seat_offset"
                                min="0"
                                step="1"
                                required
                                inputmode="numeric"
                                autocomplete="off"
                                aria-describedby="edit-seat-hint"
                                class="{{ $inputClass }}"
                            />
                        </div>
                        <p id="edit-seat-hint" class="text-xs text-muted-foreground" aria-live="polite"></p>
                    </div>
                </div>

                <div class="modal-footer sticky bottom-0 z-10 !-mb-6">
                    <span id="edit-dirty-hint" class="mr-auto hidden text-xs text-muted-foreground sm:inline">{{ __('Tiada perubahan untuk disimpan.') }}</span>
                    <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                    <button type="submit" class="btn disabled:cursor-not-allowed disabled:opacity-50">
                        <i class="js-btn-spinner ri-loader-4-line hidden animate-spin"></i>
                        <span>{{ __('Kemas kini') }}</span>
                    </button>
                </div>
            </form>
        </x-crud-modal>

        <x-crud-modal modal-id="guide-modal" size="modal-lg" :title="__('Panduan sesi majlis')">
            <div x-data="{ tab: 'medan' }">
                <div
                    class="flex items-start gap-3 rounded-2xl border border-border/70 bg-gradient-to-br from-card via-card to-primary/[0.06] p-4 dark:from-card dark:via-card dark:to-primary/[0.09]"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-md shadow-primary/25"
                        aria-hidden="true"
                    >
                        <i class="ri-lightbulb-line text-lg"></i>
                    </span>
                    <p class="text-sm leading-relaxed text-muted-foreground">
                        {{ __('Rujukan pantas medan, peraturan, dan mod acara untuk pengurusan sesi majlis.') }}
                    </p>
                </div>

                <div
                    role="tablist"
                    class="mt-4 grid grid-cols-2 gap-1 rounded-xl bg-muted/40 p-1 sm:grid-cols-4"
                >
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="tab === 'medan'"
                        @click="tab = 'medan'"
                        :class="tab === 'medan' ? 'bg-card text-foreground shadow-sm ring-1 ring-border/60' : 'text-muted-foreground'"
                        class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium transition sm:text-sm"
                    >
                        <i class="ri-list-check-2"></i>
                        <span>{{ __('Medan') }}</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="tab === 'offset'"
                        @click="tab = 'offset'"
                        :class="tab === 'offset' ? 'bg-card text-foreground shadow-sm ring-1 ring-border/60' : 'text-muted-foreground'"
                        class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium transition sm:text-sm"
                    >
                        <i class="ri-armchair-line"></i>
                        <span>{{ __('Offset kerusi') }}</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="tab === 'peraturan'"
                        @click="tab = 'peraturan'"
                        :class="tab === 'peraturan' ? 'bg-card text-foreground shadow-sm ring-1 ring-border/60' : 'text-muted-foreground'"
                        class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium transition sm:text-sm"
                    >
                        <i class="ri-shield-check-line"></i>
                        <span>{{ __('Peraturan') }}</span>
                    </button>
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="tab === 'mode'"
                        @click="tab = 'mode'"
                        :class="tab === 'mode' ? 'bg-card text-foreground shadow-sm ring-1 ring-border/60' : 'text-muted-foreground'"
                        class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-medium transition sm:text-sm"
                    >
                        <i class="ri-toggle-line"></i>
                        <span>{{ __('Mod acara') }}</span>
                    </button>
                </div>

                <div class="mt-4" x-cloak>
                    <div x-show="tab === 'medan'" class="grid gap-3 sm:grid-cols-2">
                        <div class="flex items-start gap-2.5 rounded-xl border border-border/60 bg-muted/10 p-3">
                            <i class="ri-calendar-event-line mt-0.5 shrink-0 text-primary" aria-hidden="true"></i>
                            <p class="text-xs leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('Sesi') }}:</span>
                                {{ __('nama pengenalan untuk sesi ini, contohnya "Pagi" atau "Petang".') }}
                            </p>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl border border-border/60 bg-muted/10 p-3">
                            <i class="ri-flashlight-line mt-0.5 shrink-0 text-primary" aria-hidden="true"></i>
                            <p class="text-xs leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('Aktif') }}:</span>
                                {{ __('sesi ini sedang berjalan.') }}
                            </p>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl border border-border/60 bg-muted/10 p-3">
                            <i class="ri-timer-flash-line mt-0.5 shrink-0 text-primary" aria-hidden="true"></i>
                            <p class="text-xs leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('Lewat') }}:</span>
                                {{ __('sesi untuk kehadiran lewat.') }}
                            </p>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl border border-border/60 bg-muted/10 p-3">
                            <i class="ri-hashtag mt-0.5 shrink-0 text-primary" aria-hidden="true"></i>
                            <p class="text-xs leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('No. kerusi lewat bermula') }}:</span>
                                {{ __('nombor kerusi pertama untuk pegawai yang hadir lewat, kemudian bertambah 1 setiap pegawai. Kosong = ikut offset kerusi.') }}
                            </p>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl border border-border/60 bg-muted/10 p-3">
                            <i class="ri-armchair-line mt-0.5 shrink-0 text-primary" aria-hidden="true"></i>
                            <p class="text-xs leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('Offset kerusi') }}:</span>
                                {{ __('nombor asas untuk sesi ini.') }}
                            </p>
                        </div>
                        <div class="flex items-start gap-2.5 rounded-xl border border-border/60 bg-muted/10 p-3">
                            <i class="ri-sun-line mt-0.5 shrink-0 text-primary" aria-hidden="true"></i>
                            <p class="text-xs leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('Jenis kehadiran') }}:</span>
                                {{ __('mestilah sepadan dengan sesi pegawai (pagi/petang).') }}
                            </p>
                        </div>
                    </div>

                    <div x-show="tab === 'offset'" class="space-y-4">
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            {{ __('Setiap sesi menetapkan offset kerusi asas untuk pengiraan no. meja: 0 bermaksud kerusi bermula 1; 700 bermaksud kerusi 701+ dikira meja 1, 2, dan seterusnya.') }}
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="flex flex-wrap items-center justify-center gap-2 rounded-xl border border-border/60 bg-muted/10 p-4 text-sm font-medium">
                                <span class="rounded-lg bg-card px-2.5 py-1 shadow-sm ring-1 ring-border/60">{{ __('Offset 0') }}</span>
                                <i class="ri-arrow-right-line text-muted-foreground"></i>
                                <span class="rounded-lg bg-card px-2.5 py-1 shadow-sm ring-1 ring-border/60">{{ __('Kerusi 1') }}</span>
                                <i class="ri-arrow-right-line text-muted-foreground"></i>
                                <span class="rounded-lg bg-primary px-2.5 py-1 text-primary-foreground shadow-sm">{{ __('Meja 1') }}</span>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-2 rounded-xl border border-border/60 bg-muted/10 p-4 text-sm font-medium">
                                <span class="rounded-lg bg-card px-2.5 py-1 shadow-sm ring-1 ring-border/60">{{ __('Offset 700') }}</span>
                                <i class="ri-arrow-right-line text-muted-foreground"></i>
                                <span class="rounded-lg bg-card px-2.5 py-1 shadow-sm ring-1 ring-border/60">{{ __('Kerusi 701') }}</span>
                                <i class="ri-arrow-right-line text-muted-foreground"></i>
                                <span class="rounded-lg bg-primary px-2.5 py-1 text-primary-foreground shadow-sm">{{ __('Meja 1') }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ __('Setiap sesi dikira bermula dari offset sendiri.') }}</p>
                    </div>

                    <div x-show="tab === 'peraturan'" class="divide-y divide-border/60">
                        <div class="flex items-start gap-2.5 py-3 first:pt-0">
                            <i class="ri-error-warning-line mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                            <p class="text-sm leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('Satu sesi aktif') }}:</span>
                                {{ __('pastikan hanya satu sesi ditanda aktif pada satu masa.') }}
                            </p>
                        </div>
                        <div class="flex items-start gap-2.5 py-3">
                            <i class="ri-timer-flash-line mt-0.5 shrink-0 text-primary" aria-hidden="true"></i>
                            <p class="text-sm leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('Sesi lewat') }}:</span>
                                {{ __('menutup sesi lewat akan mengagihkan no. panggilan lewat secara automatik.') }}
                            </p>
                        </div>
                        <div class="flex items-start gap-2.5 py-3 last:pb-0">
                            <i class="ri-sun-line mt-0.5 shrink-0 text-primary" aria-hidden="true"></i>
                            <p class="text-sm leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('Jenis kehadiran') }}:</span>
                                {{ __('mestilah sepadan dengan sesi pegawai (pagi/petang) untuk pengesahan kehadiran.') }}
                            </p>
                        </div>
                    </div>

                    <div x-show="tab === 'mode'" class="grid gap-3 sm:grid-cols-2">
                        @foreach ($eventModeOptions as $value => $label)
                            <div
                                data-mode-card="{{ $value }}"
                                class="relative rounded-xl border border-border/60 bg-muted/10 p-4"
                            >
                                <span
                                    data-mode-current
                                    class="{{ $eventMode === $value ? '' : 'hidden' }} absolute right-3 top-3 rounded-full bg-primary/10 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-primary"
                                >
                                    {{ __('Semasa') }}
                                </span>
                                <i class="{{ $eventModeIcons[$value] ?? 'ri-toggle-line' }} text-2xl text-primary" aria-hidden="true"></i>
                                <p class="mt-2 text-sm font-semibold text-foreground">{{ $label }}</p>
                                <p class="mt-1 text-xs leading-snug text-muted-foreground">
                                    {{ $value === 'jasamu'
                                        ? __('Majlis persaraan: memaparkan tarikh bersara, jenis persaraan dan tempoh berkhidmat.')
                                        : __('Majlis anugerah: memaparkan PTJ pegawai seperti biasa.') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn" data-modal-close>{{ __('Faham') }}</button>
            </div>
        </x-crud-modal>
    </x-kawalan-shell>

    @push('scripts')
        <script>
            $(function () {
                function guideSeen() {
                    try {
                        return localStorage.getItem('sesiMajlisGuideSeen') === '1';
                    } catch (e) {
                        return true;
                    }
                }

                function markGuideSeen() {
                    try {
                        localStorage.setItem('sesiMajlisGuideSeen', '1');
                    } catch (e) {
                        // storage unavailable — ignore
                    }
                    $('#guide-hint-dot').addClass('hidden');
                }

                if (!guideSeen()) {
                    $('#guide-hint-dot').removeClass('hidden');
                }

                $('#guide-trigger').on('click', markGuideSeen);

                $(document).on('keydown', function (e) {
                    if (e.key !== '?' || e.metaKey || e.ctrlKey || e.altKey) {
                        return;
                    }
                    const tag = (document.activeElement?.tagName || '').toLowerCase();
                    if (tag === 'input' || tag === 'textarea' || tag === 'select') {
                        return;
                    }
                    if (document.querySelector('.modal.is-open')) {
                        return;
                    }
                    e.preventDefault();
                    markGuideSeen();
                    window.openModal('guide-modal');
                });

                function setBusy($form, busy) {
                    const $btn = $form.find('button[type="submit"]');
                    $btn.prop('disabled', busy);
                    $btn.find('.js-btn-spinner').toggleClass('hidden', !busy);
                }

                function toast(icon, title) {
                    window.Swal?.fire({
                        toast: true,
                        position: 'top-end',
                        icon: icon,
                        title: title,
                        showConfirmButton: false,
                        timer: 2200,
                        background: 'var(--popover)',
                        color: 'var(--popover-foreground)',
                    });
                }

                function showError(xhr, fallback) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        window.Swal?.fire({
                            icon: 'error',
                            title: '{{ __('Ralat pengesahan') }}',
                            html: Object.values(xhr.responseJSON.errors).flat().join('<br>'),
                            confirmButtonText: '{{ __('OK') }}',
                            background: 'var(--popover)',
                            color: 'var(--popover-foreground)',
                            buttonsStyling: false,
                            customClass: {
                                popup: 'kawalan-swal2-popup',
                                htmlContainer: 'kawalan-swal2-text',
                                actions: 'kawalan-swal2-actions',
                                confirmButton: 'kawalan-swal2-confirm',
                            },
                        });
                        return;
                    }

                    window.Swal?.fire({
                        icon: 'error',
                        title: fallback,
                        confirmButtonText: '{{ __('OK') }}',
                        background: 'var(--popover)',
                        color: 'var(--popover-foreground)',
                        buttonsStyling: false,
                        customClass: {
                            popup: 'kawalan-swal2-popup',
                            actions: 'kawalan-swal2-actions',
                            confirmButton: 'kawalan-swal2-confirm',
                        },
                    });
                }

                function nameChips(names, tone, emptyText) {
                    if (!names.length) {
                        return $('<span class="text-sm font-medium text-muted-foreground"></span>').text(emptyText);
                    }
                    return names.map(function (name) {
                        const $chip = $('<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-semibold"></span>')
                            .addClass(tone);
                        $chip.append('<span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>');
                        $chip.append(document.createTextNode(name));
                        return $chip;
                    });
                }

                function renderStats(stats) {
                    if (!stats) {
                        return;
                    }

                    $('[data-stat="active-names"]').empty().append(
                        nameChips(stats.active, 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400', '{{ __('Tiada sesi aktif') }}')
                    );
                    $('[data-stat="active-warning"]').toggleClass('hidden', stats.active.length <= 1).toggleClass('flex', stats.active.length > 1);
                    $('[data-stat="active-hint"]').text(stats.active.length
                        ? '{{ __('Sesi yang sedang menerima kehadiran.') }}'
                        : '{{ __('Aktifkan satu sesi untuk mula menerima kehadiran.') }}');

                    $('[data-stat="late-names"]').empty().append(
                        nameChips(stats.late, 'bg-orange-500/10 text-orange-700 dark:text-orange-400', '{{ __('Tiada sesi lewat') }}')
                    );

                    $('[data-stat="total-label"]').removeClass('animate-pulse').text(stats.total);
                    $('[data-stat="count-pagi"]').text(stats.pagi);
                    $('[data-stat="count-petang"]').text(stats.petang);
                    const pct = function (n) { return stats.total ? (n / stats.total) * 100 : 0; };
                    $('[data-stat="bar-pagi"]').css('width', pct(stats.pagi) + '%');
                    $('[data-stat="bar-petang"]').css('width', pct(stats.petang) + '%');
                    $('[data-stat="total-bar"]').attr('aria-label',
                        '{{ __('Pagi') }} ' + stats.pagi + ', {{ __('Petang') }} ' + stats.petang);
                    $('[data-stat="total-count"]').removeClass('animate-pulse').text(stats.total);
                }

                const table = $('#sesi-majlis-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    scrollY: '100%',
                    ajax: '{{ route('admin.kawalan.sesi-majlis.datatable') }}',
                    language: {
                        search: '',
                        searchPlaceholder: '{{ __('Cari sesi…') }}',
                        lengthMenu: '_MENU_ {{ __('per halaman') }}',
                        info: '{{ __('Menunjukkan _START_–_END_ daripada _TOTAL_ sesi') }}',
                        infoEmpty: '{{ __('Tiada sesi') }}',
                        infoFiltered: '({{ __('disaring daripada') }} _MAX_ {{ __('sesi') }})',
                        zeroRecords: '{{ __('Tiada sesi sepadan carian.') }}',
                        emptyTable: '{{ __('Tiada sesi lagi — klik "Tambah sesi" untuk mula.') }}',
                        paginate: {
                            previous: '<i class="ri-arrow-left-s-line"></i>',
                            next: '<i class="ri-arrow-right-s-line"></i>',
                        },
                    },
                    createdRow: function (row, data) {
                        $(row).toggleClass('kawalan-dt-row-active', Number(data.is_active_raw) === 1);
                    },
                    columnDefs: [
                        { targets: [4, 5], className: 'text-muted-foreground tabular-nums' },
                        { targets: 6, className: 'text-muted-foreground' },
                        { targets: -1, className: 'text-right' },
                    ],
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'sesi', name: 'sesi' },
                        {
                            data: 'is_active_label',
                            name: 'is_active',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'is_late_label',
                            name: 'is_late',
                            orderable: false,
                            searchable: false,
                        },
                        { data: 'countdown_start_late', name: 'countdown_start_late' },
                        { data: 'seat_offset', name: 'seat_offset', defaultContent: '0' },
                        {
                            data: 's_kehadiran_label',
                            name: 's_kehadiran',
                            orderable: false,
                            searchable: false,
                        },
                        { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });

                table.on('xhr.dt', function (e, settings, json) {
                    renderStats(json?.stats);
                });

                const resizeObserver = new ResizeObserver(function () {
                    table.columns.adjust();
                });
                resizeObserver.observe(document.getElementById('sesi-majlis-table').closest('.kawalan-dt-card--fill'));

                $('#create-sesi-majlis-form').on('submit', function (e) {
                    e.preventDefault();
                    const $form = $(this);
                    setBusy($form, true);
                    $.ajax({
                        url: '{{ route('admin.kawalan.sesi-majlis.store') }}',
                        method: 'POST',
                        data: $form.serialize(),
                        headers: { Accept: 'application/json' },
                    })
                        .done(function () {
                            closeModal('create-modal');
                            $form.trigger('reset');
                            $('#create-is_active').prop('checked', false);
                            $('#create-is_late').prop('checked', false);
                            $('#create-seat_offset').val('0');
                            $('#create-s_kehadiran').val('0');
                            table.ajax.reload(null, false);
                            toast('success', '{{ __('Sesi ditambah') }}');
                        })
                        .fail(function (xhr) {
                            showError(xhr, '{{ __('Ralat menambah sesi') }}');
                        })
                        .always(function () {
                            setBusy($form, false);
                        });
                });

                const $editForm = $('#edit-sesi-majlis-form');
                let editInitial = '';
                let editWas = { active: false, late: false };

                function refreshEditUi() {
                    const name = $('#edit-sesi').val().trim();
                    const active = $('#edit-is_active').prop('checked');
                    const late = $('#edit-is_late').prop('checked');

                    $('#edit-context-name').text(name || '{{ __('Sesi tanpa nama') }}');
                    $('#edit-chip-active')
                        .html(active
                            ? '<i class="ri-checkbox-circle-line"></i>{{ __('Aktif') }}'
                            : '<i class="ri-pause-circle-line"></i>{{ __('Tidak aktif') }}')
                        .toggleClass('bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', active)
                        .toggleClass('bg-muted text-muted-foreground', !active);
                    $('#edit-chip-late').toggleClass('hidden', !late).toggleClass('inline-flex', late);

                    const offsetVal = parseInt($('#edit-seat_offset').val(), 10);
                    const auto = offsetVal > 0 ? offsetVal : 1;
                    const lateRaw = $('#edit-countdown_start_late').val().trim();
                    const lateVal = parseInt(lateRaw, 10);
                    const start = lateRaw === '' || Number.isNaN(lateVal) ? auto : Math.max(1, lateVal);
                    const isAuto = lateRaw === '' || Number.isNaN(lateVal);
                    $('#edit-late-preview').html(
                        [0, 1, 2]
                            .map(function (i) {
                                return '<span class="rounded-md bg-primary/10 px-2 py-0.5 text-xs font-semibold tabular-nums text-primary">' + (start + i) + '</span>';
                            })
                            .join('') + '<span class="px-1 text-xs text-muted-foreground">…</span>'
                    );
                    $('#edit-late-hint').text(isAuto
                        ? '{{ __('Auto: ikut offset kerusi') }} (' + auto + ').'
                        : '{{ __('Pegawai lewat dipanggil mulai no.') }} ' + start + '.');
                    $('#edit-late-auto').toggleClass('hidden', isAuto);

                    const offset = parseInt($('#edit-seat_offset').val(), 10);
                    $('#edit-seat-hint').text('{{ __('Kerusi bermula dari no.') }} ' + ((Number.isNaN(offset) ? 0 : offset) + 1));

                    const showWarn = editWas.active && editWas.late && !active;
                    $('#edit-deactivate-warning').toggleClass('hidden', !showWarn).toggleClass('flex', showWarn);

                    const dirty = $editForm.serialize() !== editInitial;
                    $editForm.find('button[type="submit"]').prop('disabled', !dirty);
                    $('#edit-dirty-hint').toggleClass('hidden', dirty).toggleClass('sm:inline', !dirty);
                }

                $editForm.on('input change', refreshEditUi);
                $('#edit-late-auto').on('click', function () {
                    $('#edit-countdown_start_late').val('').trigger('input').trigger('focus');
                });

                $('#sesi-majlis-table').on('click', '.js-edit-sesi-majlis', function () {
                    const $btn = $(this);
                    $('#edit-sesi-majlis-id').val($btn.data('id'));
                    $('#edit-context-id').text('#' + $btn.data('id'));
                    $('#edit-sesi').val($btn.data('sesi'));
                    $('#edit-is_active').prop('checked', String($btn.data('is_active')) === '1');
                    $('#edit-is_late').prop('checked', String($btn.data('is_late')) === '1');
                    $('#edit-countdown_start_late').val($btn.attr('data-countdown-start-late') ?? '');
                    $('#edit-seat_offset').val($btn.attr('data-seat-offset') ?? '0');
                    $editForm
                        .find('input[name="s_kehadiran"][value="' + String($btn.attr('data-s-kehadiran') ?? '0') + '"]')
                        .prop('checked', true);
                    editInitial = $editForm.serialize();
                    editWas = {
                        active: $('#edit-is_active').prop('checked'),
                        late: $('#edit-is_late').prop('checked'),
                    };
                    refreshEditUi();
                    openModal('edit-modal');
                    setTimeout(function () {
                        $('#edit-sesi').trigger('focus').trigger('select');
                    }, 220);
                });

                $('#edit-sesi-majlis-form').on('submit', function (e) {
                    e.preventDefault();
                    const id = $('#edit-sesi-majlis-id').val();
                    const $form = $(this);
                    setBusy($form, true);
                    $.ajax({
                        url: '{{ url('/admin/kawalan/sesi-majlis') }}/' + id,
                        method: 'POST',
                        data: $form.serialize(),
                        headers: { Accept: 'application/json' },
                    })
                        .done(function () {
                            closeModal('edit-modal');
                            table.ajax.reload(null, false);
                            toast('success', '{{ __('Sesi dikemas kini') }}');
                        })
                        .fail(function (xhr) {
                            showError(xhr, '{{ __('Ralat mengemaskini sesi') }}');
                        })
                        .always(function () {
                            setBusy($form, false);
                            refreshEditUi();
                        });
                });

                $('#sesi-majlis-table').on('click', '.js-delete-sesi-majlis', function () {
                    const id = $(this).data('id');
                    window.kawalanConfirmDelete({
                        title: '{{ __('Padam rekod ini?') }}',
                        text: '{{ __('Tindakan ini tidak boleh dibuat asal.') }}',
                        confirmButtonText: '{{ __('Padam') }}',
                        cancelButtonText: '{{ __('Batal') }}',
                    }).then(function (ok) {
                        if (!ok) {
                            return;
                        }
                        $.ajax({
                            url: '{{ url('/admin/kawalan/sesi-majlis') }}/' + id,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                            headers: { Accept: 'application/json' },
                        })
                            .done(function () {
                                table.ajax.reload(null, false);
                                toast('success', '{{ __('Sesi dipadam') }}');
                            })
                            .fail(function (xhr) {
                                showError(xhr, '{{ __('Ralat memadam sesi') }}');
                            });
                    });
                });

                let currentEventMode = '{{ $eventMode }}';
                const eventModeLabels = @json($eventModeOptions);

                function applyEventMode(mode, previous) {
                    $('#event-mode-choice').prop('disabled', true).css('opacity', 0.7);
                    $('#event-mode-spinner').removeClass('hidden');

                    $.ajax({
                        url: '{{ route('admin.kawalan.sesi-majlis.event-mode') }}',
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}', mode: mode },
                        headers: { Accept: 'application/json' },
                    })
                        .done(function () {
                            currentEventMode = mode;
                            $('[data-stat="mode-label"]').text(eventModeLabels[mode] ?? mode);
                            $('[data-mode-current]').addClass('hidden');
                            $('[data-mode-card="' + mode + '"] [data-mode-current]').removeClass('hidden');
                            toast('success', '{{ __('Mod acara dikemas kini') }}');
                        })
                        .fail(function (xhr) {
                            revertEventMode(previous);
                            showError(xhr, '{{ __('Ralat mengemaskini mod acara') }}');
                        })
                        .always(function () {
                            $('#event-mode-choice').prop('disabled', false).css('opacity', '');
                            $('#event-mode-spinner').addClass('hidden');
                        });
                }

                function revertEventMode(previous) {
                    $('.js-event-mode').prop('checked', false);
                    $('.js-event-mode[value="' + previous + '"]').prop('checked', true);
                }

                $('.js-event-mode').on('change', function () {
                    const mode = $(this).val();
                    const previous = currentEventMode;
                    if (mode === previous) {
                        return;
                    }

                    window.kawalanConfirmDelete({
                        title: '{{ __('Tukar mod acara?') }}',
                        text: '{{ __('Mod akan ditukar kepada') }} "' + (eventModeLabels[mode] ?? mode) + '" {{ __('untuk semua pengguna.') }}',
                        confirmButtonText: '{{ __('Tukar') }}',
                        cancelButtonText: '{{ __('Batal') }}',
                    }).then(function (ok) {
                        if (ok) {
                            applyEventMode(mode, previous);
                        } else {
                            revertEventMode(previous);
                        }
                    });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
