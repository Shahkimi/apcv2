@php
    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
@endphp

<x-dashboard-layout :title="__('Sesi Majlis')" role="admin">
    <x-crud-header
        :title="__('Sesi Majlis')"
        :description="__('Urus sesi majlis dan status aktif.')"
        :create-label="__('Tambah sesi')"
    />

    <x-data-table
        table-id="sesi-majlis-table"
        :columns="['ID', __('Sesi'), __('Aktif'), __('On Air'), __('Lewat'), __('Mula kira detik'), __('Dicipta'), __('Tindakan')]"
    />

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
                <input type="hidden" name="is_on_air" value="0" />
                <input
                    id="create-is_on_air"
                    type="checkbox"
                    name="is_on_air"
                    value="1"
                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                />
                <label class="text-sm font-medium leading-none" for="create-is_on_air">{{ __('On Air') }}</label>
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
                <label class="text-sm font-medium leading-none" for="create-countdown_start">{{ __('Mula kira detik') }}</label>
                <input
                    id="create-countdown_start"
                    type="number"
                    name="countdown_start"
                    min="0"
                    step="1"
                    class="{{ $inputClass }}"
                />
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
                <input type="hidden" name="is_on_air" value="0" />
                <input
                    id="edit-is_on_air"
                    type="checkbox"
                    name="is_on_air"
                    value="1"
                    class="h-4 w-4 rounded border-border text-primary focus:ring-primary"
                />
                <label class="text-sm font-medium leading-none" for="edit-is_on_air">{{ __('On Air') }}</label>
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
                <label class="text-sm font-medium leading-none" for="edit-countdown_start">{{ __('Mula kira detik') }}</label>
                <input
                    id="edit-countdown_start"
                    type="number"
                    name="countdown_start"
                    min="0"
                    step="1"
                    class="{{ $inputClass }}"
                />
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
                        { targets: 5, className: 'text-muted-foreground tabular-nums' },
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
                            data: 'is_on_air_label',
                            name: 'is_on_air',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'is_late_label',
                            name: 'is_late',
                            orderable: false,
                            searchable: false,
                        },
                        { data: 'countdown_start', name: 'countdown_start' },
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
                            $('#create-is_on_air').prop('checked', false);
                            $('#create-is_late').prop('checked', false);
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
                    $('#edit-is_on_air').prop('checked', String($btn.data('is_on_air')) === '1');
                    $('#edit-is_late').prop('checked', String($btn.data('is_late')) === '1');
                    $('#edit-countdown_start').val($btn.data('countdown_start') ?? '');
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
            });
        </script>
    @endpush
</x-dashboard-layout>
