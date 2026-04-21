@php
    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
@endphp

<x-dashboard-layout :title="__('PTJ')" role="admin">
    <x-crud-header
        :title="__('PTJ')"
        :description="__('Urus PTJ (Pusat Tanggungjawab).')"
        :create-label="__('Tambah PTJ')"
    />

    <x-data-table table-id="ptj-table" :columns="['ID', __('Nama PTJ'), __('Dicipta'), __('Tindakan')]" />

    <div class="modal-backdrop"></div>

    <x-crud-modal modal-id="create-modal" :title="__('Tambah PTJ')">
        <form id="create-ptj-form" class="space-y-4">
            @csrf
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="create-nama_ptj">{{ __('Nama PTJ') }}</label>
                <input id="create-nama_ptj" type="text" name="nama_ptj" required class="{{ $inputClass }}" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                <button type="submit" class="btn">{{ __('Simpan') }}</button>
            </div>
        </form>
    </x-crud-modal>

    <x-crud-modal modal-id="edit-modal" :title="__('Edit PTJ')">
        <form id="edit-ptj-form" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="ptj_id" id="edit-ptj-id" />
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none" for="edit-nama_ptj">{{ __('Nama PTJ') }}</label>
                <input id="edit-nama_ptj" type="text" name="nama_ptj" required class="{{ $inputClass }}" />
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
                const table = $('#ptj-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.kawalan.ptj.datatable') }}',
                    columnDefs: [{ targets: -1, className: 'text-right' }],
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'nama_ptj', name: 'nama_ptj' },
                        { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });

                $('#create-ptj-form').on('submit', function (e) {
                    e.preventDefault();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ route('admin.kawalan.ptj.store') }}',
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

                $('#ptj-table').on('click', '.js-edit-ptj', function () {
                    const $btn = $(this);
                    $('#edit-ptj-id').val($btn.data('id'));
                    $('#edit-nama_ptj').val($btn.data('nama_ptj'));
                    openModal('edit-modal');
                });

                $('#edit-ptj-form').on('submit', function (e) {
                    e.preventDefault();
                    const id = $('#edit-ptj-id').val();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ url('/admin/kawalan/ptj') }}/' + id,
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

                $('#ptj-table').on('click', '.js-delete-ptj', function () {
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
                            url: '{{ url('/admin/kawalan/ptj') }}/' + id,
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
