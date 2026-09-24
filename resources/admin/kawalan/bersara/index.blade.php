@php
    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
@endphp

<x-dashboard-layout :title="__('Bersara')" role="admin">
    <x-crud-header
        :title="__('Bersara')"
        :description="__('Urus jenis persaraan untuk mod acara Jasamu Dikenang.')"
        :create-label="__('Tambah jenis persaraan')"
    />

    <x-data-table table-id="bersara-table" :columns="['ID', __('Jenis persaraan'), __('Dicipta'), __('Tindakan')]" />

    <div class="modal-backdrop"></div>

    <x-crud-modal modal-id="create-modal" :title="__('Tambah jenis persaraan')">
        <form id="create-bersara-form" class="space-y-4">
            @csrf
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="create-jenis_bersara">{{ __('Jenis persaraan') }}</label>
                <input id="create-jenis_bersara" type="text" name="jenis_bersara" required class="{{ $inputClass }}" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                <button type="submit" class="btn">{{ __('Simpan') }}</button>
            </div>
        </form>
    </x-crud-modal>

    <x-crud-modal modal-id="edit-modal" :title="__('Edit jenis persaraan')">
        <form id="edit-bersara-form" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="bersara_id" id="edit-bersara-id" />
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="edit-jenis_bersara">{{ __('Jenis persaraan') }}</label>
                <input id="edit-jenis_bersara" type="text" name="jenis_bersara" required class="{{ $inputClass }}" />
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
                const table = $('#bersara-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.kawalan.bersara.datatable') }}',
                    columnDefs: [{ targets: -1, className: 'text-right' }],
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'jenis_bersara', name: 'jenis_bersara' },
                        { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });

                $('#create-bersara-form').on('submit', function (e) {
                    e.preventDefault();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ route('admin.kawalan.bersara.store') }}',
                        method: 'POST',
                        data: $form.serialize(),
                        headers: { Accept: 'application/json' },
                    })
                        .done(function () {
                            closeModal('create-modal');
                            $form.trigger('reset');
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

                $('#bersara-table').on('click', '.js-edit-bersara', function () {
                    const $btn = $(this);
                    $('#edit-bersara-id').val($btn.data('id'));
                    $('#edit-jenis_bersara').val($btn.data('jenis_bersara'));
                    openModal('edit-modal');
                });

                $('#edit-bersara-form').on('submit', function (e) {
                    e.preventDefault();
                    const id = $('#edit-bersara-id').val();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ url('/admin/kawalan/bersara') }}/' + id,
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

                $('#bersara-table').on('click', '.js-delete-bersara', function () {
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
                            url: '{{ url('/admin/kawalan/bersara') }}/' + id,
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
