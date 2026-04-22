@php
    $inputClass =
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2';
    $dropZoneClass =
        'flex flex-col items-center justify-center w-full min-h-[11rem] border-2 border-dashed border-border rounded-xl cursor-pointer bg-muted/30 hover:bg-muted/45 hover:border-primary/55 transition-all duration-200 ring-offset-background focus-within:ring-2 focus-within:ring-ring select-none';
@endphp

<x-dashboard-layout :title="__('Backdrop')" role="admin">
    <x-crud-header
        :title="__('Backdrop')"
        :description="__('Urus imej latar untuk paparan.')"
        :create-label="__('Tambah backdrop')"
    />

    <x-data-table
        table-id="backdrop-table"
        :columns="['ID', __('Pratonton'), __('Nama'), __('Aktif'), __('Tindakan')]"
    />

    <div class="modal-backdrop"></div>

    <x-crud-modal modal-id="create-modal" :title="__('Tambah backdrop')">
        <form id="create-backdrop-form" class="space-y-5" enctype="multipart/form-data">
            @csrf
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none text-foreground" for="create-backdrop_name">{{ __('Nama backdrop') }}</label>
                <input
                    id="create-backdrop_name"
                    type="text"
                    name="backdrop_name"
                    required
                    class="{{ $inputClass }}"
                    placeholder="{{ __('Contoh: Latar utama majlis') }}"
                />
            </div>
            <div class="space-y-2">
                <span class="text-sm font-medium leading-none text-foreground">{{ __('Muat naik imej') }}</span>
                <label id="create-upload-zone" class="{{ $dropZoneClass }}">
                    <div class="flex flex-col items-center justify-center px-4 py-6">
                        <div class="mb-3 rounded-full bg-primary/10 p-4 ring-1 ring-primary/20">
                            <i class="ri-upload-cloud-2-line text-5xl text-primary"></i>
                        </div>
                        <p class="mb-1 text-base font-semibold text-foreground">
                            {{ __('Klik untuk muat naik') }}
                        </p>
                        <p class="mb-3 text-sm text-muted-foreground">
                            {{ __('atau seret dan lepas fail di sini') }}
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                            <span class="inline-flex items-center gap-1">
                                <i class="ri-file-image-line"></i>
                                PNG, JPG, GIF, WEBP
                            </span>
                            <span aria-hidden="true">•</span>
                            <span class="inline-flex items-center gap-1">
                                <i class="ri-file-line"></i>
                                {{ __('Maks.') }} 50MB
                            </span>
                        </div>
                    </div>
                    <input
                        type="file"
                        id="create-backdrop-file-input"
                        name="backdrop_file"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        class="hidden"
                        required
                    />
                </label>
                <div id="create-file-preview" class="mt-3 hidden">
                    <div class="relative overflow-hidden rounded-xl border-2 border-border bg-card shadow-md ring-1 ring-border/50">
                        <div class="relative bg-muted/40">
                            <img src="" alt="" class="h-48 w-full object-contain p-3" />
                            <div class="absolute right-2 top-2 flex flex-wrap justify-end gap-2">
                                <button
                                    type="button"
                                    class="js-change-image btn btn-sm btn-secondary shadow-md"
                                >
                                    <i class="ri-image-edit-line"></i>
                                    <span class="ml-1">{{ __('Tukar') }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="js-remove-image btn btn-sm btn-destructive shadow-md"
                                    title="{{ __('Buang imej') }}"
                                >
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                        <div class="border-t border-border bg-muted/40 px-3 py-2.5">
                            <div class="flex min-w-0 items-center justify-between gap-2 text-xs">
                                <span class="file-name truncate font-medium text-foreground"></span>
                                <span class="file-size shrink-0 tabular-nums text-muted-foreground"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                <button type="submit" class="btn">{{ __('Simpan') }}</button>
            </div>
        </form>
    </x-crud-modal>

    <x-crud-modal modal-id="edit-modal" :title="__('Edit backdrop')">
        <form id="edit-backdrop-form" class="space-y-5" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" id="edit-backdrop-id" />
            <div class="space-y-2">
                <label class="text-sm font-medium leading-none text-foreground" for="edit-backdrop_name">{{ __('Nama backdrop') }}</label>
                <input
                    id="edit-backdrop_name"
                    type="text"
                    name="backdrop_name"
                    required
                    class="{{ $inputClass }}"
                />
            </div>
            <div class="space-y-2">
                <span class="text-sm font-medium leading-none text-foreground">{{ __('Imej semasa') }}</span>
                <div
                    class="flex min-h-48 items-center justify-center rounded-xl border border-border bg-muted/20 p-3 shadow-sm ring-1 ring-border/40"
                >
                    <img
                        id="edit-current-image"
                        src=""
                        alt=""
                        class="max-h-44 w-full max-w-full rounded-lg object-contain"
                    />
                </div>
            </div>
            <div class="space-y-2">
                <span class="text-sm font-medium leading-none text-foreground">{{ __('Ganti imej') }} ({{ __('pilihan') }})</span>
                <label id="edit-upload-zone" class="{{ $dropZoneClass }}">
                    <div class="flex flex-col items-center justify-center px-4 py-6">
                        <div class="mb-3 rounded-full bg-primary/10 p-4 ring-1 ring-primary/20">
                            <i class="ri-upload-cloud-2-line text-5xl text-primary"></i>
                        </div>
                        <p class="mb-1 text-base font-semibold text-foreground">
                            {{ __('Klik untuk muat naik') }}
                        </p>
                        <p class="mb-3 text-sm text-muted-foreground">
                            {{ __('atau seret dan lepas fail di sini') }}
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-xs text-muted-foreground">
                            <span class="inline-flex items-center gap-1">
                                <i class="ri-file-image-line"></i>
                                PNG, JPG, GIF, WEBP
                            </span>
                            <span aria-hidden="true">•</span>
                            <span class="inline-flex items-center gap-1">
                                <i class="ri-file-line"></i>
                                {{ __('Maks.') }} 50MB
                            </span>
                        </div>
                    </div>
                    <input
                        type="file"
                        id="edit-backdrop-file-input"
                        name="backdrop_file"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        class="hidden"
                    />
                </label>
                <div id="edit-file-preview" class="mt-3 hidden">
                    <p class="mb-2 text-xs font-medium text-muted-foreground">{{ __('Pratonton fail baharu') }}</p>
                    <div class="relative overflow-hidden rounded-xl border-2 border-border bg-card shadow-md ring-1 ring-border/50">
                        <div class="relative bg-muted/40">
                            <img src="" alt="" class="h-48 w-full object-contain p-3" />
                            <div class="absolute right-2 top-2 flex flex-wrap justify-end gap-2">
                                <button
                                    type="button"
                                    class="js-change-image btn btn-sm btn-secondary shadow-md"
                                >
                                    <i class="ri-image-edit-line"></i>
                                    <span class="ml-1">{{ __('Tukar') }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="js-remove-image btn btn-sm btn-destructive shadow-md"
                                    title="{{ __('Buang imej') }}"
                                >
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                        <div class="border-t border-border bg-muted/40 px-3 py-2.5">
                            <div class="flex min-w-0 items-center justify-between gap-2 text-xs">
                                <span class="file-name truncate font-medium text-foreground"></span>
                                <span class="file-size shrink-0 tabular-nums text-muted-foreground"></span>
                            </div>
                        </div>
                    </div>
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
                function formatFileSize(bytes) {
                    if (!bytes || bytes === 0) {
                        return '0 B';
                    }
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return (Math.round((bytes / Math.pow(k, i)) * 100) / 100) + ' ' + sizes[i];
                }

                function previewFile(file, $img) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        $img.attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                }

                function assignFileToInput(input, file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                }

                const dragActiveClass = 'border-primary bg-primary/10 scale-[1.02]';

                function bindDropZone($zone, $input, $previewWrap) {
                    const $previewImg = $previewWrap.find('img');
                    const $fileName = $previewWrap.find('.file-name');
                    const $fileSize = $previewWrap.find('.file-size');

                    function showPreview(file) {
                        previewFile(file, $previewImg);
                        $fileName.text(file.name);
                        $fileSize.text(formatFileSize(file.size));
                        $previewWrap.removeClass('hidden').hide().stop(true, true).fadeIn(200);
                        $zone.addClass('hidden');
                    }

                    function hidePreview(instant) {
                        $zone.removeClass('hidden');
                        $input.val('');
                        const done = function () {
                            $previewWrap.addClass('hidden').css({ display: '', opacity: '' });
                            $previewImg.attr('src', '');
                            $fileName.text('');
                            $fileSize.text('');
                        };
                        if (instant) {
                            $previewWrap.stop(true, true);
                            done();
                        } else {
                            $previewWrap.stop(true, true).fadeOut(200, done);
                        }
                    }

                    $previewWrap.on('click', '.js-remove-image', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        hidePreview(false);
                    });

                    $previewWrap.on('click', '.js-change-image', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $input.trigger('click');
                    });

                    $input.on('change', function () {
                        const file = this.files[0];
                        if (file && file.type.startsWith('image/')) {
                            showPreview(file);
                        }
                    });

                    $zone.on('dragover dragenter', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $(this).addClass(dragActiveClass);
                    });
                    $zone.on('dragleave', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $(this).removeClass(dragActiveClass);
                    });
                    $zone.on('drop', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        $(this).removeClass(dragActiveClass);
                        const file = e.originalEvent.dataTransfer.files[0];
                        if (file && file.type.startsWith('image/')) {
                            assignFileToInput($input[0], file);
                            showPreview(file);
                        }
                    });

                    return { hidePreview: hidePreview };
                }

                const $createZone = $('#create-upload-zone');
                const $createInput = $('#create-backdrop-file-input');
                const $createPreview = $('#create-file-preview');
                const createDrop = bindDropZone($createZone, $createInput, $createPreview);

                const $editZone = $('#edit-upload-zone');
                const $editInput = $('#edit-backdrop-file-input');
                const $editPreview = $('#edit-file-preview');
                const editDrop = bindDropZone($editZone, $editInput, $editPreview);

                document.querySelectorAll('[data-modal-target="create-modal"]').forEach(function (el) {
                    el.addEventListener('click', function () {
                        createDrop.hidePreview(true);
                        $('#create-backdrop-form').trigger('reset');
                    });
                });

                $('#backdrop-table').on('click', '.js-backdrop-preview-thumb', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const url = $(this).attr('src');
                    if (!url || !window.Swal) {
                        return;
                    }
                    const $img = $('<img>', {
                        src: url,
                        alt: '',
                        class: 'rounded-lg',
                    }).css({
                        maxWidth: '100%',
                        maxHeight: '85vh',
                        objectFit: 'contain',
                    });
                    const $wrap = $('<div class="flex justify-center p-1"></div>').append($img);
                    window.Swal.fire({
                        title: '{{ __('Pratonton backdrop') }}',
                        html: $wrap[0].outerHTML,
                        showCloseButton: true,
                        showConfirmButton: false,
                        width: 'min(90vw, 56rem)',
                        padding: '1.25rem',
                    });
                });

                const table = $('#backdrop-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.kawalan.backdrop.datatable') }}',
                    columnDefs: [{ targets: -1, className: 'text-right' }],
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'preview', name: 'preview', orderable: false, searchable: false },
                        { data: 'backdrop_name', name: 'backdrop_name' },
                        { data: 'is_active', name: 'is_active' },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });

                $('#create-backdrop-form').on('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    $.ajax({
                        url: '{{ route('admin.kawalan.backdrop.store') }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: { Accept: 'application/json' },
                    })
                        .done(function () {
                            closeModal('create-modal');
                            $('#create-backdrop-form').trigger('reset');
                            createDrop.hidePreview(true);
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

                $('#backdrop-table').on('click', '.js-edit-backdrop', function () {
                    const $btn = $(this);
                    $('#edit-backdrop-id').val($btn.attr('data-id'));
                    $('#edit-backdrop_name').val($btn.attr('data-backdrop-name') || '');
                    const imageUrl = $btn.attr('data-image-url') || '';
                    $('#edit-current-image').attr('src', imageUrl);
                    editDrop.hidePreview(true);
                    openModal('edit-modal');
                });

                $('#edit-backdrop-form').on('submit', function (e) {
                    e.preventDefault();
                    const id = $('#edit-backdrop-id').val();
                    const formData = new FormData(this);
                    $.ajax({
                        url: '{{ url('/admin/kawalan/backdrop') }}/' + id,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: { Accept: 'application/json' },
                    })
                        .done(function () {
                            closeModal('edit-modal');
                            editDrop.hidePreview(true);
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

                $('#backdrop-table').on('click', '.js-toggle-active-backdrop', function () {
                    const id = $(this).data('id');
                    $.ajax({
                        url: '{{ url('/admin/kawalan/backdrop') }}/' + id + '/toggle-active',
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}' },
                        headers: { Accept: 'application/json' },
                    })
                        .done(function () {
                            table.ajax.reload(null, false);
                        })
                        .fail(function () {
                            alert('{{ __('Ralat') }}');
                        });
                });

                $('#backdrop-table').on('click', '.js-delete-backdrop', function () {
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
                            url: '{{ url('/admin/kawalan/backdrop') }}/' + id,
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
