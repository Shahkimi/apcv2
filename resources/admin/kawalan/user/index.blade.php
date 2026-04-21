@php
    use App\Models\User;

    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
    $selectClass = $inputClass;
@endphp

<x-dashboard-layout :title="__('Pengguna')" role="admin">
    <x-crud-header
        :title="__('Pengguna')"
        :description="__('Urus akaun pengguna sistem.')"
        :create-label="__('Tambah pengguna')"
    />

    <x-data-table
        table-id="user-table"
        :columns="['ID', __('Nama'), __('Nama pengguna'), __('Peranan'), __('Dicipta'), __('Tindakan')]"
    />

    <div class="modal-backdrop"></div>

    <x-crud-modal modal-id="create-modal" size="modal-xl" :title="__('Tambah pengguna')">
        <form id="create-user-form" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2 sm:col-span-2">
                    <label class="text-sm font-medium leading-none" for="create-name">{{ __('Nama') }}</label>
                    <input id="create-name" type="text" name="name" required class="{{ $inputClass }}" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="create-username">{{ __('Nama pengguna') }}</label>
                    <input id="create-username" type="text" name="username" required class="{{ $inputClass }}" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="create-role">{{ __('Peranan') }}</label>
                    <select id="create-role" name="role" required class="{{ $selectClass }}">
                        <option value="{{ User::ROLE_USER }}">{{ __('Pengguna') }}</option>
                        <option value="{{ User::ROLE_MEDIA }}">{{ __('Media') }}</option>
                        <option value="{{ User::ROLE_ADMIN }}">{{ __('Admin') }}</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="create-password">{{ __('Kata laluan') }}</label>
                    <input id="create-password" type="password" name="password" required class="{{ $inputClass }}" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="create-password_confirmation">{{ __('Sahkan kata laluan') }}</label>
                    <input
                        id="create-password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        class="{{ $inputClass }}"
                    />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                <button type="submit" class="btn">{{ __('Simpan') }}</button>
            </div>
        </form>
    </x-crud-modal>

    <x-crud-modal modal-id="edit-modal" size="modal-xl" :title="__('Edit pengguna')">
        <form id="edit-user-form" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit-user-id" />
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2 sm:col-span-2">
                    <label class="text-sm font-medium leading-none" for="edit-name">{{ __('Nama') }}</label>
                    <input id="edit-name" type="text" name="name" required class="{{ $inputClass }}" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="edit-username">{{ __('Nama pengguna') }}</label>
                    <input id="edit-username" type="text" name="username" required class="{{ $inputClass }}" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="edit-role">{{ __('Peranan') }}</label>
                    <select id="edit-role" name="role" required class="{{ $selectClass }}">
                        <option value="{{ User::ROLE_USER }}">{{ __('Pengguna') }}</option>
                        <option value="{{ User::ROLE_MEDIA }}">{{ __('Media') }}</option>
                        <option value="{{ User::ROLE_ADMIN }}">{{ __('Admin') }}</option>
                    </select>
                </div>
                <div class="space-y-2 sm:col-span-2 text-sm text-muted-foreground">
                    {{ __('Kosongkan kata laluan untuk mengekalkan kata laluan sedia ada.') }}
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="edit-password">{{ __('Kata laluan baharu') }}</label>
                    <input id="edit-password" type="password" name="password" class="{{ $inputClass }}" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium leading-none" for="edit-password_confirmation">{{ __('Sahkan kata laluan') }}</label>
                    <input
                        id="edit-password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="{{ $inputClass }}"
                    />
                </div>
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
                const table = $('#user-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.kawalan.user.datatable') }}',
                    columnDefs: [
                        { targets: 1, className: 'font-medium text-foreground' },
                        { targets: 4, className: 'text-muted-foreground' },
                        { targets: -1, className: 'text-right' },
                    ],
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'name', name: 'name' },
                        { data: 'username', name: 'username' },
                        { data: 'role_label', name: 'role', orderable: false, searchable: false },
                        { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });

                $('#create-user-form').on('submit', function (e) {
                    e.preventDefault();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ route('admin.kawalan.user.store') }}',
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

                $('#user-table').on('click', '.js-edit-user', function () {
                    const $btn = $(this);
                    $('#edit-user-id').val($btn.data('id'));
                    $('#edit-name').val($btn.data('name'));
                    $('#edit-username').val($btn.data('username'));
                    $('#edit-role').val(String($btn.data('role')));
                    $('#edit-password').val('');
                    $('#edit-password_confirmation').val('');
                    openModal('edit-modal');
                });

                $('#edit-user-form').on('submit', function (e) {
                    e.preventDefault();
                    const id = $('#edit-user-id').val();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ url('/admin/kawalan/user') }}/' + id,
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

                $('#user-table').on('click', '.js-delete-user', function () {
                    const id = $(this).data('id');
                    window.kawalanConfirmDelete({
                        title: '{{ __('Padam pengguna ini?') }}',
                        text: '{{ __('Tindakan ini tidak boleh dibuat asal.') }}',
                        confirmButtonText: '{{ __('Padam') }}',
                        cancelButtonText: '{{ __('Batal') }}',
                    }).then(function (ok) {
                        if (!ok) {
                            return;
                        }
                        $.ajax({
                            url: '{{ url('/admin/kawalan/user') }}/' + id,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
                            headers: { Accept: 'application/json' },
                        })
                            .done(function () {
                                table.ajax.reload(null, false);
                            })
                            .fail(function (xhr) {
                                if (xhr.status === 403) {
                                    alert('{{ __('Tidak dibenarkan') }}');
                                } else {
                                    alert('{{ __('Ralat') }}');
                                }
                            });
                    });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
