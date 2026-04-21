@php
    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
@endphp

<x-dashboard-layout :title="__('Gred')" role="admin">
    <x-crud-header
        :title="__('Gred')"
        :description="__('Urus gred.')"
        :create-label="__('Tambah gred')"
    />

    <x-data-table table-id="gred-table" :columns="['ID', __('Perihal gred'), __('Dicipta'), __('Tindakan')]" />

    <div class="modal-backdrop"></div>

    <x-crud-modal modal-id="create-modal" :title="__('Tambah gred')">
        <form id="create-gred-form" class="space-y-4">
            @csrf
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="create-desc_gred">{{ __('Perihal gred') }}</label>
                <input id="create-desc_gred" type="text" name="desc_gred" required class="{{ $inputClass }}" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                <button type="submit" class="btn">{{ __('Simpan') }}</button>
            </div>
        </form>
    </x-crud-modal>

    <x-crud-modal modal-id="edit-modal" :title="__('Edit gred')">
        <form id="edit-gred-form" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="gred_id" id="edit-gred-id" />
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="edit-desc_gred">{{ __('Perihal gred') }}</label>
                <input id="edit-desc_gred" type="text" name="desc_gred" required class="{{ $inputClass }}" />
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
                const table = $('#gred-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.kawalan.gred.datatable') }}',
                    columnDefs: [{ targets: -1, className: 'text-right' }],
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'desc_gred', name: 'desc_gred' },
                        { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });

                $('#create-gred-form').on('submit', function (e) {
                    e.preventDefault();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ route('admin.kawalan.gred.store') }}',
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

                $('#gred-table').on('click', '.js-edit-gred', function () {
                    const $btn = $(this);
                    $('#edit-gred-id').val($btn.data('id'));
                    $('#edit-desc_gred').val($btn.data('desc_gred'));
                    openModal('edit-modal');
                });

                $('#edit-gred-form').on('submit', function (e) {
                    e.preventDefault();
                    const id = $('#edit-gred-id').val();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ url('/admin/kawalan/gred') }}/' + id,
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

                $('#gred-table').on('click', '.js-delete-gred', function () {
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
                            url: '{{ url('/admin/kawalan/gred') }}/' + id,
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
