@php
    $inputClass =
        'min-w-0 flex-1 border-0 bg-transparent px-3 py-2.5 text-base tabular-nums text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0 sm:text-sm';
    $fieldShell =
        'flex h-12 overflow-hidden rounded-xl border border-input/90 bg-background shadow-sm ring-1 ring-border/30 transition-[box-shadow,ring-color,border-color] focus-within:border-ring focus-within:shadow-md focus-within:ring-2 focus-within:ring-ring dark:bg-background/80';
@endphp

<x-dashboard-layout :title="__('Meja & kapasiti kerusi')" role="admin">
    <x-kawalan-shell>
        <x-crud-header
            :title="__('Meja & kapasiti kerusi')"
            :description="__('Setiap rekod ialah satu meja dengan satu bilangan kerusi. Gunakan nilai ini untuk logik tempat duduk atau kuota.')"
            :create-label="__('Tambah meja')"
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
                        <i class="ri-table-line text-2xl leading-none"></i>
                    </span>
                    <div class="min-w-0 space-y-2">
                        <h2 class="text-base font-semibold tracking-tight text-foreground sm:text-lg">
                            {{ __('Cara data meja digunakan') }}
                        </h2>
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            {{ __('Satu medan nombor sahaja — bilangan kerusi bagi meja tersebut. Integrasi lain boleh membaca `sizing` mengikut ID rekod untuk agihan tempat duduk.') }}
                        </p>
                    </div>
                </div>
            </div>

            <aside class="flex flex-col gap-3" aria-label="{{ __('Ringkasan') }}">
                <div
                    class="flex items-start gap-3 rounded-xl border border-border/60 bg-muted/20 px-4 py-3 dark:bg-muted/10"
                >
                    <span class="mt-0.5 text-primary" aria-hidden="true"><i class="ri-focus-2-line text-lg"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-foreground">
                            {{ __('Satu nilai') }}
                        </p>
                        <p class="mt-0.5 text-xs leading-snug text-muted-foreground">
                            {{ __('Integer positif = bilangan kerusi.') }}
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-start gap-3 rounded-xl border border-border/60 bg-muted/20 px-4 py-3 dark:bg-muted/10"
                >
                    <span class="mt-0.5 text-primary" aria-hidden="true"><i class="ri-links-line text-lg"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-foreground">
                            {{ __('Mengikut ID') }}
                        </p>
                        <p class="mt-0.5 text-xs leading-snug text-muted-foreground">
                            {{ __('Ambil kapasiti melalui ID meja dalam API atau kod.') }}
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-start gap-3 rounded-xl border border-border/60 bg-muted/20 px-4 py-3 dark:bg-muted/10"
                >
                    <span class="mt-0.5 text-primary" aria-hidden="true"><i class="ri-layout-grid-line text-lg"></i></span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-foreground">
                            {{ __('Senarai di bawah') }}
                        </p>
                        <p class="mt-0.5 text-xs leading-snug text-muted-foreground">
                            {{ __('Urus semua meja dalam jadual — cari, susun, dan edit pantas.') }}
                        </p>
                    </div>
                </div>
            </aside>
        </div>

        <x-data-table
            table-id="meja-table"
            :columns="['ID', __('Kapasiti kerusi'), __('Dicipta'), __('Tindakan')]"
        />

        <div class="modal-backdrop"></div>

        <x-crud-modal modal-id="create-modal" size="modal-lg" :title="__('Tambah meja')">
            <form id="create-meja-form" class="space-y-5">
                @csrf
                <div
                    class="rounded-xl border border-dashed border-border/80 bg-muted/15 px-4 py-3 text-center text-xs text-muted-foreground dark:bg-muted/10"
                >
                    <i class="ri-lightbulb-line mb-1 inline-block text-base text-amber-500/90 dark:text-amber-400/90"></i>
                    <p>{{ __('Contoh: 8 kerusi untuk meja mesyuarat bulat — masukkan nombor yang mencerminkan kapasiti sebenar.') }}</p>
                </div>
                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="create-sizing">
                            {{ __('Bilangan kerusi') }}
                        </label>
                        <p class="text-xs text-muted-foreground">
                            {{ __('Minimum 1. Hanya digit sahaja — tiada perpuluhan.') }}
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
                            id="create-sizing"
                            type="number"
                            name="sizing"
                            min="1"
                            step="1"
                            required
                            placeholder="8"
                            inputmode="numeric"
                            autocomplete="off"
                            class="{{ $inputClass }}"
                        />
                        <span
                            class="flex shrink-0 items-center border-l border-border/80 bg-muted/25 px-4 text-sm font-semibold tracking-wide text-muted-foreground"
                        >
                            {{ __('kerusi') }}
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-modal-close>{{ __('Batal') }}</button>
                    <button type="submit" class="btn">{{ __('Simpan') }}</button>
                </div>
            </form>
        </x-crud-modal>

        <x-crud-modal modal-id="edit-modal" size="modal-lg" :title="__('Edit meja')">
            <form id="edit-meja-form" class="space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit-meja-id" />
                <div class="space-y-3 rounded-2xl border border-border/60 bg-card/50 p-4 sm:p-5 dark:bg-card/30">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium leading-none text-foreground" for="edit-sizing">
                            {{ __('Bilangan kerusi') }}
                        </label>
                        <p class="text-xs text-muted-foreground">
                            {{ __('Kemas kini kapasiti. Perubahan akan kelihatan serta-merta dalam jadual.') }}
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
                            id="edit-sizing"
                            type="number"
                            name="sizing"
                            min="1"
                            step="1"
                            required
                            inputmode="numeric"
                            autocomplete="off"
                            class="{{ $inputClass }}"
                        />
                        <span
                            class="flex shrink-0 items-center border-l border-border/80 bg-muted/25 px-4 text-sm font-semibold tracking-wide text-muted-foreground"
                        >
                            {{ __('kerusi') }}
                        </span>
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
                const table = $('#meja-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ajax: '{{ route('admin.kawalan.meja.datatable') }}',
                    columnDefs: [
                        { targets: 1, className: 'min-w-[14rem]' },
                        { targets: -1, className: 'text-right' },
                    ],
                    columns: [
                        { data: 'id', name: 'id' },
                        {
                            data: 'seats_display',
                            name: 'sizing',
                            orderable: true,
                            searchable: true,
                        },
                        { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false },
                    ],
                });

                $('#create-meja-form').on('submit', function (e) {
                    e.preventDefault();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ route('admin.kawalan.meja.store') }}',
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

                $('#meja-table').on('click', '.js-edit-meja', function () {
                    const $btn = $(this);
                    $('#edit-meja-id').val($btn.data('id'));
                    $('#edit-sizing').val($btn.data('sizing'));
                    openModal('edit-modal');
                });

                $('#edit-meja-form').on('submit', function (e) {
                    e.preventDefault();
                    const id = $('#edit-meja-id').val();
                    const $form = $(this);
                    $.ajax({
                        url: '{{ url('/admin/kawalan/meja') }}/' + id,
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

                $('#meja-table').on('click', '.js-delete-meja', function () {
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
                            url: '{{ url('/admin/kawalan/meja') }}/' + id,
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
