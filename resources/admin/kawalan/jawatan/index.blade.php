@php
    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
@endphp

<x-dashboard-layout :title="__('Jawatan')" role="admin">
    <x-crud-header
        :title="__('Jawatan')"
        :description="__('Urus jawatan.')"
        :create-label="__('Tambah jawatan')"
    />

    <x-data-table table-id="jawatan-table" :columns="['ID', __('Perihal jawatan'), __('Dicipta'), __('Tindakan')]" />

    <div class="modal-backdrop"></div>

    <x-crud-modal modal-id="create-modal" :title="__('Tambah jawatan')">
        <form id="create-jawatan-form" class="space-y-4">
            @csrf
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="create-desc_jawatan">{{ __('Perihal jawatan') }}</label>
                <input id="create-desc_jawatan" type="text" name="desc_jawatan" required class="{{ $inputClass }}" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                <button type="submit" class="btn">{{ __('Simpan') }}</button>
            </div>
        </form>
    </x-crud-modal>

    <x-crud-modal modal-id="edit-modal" :title="__('Edit jawatan')">
        <form id="edit-jawatan-form" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="jawatan_id" id="edit-jawatan-id" />
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="edit-desc_jawatan">{{ __('Perihal jawatan') }}</label>
                <input id="edit-desc_jawatan" type="text" name="desc_jawatan" required class="{{ $inputClass }}" />
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
                const table = $('#jawatan-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.kawalan.jawatan.datatable') }}',
                    columnDefs: [{ targets: -1, className: 'text-right' }],
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'desc_jawatan', name: 'desc_jawatan' },
                        { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });

                $('#create-jawatan-form').on('submit', function (e) {
                    e.preventDefault();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ route('admin.kawalan.jawatan.store') }}',
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

                $('#jawatan-table').on('click', '.js-edit-jawatan', function () {
                    const $btn = $(this);
                    $('#edit-jawatan-id').val($btn.data('id'));
                    $('#edit-desc_jawatan').val($btn.data('desc_jawatan'));
                    openModal('edit-modal');
                });

                $('#edit-jawatan-form').on('submit', function (e) {
                    e.preventDefault();
                    const id = $('#edit-jawatan-id').val();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ url('/admin/kawalan/jawatan') }}/' + id,
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

                $('#jawatan-table').on('click', '.js-delete-jawatan', function () {
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
                            url: '{{ url('/admin/kawalan/jawatan') }}/' + id,
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
