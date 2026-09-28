@props([
    'progress',
    'allSesis',
    'selectedSesiId',
    'formAction',
    'pollUrl',
    'datatableUrl',
])

@php
    $toggleTrackClass =
        'peer h-5 w-9 rounded-full bg-muted ring-1 ring-border/60 transition-all after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-background after:shadow-sm after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-ring peer-focus-visible:ring-offset-2';
@endphp

<div>
    {{-- Stats --}}
    <section id="analytics-stats-region" class="mb-6" aria-label="{{ __('Ringkasan analitik') }}" aria-live="polite" aria-atomic="false">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stats-card :label="__('Jumlah pegawai')" :value="number_format((int) $progress['total_officers'])"
                stat-key="total" icon="ri-team-line" />
            <x-stats-card :label="__('Telah diumumkan')" :value="number_format((int) $progress['announced_count'])"
                stat-key="announced" icon="ri-megaphone-line" />
            <x-stats-card :label="__('Kemajuan')" :value="number_format((float) $progress['progress_percent'], 1).'%'"
                stat-key="percent" icon="ri-line-chart-line" />
            <x-stats-card :label="__('Kedudukan semasa')" :value="((int) $progress['current_index']) + 1"
                stat-key="position" icon="ri-focus-3-line" />
        </div>
    </section>

    {{-- Table --}}
    <section class="mt-8 space-y-4" aria-labelledby="analytics-table-heading">
        <div class="flex flex-col gap-4 border-b border-border/60 pb-5 sm:flex-row sm:items-end sm:justify-between sm:gap-6 lg:gap-8">
            <div class="min-w-0 flex-1">
                <h2 id="analytics-table-heading" class="text-lg font-semibold tracking-tight text-foreground">
                    {{ __('Pengumuman terkini') }}
                </h2>
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                    {{ __('Senarai pegawai yang telah diumumkan mengikut masa, terkini di atas.') }}
                </p>
            </div>
            <form method="get" action="{{ $formAction }}" class="group/toolbar flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:min-w-[13rem] sm:items-end">
                <label class="text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:text-right" for="analytics-sesi">
                    {{ __('Tapis mengikut sesi') }}
                </label>
                <div class="relative w-full sm:w-auto">
                    <span class="pointer-events-none absolute left-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground transition-colors group-focus-within/toolbar:text-primary" aria-hidden="true">
                        <i class="ri-filter-3-line text-lg leading-none"></i>
                    </span>
                    <span class="pointer-events-none absolute right-3 top-1/2 z-[1] -translate-y-1/2 text-muted-foreground" aria-hidden="true">
                        <i class="ri-arrow-down-s-line text-lg leading-none"></i>
                    </span>
                    <select
                        id="analytics-sesi"
                        name="sesi_id"
                        onchange="this.form.submit()"
                        class="h-11 w-full min-w-0 cursor-pointer appearance-none rounded-lg border border-input bg-background py-2 pl-10 pr-10 text-sm font-medium text-foreground shadow-sm ring-offset-background transition-all duration-200 hover:border-primary/30 hover:bg-muted/40 hover:shadow-md focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/40 focus:ring-offset-2 dark:hover:bg-muted/20 sm:min-w-[14rem]"
                    >
                        <option value="">{{ __('Semua sesi') }}</option>
                        @foreach ($allSesis as $sesi)
                            <option value="{{ $sesi->id }}" @selected($selectedSesiId === $sesi->id)>{{ $sesi->sesi }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <span id="live-dot" class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                <span id="updated-at" data-stat="updated-at">{{ __('Belum disegerakkan') }}</span>
            </span>

            <div class="flex flex-wrap items-center gap-3">
                <label class="flex items-center gap-2 text-xs font-medium text-muted-foreground" for="auto-refresh-toggle">
                    <span>{{ __('Auto-segar') }}</span>
                    <span class="relative inline-flex shrink-0 cursor-pointer items-center">
                        <input id="auto-refresh-toggle" type="checkbox" class="peer sr-only" checked />
                        <span class="{{ $toggleTrackClass }}"></span>
                    </span>
                </label>

                <button type="button" id="reload-btn" class="btn btn-outline btn-sm">
                    <i id="reload-icon" class="ri-refresh-line"></i>
                    <span>{{ __('Muat semula') }}</span>
                </button>
            </div>
        </div>

        <x-data-table
            table-id="announced-table"
            :columns="[__('Bil'), __('Nama'), __('Jawatan'), __('PTJ'), __('Masa diumumkan')]"
            class="shadow-sm ring-1 ring-border/30"
        />
    </section>
</div>

@push('scripts')
    <script>
        $(function () {
            const sesiId = @json($selectedSesiId);
            const pollUrl = @json($pollUrl);
            const datatableUrl = @json($datatableUrl);
            const storageKey = 'senaraiAnalyticsAutoRefresh';
            const POLL_MS = 15000;

            const statsRegion = document.getElementById('analytics-stats-region');
            const updatedAtEl = document.getElementById('updated-at');
            const liveDot = document.getElementById('live-dot');
            const toggle = document.getElementById('auto-refresh-toggle');
            const reloadBtn = document.getElementById('reload-btn');
            const reloadIcon = document.getElementById('reload-icon');

            let lastAnnounced = @json((int) $progress['announced_count']);
            let lastIndex = @json((int) $progress['current_index']);
            let pollInFlight = false;

            function readAutoRefresh() {
                try {
                    const stored = localStorage.getItem(storageKey);
                    return stored === null ? true : stored === '1';
                } catch (e) {
                    return true;
                }
            }

            function writeAutoRefresh(value) {
                try {
                    localStorage.setItem(storageKey, value ? '1' : '0');
                } catch (e) {
                    /* ignore */
                }
            }

            toggle.checked = readAutoRefresh();

            toggle.addEventListener('change', function () {
                writeAutoRefresh(toggle.checked);
                if (toggle.checked) {
                    tick();
                }
            });

            function stampUpdated(ok) {
                const now = new Date();
                updatedAtEl.textContent = '{{ __('Dikemas kini') }} ' + now.toLocaleTimeString('ms-MY', { hour12: false });
                liveDot.classList.toggle('bg-emerald-500', ok);
                liveDot.classList.toggle('bg-amber-500', !ok);
                liveDot.title = ok ? '' : '{{ __('Sambungan terputus') }}';
            }

            function renderStats(data) {
                if (!data || !statsRegion) return;
                const total = statsRegion.querySelector('[data-stat="total"]');
                const announced = statsRegion.querySelector('[data-stat="announced"]');
                const percent = statsRegion.querySelector('[data-stat="percent"]');
                const position = statsRegion.querySelector('[data-stat="position"]');

                if (total) total.textContent = Number(data.total_officers ?? 0).toLocaleString();
                if (announced) announced.textContent = Number(data.announced_count ?? 0).toLocaleString();
                if (percent) percent.textContent = Number(data.progress_percent ?? 0).toFixed(1) + '%';
                if (position) position.textContent = String(Number(data.current_index ?? 0) + 1);
            }

            function plainText(html) {
                const div = document.createElement('div');
                div.innerHTML = html ?? '';
                return div.textContent || '';
            }

            function renderNamaCell(nama) {
                return '<p class="kawalan-dt-officer-name">' + nama + '</p>';
            }

            function formatRelativeTime(iso) {
                const then = new Date(iso).getTime();
                if (!iso || Number.isNaN(then)) return '—';

                const diffSec = Math.round((Date.now() - then) / 1000);
                if (diffSec < 30) return '{{ __('Baru sahaja') }}';
                if (diffSec < 60) return diffSec + ' {{ __('saat lalu') }}';

                const diffMin = Math.round(diffSec / 60);
                if (diffMin < 60) return diffMin + ' {{ __('minit lalu') }}';

                const diffHour = Math.round(diffMin / 60);
                if (diffHour < 24) return diffHour + ' {{ __('jam lalu') }}';

                const diffDay = Math.round(diffHour / 24);
                return diffDay + ' {{ __('hari lalu') }}';
            }

            function renderTimeCell(iso, absoluteLabel) {
                if (!iso) return absoluteLabel ?? '—';
                return '<time datetime="' + iso + '" title="' + (absoluteLabel ?? '') + '" data-live-time="' + iso + '">'
                    + formatRelativeTime(iso)
                    + '</time>';
            }

            function refreshLiveTimes() {
                document.querySelectorAll('#announced-table [data-live-time]').forEach(function (el) {
                    el.textContent = formatRelativeTime(el.getAttribute('data-live-time'));
                });
            }

            let lastTopRowId = null;

            const table = $('#announced-table').DataTable({
                ...(window.kawalanDataTableDefaults || {}),
                processing: true,
                serverSide: true,
                searchDelay: 600,
                pageLength: 25,
                order: [[4, 'desc']],
                rowId: function (row) {
                    return 'ann-row-' + row.id;
                },
                ajax: {
                    url: datatableUrl,
                    data: function (d) {
                        if (sesiId != null) {
                            d.sesi_id = sesiId;
                        }
                    },
                },
                language: {
                    search: '',
                    searchPlaceholder: '{{ __('Cari nama…') }}',
                    lengthMenu: '_MENU_ {{ __('per halaman') }}',
                    info: '{{ __('Menunjukkan _START_–_END_ daripada _TOTAL_ pengumuman') }}',
                    infoEmpty: '{{ __('Tiada pengumuman') }}',
                    infoFiltered: '({{ __('disaring daripada') }} _MAX_ {{ __('pengumuman') }})',
                    zeroRecords: '<div class="flex flex-col items-center gap-2 py-6 text-center"><i class="ri-search-line text-3xl text-muted-foreground/60"></i><p class="text-sm text-muted-foreground">{{ __('Tiada pengumuman sepadan carian.') }}</p></div>',
                    emptyTable: '<div class="flex flex-col items-center gap-2 py-6 text-center"><i class="ri-megaphone-line text-3xl text-muted-foreground/60"></i><p class="text-sm text-muted-foreground">{{ __('Belum ada pengumuman direkodkan.') }}</p></div>',
                    paginate: {
                        previous: '<i class="ri-arrow-left-s-line"></i>',
                        next: '<i class="ri-arrow-right-s-line"></i>',
                    },
                },
                columnDefs: [
                    { targets: 0, className: 'tabular-nums text-muted-foreground align-top', width: '3.75rem' },
                    { targets: 1, className: 'align-top py-3 min-w-0', width: '32%' },
                    { targets: 2, className: 'text-muted-foreground align-top min-w-0', width: '20%' },
                    { targets: 3, className: 'text-muted-foreground align-top min-w-0', width: '20%' },
                    { targets: 4, className: 'whitespace-nowrap text-muted-foreground tabular-nums align-top', width: '15%' },
                ],
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    {
                        data: 'nama',
                        name: 'nama',
                        orderable: false,
                        render: function (data, type) {
                            return type === 'display' ? renderNamaCell(data) : plainText(data);
                        },
                    },
                    { data: 'jawatan', name: 'jawatan', orderable: false, searchable: false },
                    { data: 'ptj', name: 'ptj', orderable: false, searchable: false },
                    {
                        data: 'announced_at_iso',
                        name: 'announced_at',
                        render: function (data, type, row) {
                            return type === 'display' ? renderTimeCell(data, row.announced_at) : (data ?? '');
                        },
                    },
                ],
                drawCallback: function () {
                    const $rows = $(this.api().table().body()).find('tr');
                    $rows.find('.kawalan-latest-badge').remove();

                    const showLatestBadge = table.page() === 0 && !table.search();
                    const $first = $rows.eq(0);

                    if (showLatestBadge && $first.length) {
                        $first.find('td').eq(1).find('.kawalan-dt-officer-name').after(
                            '<span class="kawalan-latest-badge ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-[0.625rem] font-bold uppercase tracking-wide" style="background: color-mix(in srgb, var(--primary) 12%, transparent); color: var(--primary);">{{ __('Terkini') }}</span>'
                        );

                        const topId = $first.attr('id');
                        if (lastTopRowId !== null && topId !== lastTopRowId) {
                            $first.addClass('kawalan-dt-row-flash');
                            setTimeout(function () {
                                $first.removeClass('kawalan-dt-row-flash');
                            }, 1600);
                        }
                        lastTopRowId = topId;
                    }
                },
            });

            table.on('xhr.dt', function (e, settings, json) {
                renderStats(json?.stats);
                stampUpdated(true);
            });

            setInterval(refreshLiveTimes, 30000);

            async function tick() {
                if (!toggle.checked || document.hidden || pollInFlight) {
                    return;
                }

                pollInFlight = true;
                const query = sesiId != null ? `?sesi_id=${encodeURIComponent(String(sesiId))}` : '';

                try {
                    const response = await fetch(`${pollUrl}${query}`, { headers: { Accept: 'application/json' } });
                    if (!response.ok) {
                        stampUpdated(false);
                        return;
                    }

                    const data = await response.json();
                    renderStats(data);
                    stampUpdated(true);

                    const announcedCount = Number(data.announced_count ?? 0);
                    const currentIndex = Number(data.current_index ?? 0);

                    if (announcedCount !== lastAnnounced || currentIndex !== lastIndex) {
                        lastAnnounced = announcedCount;
                        lastIndex = currentIndex;
                        table.ajax.reload(null, false);
                    }
                } catch (error) {
                    stampUpdated(false);
                } finally {
                    pollInFlight = false;
                }
            }

            reloadBtn.addEventListener('click', function () {
                reloadBtn.disabled = true;
                reloadIcon.classList.add('animate-spin');
                table.ajax.reload(function () {
                    reloadBtn.disabled = false;
                    reloadIcon.classList.remove('animate-spin');
                }, false);
            });

            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    tick();
                }
            });

            setInterval(tick, POLL_MS);
        });
    </script>
@endpush
