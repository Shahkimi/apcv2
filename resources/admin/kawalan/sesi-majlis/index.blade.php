@php
    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
@endphp

<x-dashboard-layout :title="__('Sesi Majlis')" role="admin">
    <div
        x-data="{ tab: 'sesi' }"
        x-init="$watch('tab', v => { if (v === 'sesi') { requestAnimationFrame(() => $('#sesi-majlis-table').DataTable().columns.adjust()); } })"
    >
        <div role="tablist" class="mb-6 inline-flex items-center gap-1 rounded-lg bg-muted p-1">
            <button
                type="button"
                role="tab"
                x-on:click="tab = 'sesi'"
                :aria-selected="tab === 'sesi'"
                @class([
                    'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                ])
                x-bind:class="tab === 'sesi' ? 'bg-foreground text-background' : 'text-muted-foreground hover:text-foreground'"
            >
                <i class="ri-calendar-event-line"></i>
                {{ __('Sesi Majlis') }}
            </button>
            <button
                type="button"
                role="tab"
                x-on:click="tab = 'mod'"
                :aria-selected="tab === 'mod'"
                class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                x-bind:class="tab === 'mod' ? 'bg-foreground text-background' : 'text-muted-foreground hover:text-foreground'"
            >
                <i class="ri-toggle-line"></i>
                {{ __('Mod Acara') }}
            </button>
        </div>

        <div x-show="tab === 'sesi'">
            <x-crud-header
                :title="__('Sesi Majlis')"
                :description="__('Urus sesi majlis: aktif, lewat, mula kira detik, dan offset kerusi untuk pengiraan no. meja.')"
                :create-label="__('Tambah sesi')"
            />

            <x-data-table
                table-id="sesi-majlis-table"
                :columns="['ID', __('Sesi'), __('Aktif'), __('Lewat'), __('Mula kira detik'), __('Offset kerusi'), __('Jenis kehadiran'), __('Dicipta'), __('Tindakan')]"
            />
        </div>

        <div x-show="tab === 'mod'" x-cloak>
            <div class="rounded-xl border border-border bg-card p-6">
                <h2 class="text-lg font-semibold text-foreground">{{ __('Mod Acara') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ __('Pilih acara yang sedang berlangsung. Tukaran ini akan menukar medan dan paparan yang berkaitan di seluruh sistem tanpa memadam sebarang data.') }}
                </p>

                <fieldset id="event-mode-choice" class="mt-6 grid gap-3 sm:grid-cols-2">
                    <legend class="sr-only">{{ __('Mod Acara') }}</legend>
                    @foreach ($eventModeOptions as $value => $label)
                        <label
                            @class([
                                'flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors',
                                'border-primary bg-primary/5' => $eventMode === $value,
                                'border-border hover:bg-muted/50' => $eventMode !== $value,
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
                                <span class="mt-1 block text-xs text-muted-foreground">
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

    <div class="modal-backdrop"></div>

    <x-crud-modal modal-id="create-modal" :title="__('Tambah sesi majlis')">
        <form id="create-sesi-majlis-form" class="space-y-4">
            @csrf
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="create-sesi">{{ __('Sesi') }}</label>
                <input id="create-sesi" type="text" name="sesi" required class="{{ $inputClass }}" />
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0" />
                <input
                    id="create-is_active"
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                />
                <label class="text-sm font-medium leading-none" for="create-is_active">{{ __('Aktif') }}</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_late" value="0" />
                <input
                    id="create-is_late"
                    type="checkbox"
                    name="is_late"
                    value="1"
                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                />
                <label class="text-sm font-medium leading-none" for="create-is_late">{{ __('Lewat') }}</label>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="create-countdown_start_late">{{ __('Mula kira detik') }}</label>
                <input
                    id="create-countdown_start_late"
                    type="number"
                    name="countdown_start_late"
                    min="0"
                    step="1"
                    class="{{ $inputClass }}"
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="create-seat_offset">{{ __('Offset kerusi') }}</label>
                <input
                    id="create-seat_offset"
                    type="number"
                    name="seat_offset"
                    min="0"
                    step="1"
                    value="0"
                    required
                    class="{{ $inputClass }}"
                />
                <p class="text-xs text-muted-foreground">
                    {{ __('Nombor asas untuk sesi ini: 0 bermaksud kerusi bermula 1; 700 bermaksud kerusi 701+ dikira meja 1, 2, …') }}
                </p>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="create-s_kehadiran">{{ __('Jenis kehadiran') }}</label>
                <select id="create-s_kehadiran" name="s_kehadiran" required class="{{ $inputClass }}">
                    <option value="0">{{ __('Pagi') }}</option>
                    <option value="1">{{ __('Petang') }}</option>
                </select>
                <p class="text-xs text-muted-foreground">
                    {{ __('Mestilah sepadan dengan jenis sesi pegawai (pagi/petang) untuk pengesahan kehadiran.') }}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                <button type="submit" class="btn">{{ __('Simpan') }}</button>
            </div>
        </form>
    </x-crud-modal>

    <x-crud-modal modal-id="edit-modal" :title="__('Edit sesi majlis')">
        <form id="edit-sesi-majlis-form" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit-sesi-majlis-id" />
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="edit-sesi">{{ __('Sesi') }}</label>
                <input id="edit-sesi" type="text" name="sesi" required class="{{ $inputClass }}" />
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0" />
                <input
                    id="edit-is_active"
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                />
                <label class="text-sm font-medium leading-none" for="edit-is_active">{{ __('Aktif') }}</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_late" value="0" />
                <input
                    id="edit-is_late"
                    type="checkbox"
                    name="is_late"
                    value="1"
                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                />
                <label class="text-sm font-medium leading-none" for="edit-is_late">{{ __('Lewat') }}</label>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="edit-countdown_start_late">{{ __('Mula kira detik') }}</label>
                <input
                    id="edit-countdown_start_late"
                    type="number"
                    name="countdown_start_late"
                    min="0"
                    step="1"
                    class="{{ $inputClass }}"
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="edit-seat_offset">{{ __('Offset kerusi') }}</label>
                <input
                    id="edit-seat_offset"
                    type="number"
                    name="seat_offset"
                    min="0"
                    step="1"
                    required
                    class="{{ $inputClass }}"
                />
                <p class="text-xs text-muted-foreground">
                    {{ __('Nombor asas untuk sesi ini: 0 bermaksud kerusi bermula 1; 700 bermaksud kerusi 701+ dikira meja 1, 2, …') }}
                </p>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="edit-s_kehadiran">{{ __('Jenis kehadiran') }}</label>
                <select id="edit-s_kehadiran" name="s_kehadiran" required class="{{ $inputClass }}">
                    <option value="0">{{ __('Pagi') }}</option>
                    <option value="1">{{ __('Petang') }}</option>
                </select>
                <p class="text-xs text-muted-foreground">
                    {{ __('Mestilah sepadan dengan jenis sesi pegawai (pagi/petang) untuk pengesahan kehadiran.') }}
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                <button type="submit" class="btn">{{ __('Kemas kini') }}</button>
            </div>
        </form>
    </x-crud-modal>

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
