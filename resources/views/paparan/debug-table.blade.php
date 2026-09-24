<x-kawalan-shell>
    <x-crud-header
        :title="__('Debug paparan')"
        :description="__('Senarai pegawai yang telah disahkan hadir, disusun mengikut masa kehadiran terkini.')"
        :show-create="false"
    />

    @if ($lateSessionOnAir)
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200/80 bg-amber-50/90 px-4 py-3 text-amber-950 shadow-sm dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-100"
            role="status">
            <i class="ri-alarm-warning-line mt-0.5 text-xl text-amber-600 dark:text-amber-400" aria-hidden="true"></i>
            <div class="min-w-0">
                <p class="text-sm font-semibold">{{ __('Sesi lewat sedang on air') }}</p>
                <p class="mt-1 text-sm text-amber-900/85 dark:text-amber-100/85">
                    {{ __('Pegawai yang hadir kini akan diberi nombor panggilan lewat mengikut turutan ketibaan. Pegawai yang hadir awal kekal mengikut no. kerusi.') }}
                </p>
            </div>
        </div>
    @endif

    <section id="paparan-stats-region" class="mb-6" aria-label="{{ __('Ringkasan kehadiran') }}" aria-live="polite" aria-atomic="false">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-stats-card :label="__('Jumlah hadir')" value="0" stat-key="total_hadir" icon="ri-user-follow-line" />
            <x-stats-card :label="__('Tepat masa')" value="0"
                :hint="__('Pegawai yang hadir mengikut turutan kerusi.')" stat-key="total_tepat" icon="ri-checkbox-circle-line" />
            <x-stats-card :label="__('Lewat')" value="0"
                :hint="__('Pegawai yang hadir selepas sesi bertukar lewat.')" stat-key="total_lewat" icon="ri-alarm-warning-line" />
        </div>
    </section>

    <section class="mt-8 space-y-4" aria-labelledby="paparan-table-heading">
        <div class="flex flex-col gap-4 border-b border-border/60 pb-5 sm:flex-row sm:items-end sm:justify-between sm:gap-6 lg:gap-8">
            <div class="min-w-0 flex-1">
                <h2 id="paparan-table-heading" class="text-lg font-semibold tracking-tight text-foreground">
                    {{ __('Senarai kehadiran terkini') }}
                </h2>
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                    {{ __('Cari mengikut nama atau no. KP. Jadual dimuat semula secara automatik.') }}
                </p>
            </div>
            <div class="group/toolbar flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:min-w-[13rem] sm:items-end">
                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:text-right" for="paparan-sesi-filter">
                    {{ __('Tapis mengikut sesi') }}
                </label>
                <div class="relative w-full sm:w-auto">
                    <span class="pointer-events-none absolute left-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground transition-colors group-focus-within/toolbar:text-primary" aria-hidden="true">
                        <i class="ri-filter-3-line text-lg leading-none"></i>
                    </span>
                    <span class="pointer-events-none absolute right-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground" aria-hidden="true">
                        <i class="ri-arrow-down-s-line text-lg leading-none"></i>
                    </span>
                    <select id="paparan-sesi-filter"
                        class="h-11 w-full min-w-0 cursor-pointer appearance-none rounded-lg border border-input bg-background py-2 pl-10 pr-10 text-sm font-medium text-foreground shadow-sm ring-offset-background transition-all duration-200 hover:border-primary/30 hover:bg-muted/40 hover:shadow-md focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/40 focus:ring-offset-2 dark:hover:bg-muted/20 sm:min-w-[14rem]">
                        <option value="" @selected($selectedSesiId === null)>{{ __('Semua sesi') }}</option>
                        @foreach ($allSesis as $sesi)
                            <option value="{{ $sesi->id }}" @selected($selectedSesiId === $sesi->id)>
                                {{ $sesi->sesi }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <x-data-table
            table-id="paparan-table"
            :columns="[__('Bil.'), __('Giliran'), __('Nama pegawai'), __('Tempat Duduk'), __('PTJ'), __('Masa hadir'), __('Status')]"
            :column-header-classes="[0 => 'text-center', 1 => 'text-center', 3 => 'text-center', 5 => 'text-center', 6 => 'text-center']"
            class="shadow-sm ring-1 ring-border/30"
        />
    </section>
</x-kawalan-shell>

@push('scripts')
    <script>
        $(function() {
            const refreshMs = @json($refreshIntervalMs);
            const paparanStatsUrl = @json($statsRoute);

            function formatStatNumber(n) {
                return Number(n).toLocaleString('en-US');
            }

            let paparanStatsInFlight = false;

            function refreshPaparanStats() {
                const $region = $('#paparan-stats-region');
                if (!$region.length || paparanStatsInFlight) {
                    return;
                }
                paparanStatsInFlight = true;
                $.ajax({
                        url: paparanStatsUrl,
                        dataType: 'json',
                        data: {
                            sesi_majlis_id: $('#paparan-sesi-filter').val() || '',
                        },
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                    .done(function(d) {
                        $region.find('[data-stat="total_hadir"]').text(formatStatNumber(d.total_hadir));
                        $region.find('[data-stat="total_tepat"]').text(formatStatNumber(d.total_tepat));
                        $region.find('[data-stat="total_lewat"]').text(formatStatNumber(d.total_lewat));
                    })
                    .always(function() {
                        paparanStatsInFlight = false;
                    });
            }

            let ajaxRetries = 0;
            let refreshTimer = null;

            const table = $('#paparan-table').DataTable({
                ...(window.kawalanDataTableDefaults || {}),
                processing: true,
                serverSide: true,
                ordering: false,
                pageLength: 25,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                language: {
                    emptyTable: '{{ __('Tiada pegawai yang ditanda hadir buat masa ini.') }}',
                    zeroRecords: '{{ __('Tiada padanan carian.') }}',
                    processing: '{{ __('Memuatkan...') }}',
                    search: '{{ __('Cari') }}:',
                    lengthMenu: '{{ __('Papar _MENU_ baris') }}',
                    info: '{{ __('Menunjukkan _START_ hingga _END_ daripada _TOTAL_ baris') }}',
                    infoEmpty: '{{ __('Tiada baris') }}',
                    infoFiltered: '({{ __('ditapis daripada') }} _MAX_ jumlah baris)',
                    paginate: {
                        previous: '{{ __('Sebelum') }}',
                        next: '{{ __('Seterus') }}',
                    },
                },
                ajax: {
                    url: @json($datatableRoute),
                    data: function(d) {
                        d.sesi_majlis_id = $('#paparan-sesi-filter').val() || '';
                    },
                    error: function(_xhr, _textStatus, errorThrown) {
                        if (errorThrown === 'abort') {
                            return;
                        }

                        if (ajaxRetries < 1) {
                            ajaxRetries += 1;
                            setTimeout(function() {
                                table.ajax.reload(null, false);
                            }, 500);

                            return;
                        }

                        ajaxRetries = 0;

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
                columnDefs: [
                    { targets: 0, className: 'text-center tabular-nums text-muted-foreground', width: '3.5rem' },
                    { targets: 1, className: 'text-center tabular-nums font-semibold text-foreground', width: '8%' },
                    { targets: 2, className: 'align-top py-3 min-w-0', width: '30%' },
                    { targets: 3, className: 'text-center align-top py-3', width: '15%' },
                    { targets: 4, className: 'text-muted-foreground min-w-0', width: '20%' },
                    { targets: 5, className: 'text-center tabular-nums font-medium text-foreground', width: '13%' },
                    { targets: 6, className: 'text-center', width: '10%' },
                ],
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(_data, _type, _row, meta) {
                            return meta.settings._iDisplayStart + meta.row + 1;
                        },
                    },
                    { data: 'giliran', name: 'giliran', searchable: false, orderable: false, defaultContent: '—' },
                    { data: 'nama', name: 'nama' },
                    { data: 'tempat_duduk', name: 'tempat_duduk', searchable: false, orderable: false },
                    { data: 'ptj_name', name: 'ptj.nama_ptj', searchable: false, orderable: false },
                    { data: 'hadir_at_label', name: 'hadir_at', searchable: false, orderable: false, defaultContent: '—' },
                    { data: 'status_label', name: 'status', searchable: false, orderable: false },
                ],
            });

            $('#paparan-table').on('xhr.dt', function() {
                ajaxRetries = 0;
            });

            $('#paparan-sesi-filter').on('change', function() {
                table.ajax.reload();
                refreshPaparanStats();
            });

            queueMicrotask(function() {
                refreshPaparanStats();
            });

            function onPaparanVisibility() {
                if (document.visibilityState === 'visible') {
                    refreshPaparanStats();
                    table.ajax.reload(null, false);
                }
            }
            document.addEventListener('visibilitychange', onPaparanVisibility);

            function startAutoRefresh() {
                if (refreshTimer === null) {
                    refreshTimer = setInterval(function() {
                        if (document.visibilityState === 'visible') {
                            table.ajax.reload(null, false);
                            refreshPaparanStats();
                        }
                    }, refreshMs);
                }
            }
            startAutoRefresh();

            $(window).on('beforeunload', function() {
                document.removeEventListener('visibilitychange', onPaparanVisibility);
                if (refreshTimer !== null) {
                    clearInterval(refreshTimer);
                    refreshTimer = null;
                }
            });
        });
    </script>
@endpush
