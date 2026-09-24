<x-dashboard-layout :title="__('Kehadiran pegawai')" role="user">
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
        @include('kehadiran.verify-dialog')
        <script>
            $(function() {
                const kehadiranStatsUrl = @json(route('user.kehadiran.stats'));

                function formatStatNumber(n) {
                    return Number(n).toLocaleString('en-US');
                }

                const kehadiranStatsPollMs = 12000;
                let kehadiranStatsPollId = null;
                let kehadiranStatsInFlight = false;

                function refreshKehadiranStats() {
                    const $region = $('#kehadiran-stats-region');
                    if (!$region.length || kehadiranStatsInFlight) {
                        return;
                    }
                    kehadiranStatsInFlight = true;
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
                        })
                        .always(function() {
                            kehadiranStatsInFlight = false;
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
                        url: '{{ route('user.kehadiran.datatable') }}',
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
                    if ($btn.prop('disabled')) {
                        return;
                    }
                    const id = $btn.data('id');
                    const isAttend = Number($btn.data('is-attend')) === 1;

                    $btn.prop('disabled', true);

                    $.ajax({
                            url: '{{ route('user.kehadiran.details', ['pegawai' => '__ID__']) }}'.replace(
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
                            const isJasamu = response?.event_mode === 'jasamu';

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

                            const confirmLabel = isAttend ? '{{ __('Batalkan') }}' :
                                '{{ __('Sahkan') }}';
                            const title = isAttend ?
                                '{{ __('Batalkan kehadiran?') }}' :
                                '{{ __('Sahkan kehadiran?') }}';
                            const sesiDisplayName = !isAttend && activeSesiName ?
                                activeSesiName :
                                (pegawai.sesi_name ?? '—');

                            const { html: infoHtml } = window.kehadiranVerifyDialog({
                                pegawai,
                                isAttend,
                                isJasamu,
                                showTableNumber,
                                sesiDisplayName,
                                title,
                            });

                            window.Swal.fire({
                                html: infoHtml,
                                showCancelButton: true,
                                focusCancel: !isAttend,
                                reverseButtons: true,
                                confirmButtonText: confirmLabel,
                                cancelButtonText: '{{ __('Batal') }}',
                                background: 'var(--popover)',
                                color: 'var(--popover-foreground)',
                                buttonsStyling: false,
                                customClass: {
                                    popup: 'kawalan-swal2-popup kawalan-swal2-compact',
                                    htmlContainer: 'kawalan-swal2-html',
                                    actions: 'kawalan-swal2-actions',
                                    confirmButton: isAttend ? 'kawalan-swal2-confirm-warning' :
                                        'kawalan-swal2-confirm-success',
                                    cancelButton: 'kawalan-swal2-cancel',
                                },
                                width: '30rem',
                            }).then((result) => {
                                if (!result.isConfirmed) {
                                    return;
                                }

                                $btn.prop('disabled', true);

                                $.ajax({
                                        url: '{{ route('user.kehadiran.verify', ['pegawai' => '__ID__']) }}'
                                            .replace('__ID__', String(id)),
                                        method: 'POST',
                                        data: {
                                            _token: '{{ csrf_token() }}',
                                            _method: 'PUT',
                                            is_attend: isAttend ? 0 : 1
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
                                        $btn.prop('disabled', false);
                                        const msg = xhr?.responseJSON?.message;
                                        alert(msg || '{{ __('Ralat') }}');
                                    });
                            });
                        })
                        .fail(function() {
                            alert('{{ __('Ralat mendapatkan maklumat pegawai') }}');
                        })
                        .always(function() {
                            $btn.prop('disabled', false);
                        });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
