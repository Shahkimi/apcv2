<x-dashboard-layout :title="__('Kehadiran pegawai')" role="admin">
    <x-kawalan-shell>
        <x-crud-header :title="__('Kehadiran pegawai')" :description="__('Semak maklumat pegawai dan sahkan kehadiran terus daripada jadual.')" :show-create="false" />

        @if ($lateSessionOnAir)
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-amber-950 shadow-sm dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100"
                role="status">
                <i class="ri-alarm-warning-line mt-0.5 text-xl text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
                <div class="min-w-0">
                    <p class="text-sm font-semibold">{{ __('Sesi lewat sedang on air') }}</p>
                    <p class="mt-1 text-sm text-amber-900/85 dark:text-amber-100/85">
                        {{ __('Pegawai yang disahkan kini akan diberi nombor panggilan lewat mengikut turutan ketibaan. Pegawai yang hadir awal kekal mengikut no. kerusi.') }}
                    </p>
                </div>
            </div>
        @endif

        <section id="kehadiran-stats-region" class="mb-6" aria-label="{{ __('Ringkasan kehadiran') }}"
            aria-live="polite" aria-atomic="false">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <x-stats-card :label="__('Jumlah pegawai')" :value="number_format($totalPegawai)" stat-key="total_pegawai"
                    icon="ri-team-line" />
                <x-stats-card :label="__('RSVP Ya')" :value="number_format($totalRsvp)"
                    :hint="__('Berdasarkan jemputan. Sahkan kehadiran fizikal melalui jadual di bawah.')"
                    stat-key="total_rsvp" icon="ri-calendar-check-line" />
                <x-stats-card :label="__('Telah hadir')" :value="number_format($totalHadir)"
                    :hint="__('Pegawai yang telah disahkan hadir bagi sesi semasa.')" stat-key="total_hadir"
                    icon="ri-user-follow-line" />
            </div>
        </section>

        <section class="mt-8 space-y-4" aria-labelledby="kehadiran-table-heading">
            <div
                class="flex flex-col gap-4 border-b border-border/60 pb-5 sm:flex-row sm:items-end sm:justify-between sm:gap-6 lg:gap-8">
                <div class="min-w-0 flex-1">
                    <h2 id="kehadiran-table-heading" class="text-lg font-semibold tracking-tight text-foreground">
                        {{ __('Senarai pegawai') }}
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                        {{ __('Cari mengikut nama atau no. KP. Sahkan atau batalkan kehadiran melalui lajur tindakan.') }}
                    </p>
                </div>
                <div class="group/toolbar flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:min-w-[13rem] sm:items-end">
                    <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:text-right"
                        for="sesi-filter">
                        {{ __('Tapis mengikut sesi') }}
                    </label>
                    <div class="relative w-full sm:w-auto">
                        <span
                            class="pointer-events-none absolute left-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground transition-colors group-focus-within/toolbar:text-primary"
                            aria-hidden="true">
                            <i class="ri-filter-3-line text-lg leading-none"></i>
                        </span>
                        <span
                            class="pointer-events-none absolute right-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true">
                            <i class="ri-arrow-down-s-line text-lg leading-none"></i>
                        </span>
                        <select id="sesi-filter"
                            class="h-11 w-full min-w-0 cursor-pointer appearance-none rounded-lg border border-input bg-background py-2 pl-10 pr-10 text-sm font-medium text-foreground shadow-sm ring-offset-background transition-all duration-200 hover:border-primary/30 hover:bg-muted/40 hover:shadow-md focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/40 focus:ring-offset-2 dark:hover:bg-muted/20 sm:min-w-[14rem]">
                            <option value="">{{ __('Semua sesi') }}</option>
                            @foreach ($allSesis as $sesi)
                                <option value="{{ $sesi->id }}">{{ $sesi->sesi }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <x-data-table table-id="kehadiran-table" :columns="['ID', __('Pegawai'), __('PTJ'), __('RSVP').' - '.__('Sesi'), __('No. Kerusi'), __('Tindakan')]" :column-header-classes="[3 => 'text-center', 4 => 'text-center', 5 => 'text-center']"
                class="shadow-sm ring-1 ring-border/30" />
        </section>
    </x-kawalan-shell>

    @push('scripts')
        <script>
            $(function() {
                const kehadiranStatsUrl = @json(route('admin.kehadiran.stats'));

                function formatStatNumber(n) {
                    return Number(n).toLocaleString('en-US');
                }

                const kehadiranStatsPollMs = 12000;
                let kehadiranStatsPollId = null;

                function refreshKehadiranStats() {
                    const $region = $('#kehadiran-stats-region');
                    if (!$region.length) {
                        return;
                    }
                    $.ajax({
                            url: kehadiranStatsUrl,
                            dataType: 'json',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        })
                        .done(function(d) {
                            $region.find('[data-stat="total_pegawai"]').text(formatStatNumber(d.total_pegawai));
                            $region.find('[data-stat="total_rsvp"]').text(formatStatNumber(d.total_rsvp));
                            $region.find('[data-stat="total_hadir"]').text(formatStatNumber(d.total_hadir));
                        });
                }

                function startKehadiranStatsPolling() {
                    if (kehadiranStatsPollId !== null) {
                        return;
                    }
                    kehadiranStatsPollId = window.setInterval(function() {
                        if (document.visibilityState === 'visible') {
                            refreshKehadiranStats();
                        }
                    }, kehadiranStatsPollMs);
                }

                let kehadiranDtAjaxRetries = 0;

                const table = $('#kehadiran-table').DataTable({
                    ...(window.kawalanDataTableDefaults || {}),
                    processing: true,
                    serverSide: true,
                    ordering: false,
                    ajax: {
                        url: '{{ route('admin.kehadiran.datatable') }}',
                        data: function(d) {
                            d.sesi_majlis_id = $('#sesi-filter').val() || '';
                        },
                        error: function(_xhr, _textStatus, errorThrown) {
                            if (errorThrown === 'abort') {
                                return;
                            }

                            if (kehadiranDtAjaxRetries < 1) {
                                kehadiranDtAjaxRetries += 1;
                                setTimeout(function() {
                                    table.ajax.reload(null, false);
                                }, 500);

                                return;
                            }

                            kehadiranDtAjaxRetries = 0;

                            if (window.Swal && typeof window.Swal.fire === 'function') {
                                window.Swal.fire({
                                    icon: 'error',
                                    title: '{{ __('Ralat') }}',
                                    text: '{{ __('Gagal memuatkan senarai. Sila cuba lagi atau muat semula halaman.') }}',
                                    confirmButtonText: '{{ __('Tutup') }}',
                                    background: 'var(--popover)',
                                    color: 'var(--popover-foreground)',
                                    buttonsStyling: false,
                                    customClass: {
                                        popup: 'kawalan-swal2-popup',
                                        confirmButton: 'kawalan-swal2-confirm-danger',
                                    },
                                });
                            } else {
                                alert('{{ __('Gagal memuatkan senarai.') }}');
                            }
                        },
                    },
                    columnDefs: [{
                            targets: 0,
                            className: 'tabular-nums text-muted-foreground align-top',
                            width: '3.75rem',
                        },
                        {
                            targets: 1,
                            className: 'align-top py-3 min-w-0',
                            width: '33%',
                        },
                        {
                            targets: 2,
                            className: 'text-muted-foreground align-top min-w-0',
                            width: '32%',
                        },
                        {
                            targets: 3,
                            className: 'text-center align-top min-w-0',
                            width: '10%',
                        },
                        {
                            targets: 4,
                            className: 'text-muted-foreground tabular-nums text-center align-top min-w-0',
                            width: '10%',
                        },
                        {
                            targets: 5,
                            className: 'min-w-0 text-center align-top',
                            width: '10%',
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
                            data: 'ptj_name',
                            name: 'ptj.nama_ptj'
                        },
                        {
                            data: 'rsvp_sesi_label',
                            name: 'rsvp_sesi',
                            defaultContent: '—',
                            searchable: false,
                            orderable: false,
                        },
                        {
                            data: 'no_kerusi',
                            name: 'no_kerusi',
                            defaultContent: '-'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                    ],
                });

                $('#kehadiran-table').on('xhr.dt', function() {
                    kehadiranDtAjaxRetries = 0;
                });

                queueMicrotask(function() {
                    refreshKehadiranStats();
                    startKehadiranStatsPolling();
                });

                function onKehadiranStatsVisibility() {
                    if (document.visibilityState === 'visible') {
                        refreshKehadiranStats();
                    }
                }
                document.addEventListener('visibilitychange', onKehadiranStatsVisibility);

                $(window).on('beforeunload', function() {
                    document.removeEventListener('visibilitychange', onKehadiranStatsVisibility);
                    if (kehadiranStatsPollId !== null) {
                        window.clearInterval(kehadiranStatsPollId);
                        kehadiranStatsPollId = null;
                    }
                });

                $('#sesi-filter').on('change', function() {
                    table.ajax.reload();
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
                            const showTableNumber = Boolean(response?.show_table_number);
                            const activeSesiSKehadiran = response.active_sesi_s_kehadiran;
                            const activeSesiName = response.active_sesi_name;

                            if (
                                !isAttend &&
                                activeSesiSKehadiran !== null &&
                                activeSesiSKehadiran !== undefined
                            ) {
                                if (Number(pegawai.s_kehadiran) !== Number(activeSesiSKehadiran)) {
                                    const pegawaiType =
                                        Number(pegawai.s_kehadiran) === 0 ?
                                        '{{ __('pagi') }}' :
                                        '{{ __('petang') }}';
                                    const sesiType =
                                        Number(activeSesiSKehadiran) === 0 ?
                                        '{{ __('pagi') }}' :
                                        '{{ __('petang') }}';

                                    window.Swal.fire({
                                        icon: 'error',
                                        title: '{{ __('Jenis sesi tidak sepadan') }}',
                                        text: '{{ __('Pegawai ini berdaftar untuk sesi') }} ' +
                                            pegawaiType +
                                            ' {{ __('tetapi sesi semasa adalah') }} ' +
                                            sesiType +
                                            '.',
                                        confirmButtonText: '{{ __('Tutup') }}',
                                        background: 'var(--popover)',
                                        color: 'var(--popover-foreground)',
                                        buttonsStyling: false,
                                        customClass: {
                                            popup: 'kawalan-swal2-popup',
                                            confirmButton: 'kawalan-swal2-confirm-danger',
                                        },
                                    });
                                    return;
                                }
                            }

                            const isRsvpYes = Number(pegawai.rsvp) === 1;
                            const confirmLabel = isAttend ? '{{ __('Batalkan') }}' :
                                '{{ __('Sahkan') }}';
                            const title = isAttend ?
                                '{{ __('Batalkan kehadiran?') }}' :
                                '{{ __('Sahkan kehadiran?') }}';
                            const sesiDisplayName = !isAttend && activeSesiName ?
                                activeSesiName :
                                (pegawai.sesi_name ?? '—');

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
                                            <div class="flex items-center gap-3 px-4 py-3">
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                                    <i class="ri-time-line text-sm"></i>
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">{{ __('Sesi') }}</p>
                                                    <p class="truncate text-sm font-semibold text-foreground">${sesiDisplayName}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    ${isRsvpYes && pegawai.no_panggilan_lewat !== '-' && Number(pegawai.no_panggilan_lewat) > 0 ? `
                                            <div class="rounded-lg border border-amber-200/70 bg-amber-50/80 px-3 py-2 dark:border-amber-800/50 dark:bg-amber-950/30">
                                                <p class="text-[10px] font-semibold uppercase tracking-widest text-amber-800 dark:text-amber-200">{{ __('No. panggilan lewat') }}</p>
                                                <p class="mt-1 text-lg font-bold tabular-nums text-amber-900 dark:text-amber-100">${pegawai.no_panggilan_lewat}</p>
                                            </div>
                                            ` : ''}

                                    <div>
                                        <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-muted-foreground">{{ __('Ringkasan Penempatan') }}</p>
                                        ${isRsvpYes ? `
                                                <div class="${showTableNumber ? 'grid grid-cols-2 gap-3' : ''}">
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
                                                    ${showTableNumber ? `
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
                                            ` : ''}
                                                </div>
                                                ` : `
                                                <div class="relative overflow-hidden rounded-xl border border-amber-200/70 bg-gradient-to-br from-amber-50 to-amber-100/50 p-3.5 dark:border-amber-800/40 dark:from-amber-950/40 dark:to-amber-900/20">
                                                    <div class="absolute right-2.5 top-2.5 opacity-10">
                                                        <i class="ri-phone-line text-3xl text-amber-600"></i>
                                                    </div>
                                                    <p class="text-[10px] font-semibold uppercase tracking-widest text-amber-700/80 dark:text-amber-300/80">{{ __('No. Panggilan Lewat') }}</p>
                                                    <p class="mt-1.5 text-2xl font-black tabular-nums leading-none text-amber-700 dark:text-amber-200">${Number(pegawai.no_panggilan_lewat) > 0 ? pegawai.no_panggilan_lewat : '-'}</p>
                                                    <div class="mt-1.5 flex items-center gap-1">
                                                        <i class="ri-phone-line text-xs text-amber-600/60 dark:text-amber-400/60"></i>
                                                        <span class="text-[10px] text-amber-600/70 dark:text-amber-400/70">{{ __('Turutan Lewat') }}</span>
                                                    </div>
                                                </div>
                                                `}
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
                                    confirmButton: isAttend ? 'kawalan-swal2-confirm-warning' :
                                        'kawalan-swal2-confirm-success',
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
                                        refreshKehadiranStats();
                                        const isApproved = Boolean(response?.is_attend);
                                        const approveTitle = isApproved ?
                                            '{{ __('Kehadiran disahkan') }}' :
                                            '{{ __('Kehadiran dibatalkan') }}';
                                        const approveText = isApproved ?
                                            '{{ __('Pegawai telah berjaya ditanda sebagai hadir.') }}' :
                                            '{{ __('Status kehadiran telah dikemaskini.') }}';

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
                                                icon: isApproved ?
                                                    'kawalan-swal2-approve-icon' : '',
                                            },
                                        }).then(function() {
                                            table.ajax.reload(null, false);
                                        });
                                    })
                                    .fail(function(xhr) {
                                        const msg = xhr?.responseJSON?.message;
                                        alert(msg || '{{ __('Ralat') }}');
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
