<x-dashboard-layout :title="__('Kehadiran pegawai')" role="admin">
    <x-kawalan-shell>
        <x-crud-header :title="__('Kehadiran pegawai')" :description="__('Semak maklumat pegawai dan sahkan kehadiran terus daripada jadual.')" :show-create="false" />

        @if ($lateSessionOnAir)
            <div
                class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-amber-950 shadow-sm dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100"
                role="status"
            >
                <i class="ri-alarm-warning-line mt-0.5 text-xl text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                <div class="min-w-0">
                    <p class="text-sm font-semibold">{{ __('Sesi lewat sedang on air') }}</p>
                    <p class="mt-1 text-sm text-amber-900/85 dark:text-amber-100/85">
                        {{ __('Pegawai yang disahkan kini akan diberi nombor panggilan lewat mengikut turutan ketibaan. Pegawai yang hadir awal kekal mengikut no. kerusi.') }}
                    </p>
                </div>
            </div>
        @endif

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_min(19rem,100%)] lg:gap-6">
            <div
                class="relative overflow-hidden rounded-2xl border border-border/70 bg-gradient-to-br from-card via-card to-indigo/[0.06] p-5 shadow-sm ring-1 ring-border/40 sm:p-6 dark:to-indigo/[0.12]">
                <div class="pointer-events-none absolute -right-8 -top-8 h-32 w-32 rounded-full bg-indigo-400/15 blur-2xl dark:bg-indigo-300/20"
                    aria-hidden="true"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:gap-5">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-500 text-white shadow-md shadow-indigo-500/25"
                        aria-hidden="true">
                        <i class="ri-clipboard-line text-2xl leading-none"></i>
                    </span>
                    <div class="min-w-0 space-y-2">
                        <h2 class="text-base font-semibold tracking-tight text-foreground sm:text-lg">
                            {{ __('Sahkan kehadiran pegawai') }}
                        </h2>
                        <p class="text-sm leading-relaxed text-muted-foreground">
                            {{ __('Papar data pegawai berdasarkan RSVP. Gunakan butang Sahkan untuk menanda kehadiran fizikal semasa majlis.') }}
                        </p>
                    </div>
                </div>
            </div>

            <aside class="flex flex-col gap-3" aria-label="{{ __('Ringkasan kehadiran') }}">
                <div class="rounded-xl border border-border/60 bg-muted/20 px-4 py-3 dark:bg-muted/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ __('Jumlah pegawai') }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-foreground">
                        {{ number_format($totalPegawai) }}</p>
                </div>
                <div
                    class="rounded-xl border border-emerald-200/60 bg-emerald-50/70 px-4 py-3 dark:border-emerald-800/50 dark:bg-emerald-950/30">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-emerald-700/90 dark:text-emerald-300/90">
                        {{ __('RSVP Ya') }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-800 dark:text-emerald-200">
                        {{ number_format($totalRsvp) }}</p>
                </div>
                <div
                    class="rounded-xl border border-indigo-200/60 bg-indigo-50/70 px-4 py-3 dark:border-indigo-800/50 dark:bg-indigo-950/30">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700/90 dark:text-indigo-300/90">
                        {{ __('Telah hadir') }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-indigo-800 dark:text-indigo-200">
                        {{ number_format($totalHadir) }}</p>
                </div>
            </aside>
        </div>

        <section class="mt-8 space-y-4" aria-labelledby="kehadiran-table-heading">
            <div class="border-b border-border/60 pb-4">
                <h2 id="kehadiran-table-heading" class="text-lg font-semibold tracking-tight text-foreground">
                    {{ __('Senarai pegawai') }}
                </h2>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    {{ __('Cari mengikut nama atau no. KP. Sahkan atau batalkan kehadiran melalui lajur tindakan.') }}
                </p>
            </div>

            <x-data-table
                table-id="kehadiran-table"
                :columns="['ID', __('Pegawai'), __('No. Kerusi'), __('No. panggilan lewat'), __('RSVP'), __('PTJ'), __('Tindakan')]"
                class="shadow-sm ring-1 ring-border/30"
            />
        </section>
    </x-kawalan-shell>

    @push('scripts')
        <script>
            $(function() {
                const table = $('#kehadiran-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ordering: false,
                    ajax: '{{ route('admin.kehadiran.datatable') }}',
                    columnDefs: [{
                            targets: 1,
                            className: 'align-top py-3',
                            width: '22%'
                        },
                        {
                            targets: 2,
                            className: 'text-muted-foreground tabular-nums align-top'
                        },
                        {
                            targets: 3,
                            className: 'text-muted-foreground tabular-nums align-top'
                        },
                        {
                            targets: 4,
                            className: 'min-w-[7rem] align-top'
                        },
                        {
                            targets: 5,
                            className: 'text-muted-foreground align-top max-w-[12rem]'
                        },
                        {
                            targets: -1,
                            className: 'min-w-[10rem] text-right align-top'
                        },
                    ],
                    columns: [{
                            data: 'id',
                            name: 'id'
                        },
                        {
                            data: 'nama',
                            name: 'nama'
                        },
                        {
                            data: 'no_kerusi',
                            name: 'no_kerusi',
                            defaultContent: '-'
                        },
                        {
                            data: 'no_panggilan_lewat',
                            name: 'no_panggilan_lewat',
                            defaultContent: '—'
                        },
                        {
                            data: 'rsvp_label',
                            name: 'rsvp',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'ptj_name',
                            name: 'ptj.nama_ptj'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                    ],
                });

                $('#kehadiran-table').on('click', '.js-verify-kehadiran', function() {
                    const $btn = $(this);
                    const id = $btn.data('id');
                    const isAttend = Number($btn.data('is-attend')) === 1;

                    $.ajax({
                            url: '{{ route('admin.kehadiran.details', ['pegawai' => '__ID__']) }}'.replace(
                                '__ID__', String(id)),
                            method: 'GET',
                            headers: {
                                Accept: 'application/json'
                            },
                        })
                        .done(function(response) {
                            const pegawai = response.pegawai;
                            const confirmLabel = isAttend ? '{{ __('Batalkan') }}' :
                                '{{ __('Sahkan') }}';
                            const title = isAttend ?
                                '{{ __('Batalkan kehadiran?') }}' :
                                '{{ __('Sahkan kehadiran?') }}';

                            const infoHtml = `
                                <div class="space-y-3 text-left">
                                    <div class="flex items-center justify-between rounded-lg border border-border/50 bg-muted/20 px-3 py-2">
                                        <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">{{ __('Status tindakan') }}</span>
                                        <span class="${isAttend ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300' : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'} inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold">
                                            <i class="${isAttend ? 'ri-close-circle-line' : 'ri-checkbox-circle-line'} text-xs"></i>
                                            ${isAttend ? '{{ __('Batalkan hadir') }}' : '{{ __('Sahkan hadir') }}'}
                                        </span>
                                    </div>

                                    <div class="overflow-hidden rounded-xl border border-border/50 bg-card shadow-sm">
                                        <div class="flex items-center gap-2 border-b border-border/40 bg-muted/30 px-4 py-2.5">
                                            <i class="ri-account-circle-line text-base text-muted-foreground"></i>
                                            <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ __('Maklumat Pegawai') }}</span>
                                        </div>
                                        <div class="divide-y divide-border/30">
                                            <div class="flex items-center gap-3 px-4 py-3">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                                                    <i class="ri-user-line text-sm"></i>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ __('Nama Pegawai') }}</p>
                                                    <p class="truncate text-sm font-semibold text-foreground">${pegawai.nama}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3 px-4 py-3">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-violet-500/10 text-violet-600 dark:text-violet-400">
                                                    <i class="ri-id-card-line text-sm"></i>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ __('No. KP') }}</p>
                                                    <p class="font-mono text-sm font-semibold tracking-wide text-foreground">${pegawai.no_kp}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3 px-4 py-3">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-sky-500/10 text-sky-600 dark:text-sky-400">
                                                    <i class="ri-building-2-line text-sm"></i>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ __('PTJ') }}</p>
                                                    <p class="truncate text-sm font-semibold text-foreground">${pegawai.ptj_name ?? '-'}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    ${pegawai.no_panggilan_lewat && pegawai.no_panggilan_lewat !== '-' ? `
                                    <div class="rounded-lg border border-amber-200/70 bg-amber-50/80 px-3 py-2 dark:border-amber-800/50 dark:bg-amber-950/30">
                                        <p class="text-[10px] font-semibold uppercase tracking-widest text-amber-800 dark:text-amber-200">{{ __('No. panggilan lewat') }}</p>
                                        <p class="mt-1 text-lg font-bold tabular-nums text-amber-900 dark:text-amber-100">${pegawai.no_panggilan_lewat}</p>
                                    </div>
                                    ` : ''}

                                    <div>
                                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">{{ __('Ringkasan Penempatan') }}</p>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="relative overflow-hidden rounded-xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-3.5 dark:border-emerald-800/40 dark:from-emerald-950/40 dark:to-emerald-900/20">
                                                <div class="absolute right-2.5 top-2.5 opacity-10">
                                                    <i class="ri-armchair-line text-3xl text-emerald-600"></i>
                                                </div>
                                                <p class="text-[10px] font-semibold uppercase tracking-widest text-emerald-700/80 dark:text-emerald-400/80">{{ __('No. Kerusi') }}</p>
                                                <p class="mt-1.5 text-2xl font-black tabular-nums leading-none text-emerald-700 dark:text-emerald-300">${pegawai.no_kerusi}</p>
                                                <div class="mt-1.5 flex items-center gap-1">
                                                    <i class="ri-armchair-line text-xs text-emerald-600/60 dark:text-emerald-400/60"></i>
                                                    <span class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70">{{ __('Tempat Duduk') }}</span>
                                                </div>
                                            </div>
                                            <div class="relative overflow-hidden rounded-xl border border-indigo-200/70 bg-gradient-to-br from-indigo-50 to-indigo-100/50 p-3.5 dark:border-indigo-800/40 dark:from-indigo-950/40 dark:to-indigo-900/20">
                                                <div class="absolute right-2.5 top-2.5 opacity-10">
                                                    <i class="ri-table-line text-3xl text-indigo-600"></i>
                                                </div>
                                                <p class="text-[10px] font-semibold uppercase tracking-widest text-indigo-700/80 dark:text-indigo-400/80">{{ __('No. Meja') }}</p>
                                                <p class="mt-1.5 text-2xl font-black tabular-nums leading-none text-indigo-700 dark:text-indigo-300">${pegawai.no_meja}</p>
                                                <div class="mt-1.5 flex items-center gap-1">
                                                    <i class="ri-table-line text-xs text-indigo-600/60 dark:text-indigo-400/60"></i>
                                                    <span class="text-[10px] text-indigo-600/70 dark:text-indigo-400/70">{{ __('Jadual') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;

                            window.Swal.fire({
                                title,
                                html: infoHtml,
                                icon: 'question',
                                showCancelButton: true,
                                focusCancel: !isAttend,
                                reverseButtons: true,
                                confirmButtonText: confirmLabel,
                                cancelButtonText: '{{ __('Batal') }}',
                                background: 'var(--popover)',
                                color: 'var(--popover-foreground)',
                                buttonsStyling: false,
                                customClass: {
                                    popup: 'kawalan-swal2-popup kawalan-swal2-wide',
                                    htmlContainer: 'kawalan-swal2-html',
                                    actions: 'kawalan-swal2-actions',
                                    confirmButton: isAttend ? 'kawalan-swal2-confirm-warning' : 'kawalan-swal2-confirm-success',
                                    cancelButton: 'kawalan-swal2-cancel',
                                },
                                width: '28rem',
                            }).then((result) => {
                                if (!result.isConfirmed) {
                                    return;
                                }

                                $.ajax({
                                        url: '{{ route('admin.kehadiran.verify', ['pegawai' => '__ID__']) }}'
                                            .replace('__ID__', String(id)),
                                        method: 'POST',
                                        data: {
                                            _token: '{{ csrf_token() }}',
                                            _method: 'PUT'
                                        },
                                        headers: {
                                            Accept: 'application/json'
                                        },
                                    })
                                    .done(function(response) {
                                        const isApproved = Boolean(response?.is_attend);
                                        const approveTitle = isApproved
                                            ? '{{ __('Kehadiran disahkan') }}'
                                            : '{{ __('Kehadiran dibatalkan') }}';
                                        const approveText = isApproved
                                            ? '{{ __('Pegawai telah berjaya ditanda sebagai hadir.') }}'
                                            : '{{ __('Status kehadiran telah dikemaskini.') }}';

                                        window.Swal.fire({
                                            title: approveTitle,
                                            text: approveText,
                                            icon: isApproved ? 'success' : 'info',
                                            timer: 1300,
                                            timerProgressBar: true,
                                            showConfirmButton: false,
                                            background: 'var(--popover)',
                                            color: 'var(--popover-foreground)',
                                            customClass: {
                                                popup: 'kawalan-swal2-popup kawalan-swal2-approve',
                                                icon: isApproved ? 'kawalan-swal2-approve-icon' : '',
                                            },
                                        }).then(function() {
                                            table.ajax.reload(null, false);
                                        });
                                    })
                                    .fail(function() {
                                        alert('{{ __('Ralat') }}');
                                    });
                            });
                        })
                        .fail(function() {
                            alert('{{ __('Ralat mendapatkan maklumat pegawai') }}');
                        });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
