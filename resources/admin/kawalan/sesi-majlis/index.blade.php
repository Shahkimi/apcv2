@php
    $inputClass =
        'min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-base tabular-nums text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0 sm:text-sm';
    $fieldShell =
        'flex h-12 overflow-hidden rounded-xl border border-input/90 bg-background shadow-sm ring-1 ring-border/30 transition-[box-shadow,ring-color,border-color] focus-within:border-ring focus-within:shadow-md focus-within:ring-2 focus-within:ring-ring dark:bg-background/80';
    $toggleTrackClass =
        'peer h-5 w-9 rounded-full bg-muted ring-1 ring-border/60 transition-all after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-background after:shadow-sm after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-ring peer-focus-visible:ring-offset-2';
    $eventModeIcons = ['jasamu' => 'ri-hand-heart-line', 'apc' => 'ri-award-line'];
    $eventModeShort = ['jasamu' => 'Jasamu', 'apc' => 'APC'];
@endphp

<x-dashboard-layout :title="__('Sesi Majlis')" role="admin" :fill-height="true">
    <x-kawalan-shell fill>
        <div class="shrink-0 [&>div]:!mb-0">
            <x-crud-header
                :title="__('Sesi Majlis')"
                :description="__('Urus sesi majlis: aktif, lewat, mula kira detik, dan offset kerusi untuk pengiraan no. meja.')"
                :create-label="__('Tambah sesi')"
            />
        </div>

        {{-- KPI bento row --}}
        <div class="grid shrink-0 gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_minmax(0,1.5fr)]">
            <div class="rounded-2xl border border-border/70 bg-card p-4 shadow-sm ring-1 ring-border/40">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        aria-hidden="true"
                    >
                        <i class="ri-flashlight-line text-xl"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ __('Sesi aktif') }}
                        </p>
                        <p class="mt-1 animate-pulse truncate text-sm font-semibold text-foreground" data-stat="active-names">…</p>
                        <p class="mt-1 hidden items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400" data-stat="active-warning">
                            <i class="ri-error-warning-line"></i>
                            <span>{{ __('Lebih daripada satu sesi aktif') }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-border/70 bg-card p-4 shadow-sm ring-1 ring-border/40">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400"
                        aria-hidden="true"
                    >
                        <i class="ri-timer-flash-line text-xl"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ __('Sesi lewat') }}
                        </p>
                        <p class="mt-1 animate-pulse truncate text-sm font-semibold text-foreground" data-stat="late-names">…</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-border/70 bg-card p-4 shadow-sm ring-1 ring-border/40">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        aria-hidden="true"
                    >
                        <i class="ri-stack-line text-xl"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ __('Jumlah sesi') }}
                        </p>
                        <p class="mt-1 animate-pulse text-sm font-semibold text-foreground" data-stat="total-label">…</p>
                        <p class="mt-0.5 animate-pulse text-xs text-muted-foreground" data-stat="total-breakdown">…</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-border/70 bg-card p-4 shadow-sm ring-1 ring-border/40">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-start gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            aria-hidden="true"
                        >
                            <i class="ri-toggle-line text-xl"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                {{ __('Mod acara') }}
                            </p>
                            <p class="mt-1 truncate text-xs text-muted-foreground" data-stat="mode-label">
                                {{ $eventModeOptions[$eventMode] ?? '-' }}
                            </p>
                        </div>
                    </div>
                    <i
                        id="event-mode-spinner"
                        class="ri-loader-4-line mt-1 hidden shrink-0 animate-spin text-primary"
                        aria-hidden="true"
                    ></i>
                </div>

                <fieldset id="event-mode-choice" class="mt-3 grid grid-cols-2 gap-1 rounded-xl bg-muted/40 p-1">
                    <legend class="sr-only">{{ __('Mod Acara') }}</legend>
                    @foreach ($eventModeOptions as $value => $label)
                        <label
                            class="flex cursor-pointer items-center justify-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-muted-foreground transition has-[:checked]:bg-card has-[:checked]:text-foreground has-[:checked]:shadow-sm has-[:checked]:ring-1 has-[:checked]:ring-border/60 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
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
            </div>
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
                :columns="['ID', __('Sesi'), __('Aktif'), __('Lewat'), __('Mula kira detik'), __('Offset kerusi'), __('Jenis kehadiran'), __('Dicipta'), __('Tindakan')]"
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
                            {{ __('Mula kira detik') }}
                        </label>
                        <p class="text-xs text-muted-foreground">
                            {{ __('Bilangan saat sebelum kira detik lewat bermula.') }}
                        </p>
                    </div>
                    <div class="{{ $fieldShell }}">
                        <span
                            class="flex w-12 shrink-0 items-center justify-center border-r border-border/80 bg-muted/35 text-muted-foreground dark:bg-muted/25"
                            aria-hidden="true"
                        >
                            <i class="ri-timer-flash-line text-lg text-primary/90"></i>
                        </span>
                        <input
                            id="create-countdown_start_late"
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
                            {{ __('saat') }}
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
            <form id="edit-sesi-majlis-form" class="space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-sesi-majlis-id" />

                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="edit-sesi">
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
                            id="edit-sesi"
                            type="text"
                            name="sesi"
                            required
                            autocomplete="off"
                            class="{{ $inputClass }}"
                        />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label
                        class="flex items-center justify-between gap-3 rounded-2xl border border-border/60 bg-card/50 p-4 dark:bg-card/30"
                        for="edit-is_active"
                    >
                        <span>
                            <span class="block text-sm font-medium text-foreground">{{ __('Aktif') }}</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ __('Sesi ini sedang berjalan.') }}</span>
                        </span>
                        <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                            <input type="hidden" name="is_active" value="0" />
                            <input id="edit-is_active" type="checkbox" name="is_active" value="1" class="peer sr-only" />
                            <span class="{{ $toggleTrackClass }}"></span>
                        </span>
                    </label>
                    <label
                        class="flex items-center justify-between gap-3 rounded-2xl border border-border/60 bg-card/50 p-4 dark:bg-card/30"
                        for="edit-is_late"
                    >
                        <span>
                            <span class="block text-sm font-medium text-foreground">{{ __('Lewat') }}</span>
                            <span class="mt-0.5 block text-xs text-muted-foreground">{{ __('Sesi untuk kehadiran lewat.') }}</span>
                        </span>
                        <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                            <input type="hidden" name="is_late" value="0" />
                            <input id="edit-is_late" type="checkbox" name="is_late" value="1" class="peer sr-only" />
                            <span class="{{ $toggleTrackClass }}"></span>
                        </span>
                    </label>
                </div>

                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="edit-countdown_start_late">
                            {{ __('Mula kira detik') }}
                        </label>
                        <p class="text-xs text-muted-foreground">
                            {{ __('Bilangan saat sebelum kira detik lewat bermula.') }}
                        </p>
                    </div>
                    <div class="{{ $fieldShell }}">
                        <span
                            class="flex w-12 shrink-0 items-center justify-center border-r border-border/80 bg-muted/35 text-muted-foreground dark:bg-muted/25"
                            aria-hidden="true"
                        >
                            <i class="ri-timer-flash-line text-lg text-primary/90"></i>
                        </span>
                        <input
                            id="edit-countdown_start_late"
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
                            {{ __('saat') }}
                        </span>
                    </div>
                </div>

                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="edit-seat_offset">
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
                            id="edit-seat_offset"
                            type="number"
                            name="seat_offset"
                            min="0"
                            step="1"
                            required
                            inputmode="numeric"
                            autocomplete="off"
                            class="{{ $inputClass }}"
                        />
                    </div>
                </div>

                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="edit-s_kehadiran">
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
                        <select id="edit-s_kehadiran" name="s_kehadiran" required class="{{ $inputClass }} appearance-none">
                            <option value="0">{{ __('Pagi') }}</option>
                            <option value="1">{{ __('Petang') }}</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                    <button type="submit" class="btn">
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
                            <i class="ri-hourglass-line mt-0.5 shrink-0 text-primary" aria-hidden="true"></i>
                            <p class="text-xs leading-snug text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ __('Mula kira detik') }}:</span>
                                {{ __('bilangan saat sebelum kira detik lewat bermula.') }}
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

                function renderStats(stats) {
                    if (!stats) {
                        return;
                    }

                    const $activeNames = $('[data-stat="active-names"]').removeClass('animate-pulse');
                    $activeNames.text(stats.active.length ? stats.active.join(', ') : '{{ __('Tiada') }}');
                    $('[data-stat="active-warning"]').toggleClass('hidden', stats.active.length <= 1).toggleClass('flex', stats.active.length > 1);

                    $('[data-stat="late-names"]').removeClass('animate-pulse')
                        .text(stats.late.length ? stats.late.join(', ') : '{{ __('Tiada') }}');

                    $('[data-stat="total-label"]').removeClass('animate-pulse')
                        .text(stats.total + ' ' + '{{ __('sesi') }}');
                    $('[data-stat="total-breakdown"]').removeClass('animate-pulse')
                        .text('{{ __('Pagi') }} ' + stats.pagi + ' · {{ __('Petang') }} ' + stats.petang);
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

                $('#sesi-majlis-table').on('click', '.js-edit-sesi-majlis', function () {
                    const $btn = $(this);
                    $('#edit-sesi-majlis-id').val($btn.data('id'));
                    $('#edit-sesi').val($btn.data('sesi'));
                    $('#edit-is_active').prop('checked', String($btn.data('is_active')) === '1');
                    $('#edit-is_late').prop('checked', String($btn.data('is_late')) === '1');
                    $('#edit-countdown_start_late').val($btn.attr('data-countdown-start-late') ?? '');
                    $('#edit-seat_offset').val($btn.attr('data-seat-offset') ?? '0');
                    $('#edit-s_kehadiran').val(String($btn.attr('data-s-kehadiran') ?? '0'));
                    openModal('edit-modal');
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

                $('.js-event-mode').on('change', function () {
                    const $radio = $(this);
                    const mode = $radio.val();
                    const previous = currentEventMode;

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
                            $('.js-event-mode').prop('checked', false);
                            $('.js-event-mode[value="' + previous + '"]').prop('checked', true);
                            showError(xhr, '{{ __('Ralat mengemaskini mod acara') }}');
                        })
                        .always(function () {
                            $('#event-mode-choice').prop('disabled', false).css('opacity', '');
                            $('#event-mode-spinner').addClass('hidden');
                        });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
