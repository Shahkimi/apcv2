<x-dashboard-layout :title="__('Pratonton Laporan Kehadiran')" role="admin">
    <x-kawalan-shell>
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">
                    {{ __('Pratonton Laporan Kehadiran') }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ __('Sesi dipilih: :sesi', ['sesi' => $sesi->sesi]) }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.report.index') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-background px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted/60"
                >
                    <i class="ri-arrow-left-line"></i>
                    {{ __('Kembali') }}
                </a>
            </div>
        </div>

        <section class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-border/70 bg-card px-4 py-3 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ __('Jumlah Pegawai') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-foreground">{{ number_format($reportCounts['onTime'] + $reportCounts['late']) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/60 px-4 py-3 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/25">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-200">{{ __('Tepat Masa') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-800 dark:text-emerald-100">{{ number_format($reportCounts['onTime']) }}</p>
            </div>
            <div class="rounded-xl border border-amber-200/70 bg-amber-50/60 px-4 py-3 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/25">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-200">{{ __('Lewat') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-amber-800 dark:text-amber-100">{{ number_format($reportCounts['late']) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200/70 bg-slate-50/60 px-4 py-3 shadow-sm dark:border-slate-700/60 dark:bg-slate-950/25">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-200">{{ __('Tidak Hadir (Slot)') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-800 dark:text-slate-100">{{ number_format($reportCounts['notAttend']) }}</p>
            </div>
        </section>

        <section class="mb-6 rounded-2xl border border-border/70 bg-card p-4 shadow-sm ring-1 ring-border/40 sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label for="report-search" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        {{ __('Carian pantas') }}
                    </label>
                    <div class="relative w-full sm:w-80">
                        <i class="ri-search-line pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i>
                        <input
                            id="report-search"
                            type="text"
                            placeholder="{{ __('Cari nama, no. KP atau PTJ...') }}"
                            class="h-10 w-full rounded-xl border border-input bg-background pl-9 pr-3 text-sm text-foreground shadow-sm outline-none transition focus-visible:ring-2 focus-visible:ring-ring"
                        >
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2" role="tablist" aria-label="{{ __('Tapis bahagian laporan') }}">
                    <button type="button" class="js-section-filter rounded-full bg-foreground px-3 py-1.5 text-xs font-semibold text-background" data-filter="all">
                        {{ __('Semua') }}
                    </button>
                    <button type="button" class="js-section-filter rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-300" data-filter="ontime">
                        {{ __('Tepat Masa') }}
                    </button>
                    <button type="button" class="js-section-filter rounded-full bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-700 dark:text-amber-300" data-filter="late">
                        {{ __('Lewat') }}
                    </button>
                    <button type="button" class="js-section-filter rounded-full bg-slate-500/10 px-3 py-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300" data-filter="notattend">
                        {{ __('Tidak Hadir (Slot)') }}
                    </button>
                </div>
            </div>
        </section>

        <div class="space-y-6">
            <section data-section="ontime" class="rounded-2xl border border-emerald-200/70 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/25 sm:p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-emerald-900 dark:text-emerald-100">
                        {{ __('Pegawai Tepat Masa') }}
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-100">
                            {{ number_format($reportCounts['onTime']) }} {{ __('orang') }}
                        </span>
                        <a
                            href="{{ route('admin.report.download', ['sesi_id' => $sesi->id, 'export_type' => 'ontime']) }}"
                            class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-500"
                        >
                            <i class="ri-download-2-line text-xs"></i>
                            {{ __('Export PDF') }}
                        </a>
                    </div>
                </div>
                <x-data-table
                    table-id="report-ontime-table"
                    :columns="['#', __('Nama'), __('PTJ'), __('No. Kerusi / No. Sijil'), __('No. Meja')]"
                    :column-header-classes="[0 => 'text-center', 3 => 'text-center', 4 => 'text-center']"
                    class="shadow-sm ring-1 ring-border/30"
                />
            </section>

            <section data-section="late" class="rounded-2xl border border-amber-200/70 bg-amber-50/50 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/25 sm:p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-amber-900 dark:text-amber-100">
                        {{ __('Pegawai Lewat') }}
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/50 dark:text-amber-100">
                            {{ number_format($reportCounts['late']) }} {{ __('orang') }}
                        </span>
                        <a
                            href="{{ route('admin.report.download', ['sesi_id' => $sesi->id, 'export_type' => 'late']) }}"
                            class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-500"
                        >
                            <i class="ri-download-2-line text-xs"></i>
                            {{ __('Export PDF') }}
                        </a>
                    </div>
                </div>
                <x-data-table
                    table-id="report-late-table"
                    :columns="['#', __('Nama'), __('PTJ'), __('No. Kerusi / No. Sijil'), __('No. Meja')]"
                    :column-header-classes="[0 => 'text-center', 3 => 'text-center', 4 => 'text-center']"
                    class="shadow-sm ring-1 ring-border/30"
                />
            </section>

            <section data-section="notattend" class="rounded-2xl border border-slate-200/70 bg-slate-50/50 p-4 shadow-sm dark:border-slate-700/60 dark:bg-slate-950/25 sm:p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                        {{ __('Pegawai Tidak Hadir (Slot Sesi)') }}
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-slate-200/80 px-2.5 py-1 text-xs font-semibold text-slate-800 dark:bg-slate-800/50 dark:text-slate-100">
                            {{ number_format($reportCounts['notAttend']) }} {{ __('orang') }}
                        </span>
                        <a
                            href="{{ route('admin.report.download', ['sesi_id' => $sesi->id, 'export_type' => 'notattend']) }}"
                            class="inline-flex items-center gap-1 rounded-lg bg-slate-700 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-600 dark:bg-slate-600 dark:hover:bg-slate-500"
                        >
                            <i class="ri-download-2-line text-xs"></i>
                            {{ __('Export PDF') }}
                        </a>
                    </div>
                </div>
                <x-data-table
                    table-id="report-notattend-table"
                    :columns="['#', __('Nama'), __('PTJ'), __('No. Kerusi / No. Sijil'), __('No. Meja')]"
                    :column-header-classes="[0 => 'text-center', 3 => 'text-center', 4 => 'text-center']"
                    class="shadow-sm ring-1 ring-border/30"
                />
            </section>
        </div>

        @push('scripts')
            <script>
                $(function() {
                    const datatableUrl = @json(route('admin.report.datatable'));
                    const sesiId = {{ (int) $sesi->id }};

                    const reportColumns = [{
                            data: null,
                            name: 'index',
                            orderable: false,
                            searchable: false,
                            className: 'text-center tabular-nums text-muted-foreground',
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            },
                        },
                        {
                            data: 'nama',
                            name: 'nama',
                        },
                        {
                            data: 'ptj_name',
                            name: 'ptj_name',
                        },
                        {
                            data: 'no_kerusi',
                            name: 'no_kerusi',
                            className: 'text-center tabular-nums',
                        },
                        {
                            data: 'no_meja',
                            name: 'no_meja',
                            className: 'text-center tabular-nums',
                        },
                    ];

                    const dtOntime = $('#report-ontime-table').DataTable({
                        ...(window.kawalanDataTableDefaults || {}),
                        processing: true,
                        serverSide: true,
                        ordering: false,
                        pageLength: 5,
                        lengthMenu: [
                            [5, 10, 25],
                            [5, 10, 25],
                        ],
                        ajax: {
                            url: datatableUrl,
                            data: function(d) {
                                d.sesi_id = sesiId;
                                d.section = 'ontime';
                            },
                        },
                        columns: reportColumns,
                    });

                    const dtLate = $('#report-late-table').DataTable({
                        ...(window.kawalanDataTableDefaults || {}),
                        processing: true,
                        serverSide: true,
                        ordering: false,
                        pageLength: 5,
                        lengthMenu: [
                            [5, 10, 25],
                            [5, 10, 25],
                        ],
                        ajax: {
                            url: datatableUrl,
                            data: function(d) {
                                d.sesi_id = sesiId;
                                d.section = 'late';
                            },
                        },
                        columns: reportColumns,
                    });

                    const dtNotattend = $('#report-notattend-table').DataTable({
                        ...(window.kawalanDataTableDefaults || {}),
                        processing: true,
                        serverSide: true,
                        ordering: false,
                        pageLength: 5,
                        lengthMenu: [
                            [5, 10, 25],
                            [5, 10, 25],
                        ],
                        ajax: {
                            url: datatableUrl,
                            data: function(d) {
                                d.sesi_id = sesiId;
                                d.section = 'notattend';
                            },
                        },
                        columns: reportColumns,
                    });

                    const tables = [dtOntime, dtLate, dtNotattend];
                    const searchInput = document.getElementById('report-search');
                    const filterButtons = Array.from(document.querySelectorAll('.js-section-filter'));
                    const sections = Array.from(document.querySelectorAll('[data-section]'));

                    if (searchInput) {
                        searchInput.addEventListener('input', function() {
                            const v = searchInput.value;
                            tables.forEach(function(t) {
                                t.search(v).draw();
                            });
                        });
                    }

                    const applySectionFilter = function(target) {
                        sections.forEach(function(section) {
                            const shouldShow = target === 'all' || section.dataset.section === target;
                            section.classList.toggle('hidden', !shouldShow);
                        });

                        filterButtons.forEach(function(button) {
                            const active = button.dataset.filter === target;
                            button.classList.toggle('ring-2', active);
                            button.classList.toggle('ring-offset-2', active);
                            button.classList.toggle('ring-ring', active);
                            button.setAttribute('aria-pressed', active ? 'true' : 'false');
                        });

                        requestAnimationFrame(function() {
                            tables.forEach(function(t) {
                                t.columns.adjust();
                            });
                        });
                    };

                    filterButtons.forEach(function(button) {
                        button.addEventListener('click', function() {
                            applySectionFilter(button.dataset.filter || 'all');
                        });
                    });

                    applySectionFilter('all');
                });
            </script>
        @endpush
    </x-kawalan-shell>
</x-dashboard-layout>
