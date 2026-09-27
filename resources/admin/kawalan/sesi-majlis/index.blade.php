@php
    $inputClass =
        'min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-base tabular-nums text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0 sm:text-sm';
    $fieldShell =
        'flex h-12 overflow-hidden rounded-xl border border-input/90 bg-background shadow-sm ring-1 ring-border/30 transition-[box-shadow,ring-color,border-color] focus-within:border-ring focus-within:shadow-md focus-within:ring-2 focus-within:ring-ring dark:bg-background/80';
    $toggleTrackClass =
        'peer h-5 w-9 rounded-full bg-muted ring-1 ring-border/60 transition-all after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-background after:shadow-sm after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-ring peer-focus-visible:ring-offset-2';
@endphp

<x-dashboard-layout :title="__('Sesi Majlis')" role="admin">
    <x-kawalan-shell>
        <x-crud-header
            :title="__('Sesi Majlis')"
            :description="__('Urus sesi majlis: aktif, lewat, mula kira detik, dan offset kerusi untuk pengiraan no. meja.')"
            :create-label="__('Tambah sesi')"
        />

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_min(18.5rem,100%)] lg:gap-6">
            <div
                class="relative overflow-hidden rounded-2xl border border-border/70 bg-gradient-to-br from-card via-card to-primary/[0.06] p-5 shadow-sm ring-1 ring-border/40 sm:p-6 dark:from-card dark:via-card dark:to-primary/[0.09]"
            >
                <div
                    class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-primary/10 blur-2xl dark:bg-primary/15"
                    aria-hidden="true"
                ></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-5">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-md shadow-primary/25"
                        aria-hidden="true"
                    >
                        <i class="ri-calendar-event-line text-2xl leading-none"></i>
                    </span>
                    <div class="min-w-0 space-y-2">
                        <h2 class="text-base font-semibold tracking-tight text-foreground sm:text-lg">
                            {{ __('Cara sesi majlis digunakan') }}
                        </h2>
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            {{ __('Setiap sesi menetapkan offset kerusi asas untuk pengiraan no. meja: 0 bermaksud kerusi bermula 1; 700 bermaksud kerusi 701+ dikira meja 1, 2, dan seterusnya.') }}
                        </p>
                    </div>
                </div>
            </div>

            <aside class="flex flex-col gap-3" aria-label="{{ __('Ringkasan') }}">
                <div
                    class="flex items-start gap-3 rounded-xl border border-border/60 bg-muted/20 px-4 py-3 dark:bg-muted/10"
                >
                    <span class="mt-0.5 text-primary" aria-hidden="true"><i class="ri-flashlight-line text-lg"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-foreground">
                            {{ __('Satu sesi aktif') }}
                        </p>
                        <p class="mt-0.5 text-xs leading-snug text-muted-foreground">
                            {{ __('Pastikan hanya satu sesi ditanda aktif pada satu masa.') }}
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-start gap-3 rounded-xl border border-border/60 bg-muted/20 px-4 py-3 dark:bg-muted/10"
                >
                    <span class="mt-0.5 text-primary" aria-hidden="true"><i class="ri-timer-flash-line text-lg"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-foreground">
                            {{ __('Sesi lewat') }}
                        </p>
                        <p class="mt-0.5 text-xs leading-snug text-muted-foreground">
                            {{ __('Menutup sesi lewat akan mengagihkan no. panggilan lewat secara automatik.') }}
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-start gap-3 rounded-xl border border-border/60 bg-muted/20 px-4 py-3 dark:bg-muted/10"
                >
                    <span class="mt-0.5 text-primary" aria-hidden="true"><i class="ri-sun-line text-lg"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-foreground">
                            {{ __('Jenis kehadiran') }}
                        </p>
                        <p class="mt-0.5 text-xs leading-snug text-muted-foreground">
                            {{ __('Mestilah sepadan dengan sesi pegawai (pagi/petang) untuk pengesahan kehadiran.') }}
                        </p>
                    </div>
                </div>
            </aside>
        </div>

        <div
            class="relative overflow-hidden rounded-2xl border border-border/70 bg-card p-5 shadow-sm ring-1 ring-border/40 sm:p-6"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-5">
                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-md shadow-primary/25"
                    aria-hidden="true"
                >
                    <i class="ri-toggle-line text-2xl leading-none"></i>
                </span>
                <div class="min-w-0 flex-1 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight text-foreground sm:text-lg">
                            {{ __('Mod Acara') }}
                        </h2>
                        <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                            {{ __('Pilih acara yang sedang berlangsung. Tukaran ini akan menukar medan dan paparan yang berkaitan di seluruh sistem tanpa memadam sebarang data.') }}
                        </p>
                    </div>

                    <fieldset id="event-mode-choice" class="grid gap-3 sm:grid-cols-2">
                        <legend class="sr-only">{{ __('Mod Acara') }}</legend>
                        @foreach ($eventModeOptions as $value => $label)
                            <label
                                @class([
                                    'flex cursor-pointer items-start gap-3 rounded-xl border p-4 shadow-sm transition-colors',
                                    'border-primary bg-primary/5 ring-1 ring-primary/20' => $eventMode === $value,
                                    'border-border/60 bg-muted/10 hover:bg-muted/20' => $eventMode !== $value,
                                ])
                            >
                                <input
                                    type="radio"
                                    name="event_mode"
                                    value="{{ $value }}"
                                    class="js-event-mode mt-1 h-4 w-4 border-border text-primary focus:ring-primary"
                                    @checked($eventMode === $value)
                                />
                                <span>
                                    <span class="block text-sm font-semibold text-foreground">{{ $label }}</span>
                                    <span class="mt-1 block text-xs leading-snug text-muted-foreground">
                                        {{ $value === 'jasamu'
                                            ? __('Majlis persaraan: memaparkan tarikh bersara, jenis persaraan dan tempoh berkhidmat.')
                                            : __('Majlis anugerah: memaparkan PTJ pegawai seperti biasa.') }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                </div>
            </div>
        </div>

        <x-data-table
            table-id="sesi-majlis-table"
            :columns="['ID', __('Sesi'), __('Aktif'), __('Lewat'), __('Mula kira detik'), __('Offset kerusi'), __('Jenis kehadiran'), __('Dicipta'), __('Tindakan')]"
        />

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
                    <button type="submit" class="btn">{{ __('Simpan') }}</button>
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
                    <button type="submit" class="btn">{{ __('Kemas kini') }}</button>
                </div>
            </form>
        </x-crud-modal>
    </x-kawalan-shell>

    @push('scripts')
        <script>
            $(function () {
                const table = $('#sesi-majlis-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.kawalan.sesi-majlis.datatable') }}',
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

                $('#create-sesi-majlis-form').on('submit', function (e) {
                    e.preventDefault();
                    const $form = $(this);
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
                        })
                        .fail(function (xhr) {
                            if (xhr.status === 422) {
                                alert(Object.values(xhr.responseJSON.errors).flat().join('\n'));
                            } else {
                                alert('{{ __('Ralat') }}');
                            }
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
                    $.ajax({
                        url: '{{ url('/admin/kawalan/sesi-majlis') }}/' + id,
                        method: 'POST',
                        data: $form.serialize(),
                        headers: { Accept: 'application/json' },
                    })
                        .done(function () {
                            closeModal('edit-modal');
                            table.ajax.reload(null, false);
                        })
                        .fail(function (xhr) {
                            if (xhr.status === 422) {
                                alert(Object.values(xhr.responseJSON.errors).flat().join('\n'));
                            } else {
                                alert('{{ __('Ralat') }}');
                            }
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
                            })
                            .fail(function () {
                                alert('{{ __('Ralat') }}');
                            });
                    });
                });

                let currentEventMode = '{{ $eventMode }}';

                $('.js-event-mode').on('change', function () {
                    const $radio = $(this);
                    const mode = $radio.val();
                    const previous = currentEventMode;

                    $.ajax({
                        url: '{{ route('admin.kawalan.sesi-majlis.event-mode') }}',
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}', mode: mode },
                        headers: { Accept: 'application/json' },
                    })
                        .done(function () {
                            currentEventMode = mode;
                            window.Swal?.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: '{{ __('Mod acara dikemas kini') }}',
                                showConfirmButton: false,
                                timer: 2000,
                            });
                        })
                        .fail(function () {
                            $('.js-event-mode').prop('checked', false);
                            $('.js-event-mode[value="' + previous + '"]').prop('checked', true);
                            alert('{{ __('Ralat mengemaskini mod acara') }}');
                        });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
