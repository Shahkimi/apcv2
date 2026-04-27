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

        <section class="mb-6 grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-border/70 bg-card px-4 py-3 shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ __('Jumlah Pegawai') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-foreground">{{ number_format($onTime->count() + $late->count()) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/60 px-4 py-3 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/25">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-200">{{ __('Tepat Masa') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-800 dark:text-emerald-100">{{ number_format($onTime->count()) }}</p>
            </div>
            <div class="rounded-xl border border-amber-200/70 bg-amber-50/60 px-4 py-3 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/25">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-200">{{ __('Lewat') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-amber-800 dark:text-amber-100">{{ number_format($late->count()) }}</p>
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
                            placeholder="{{ __('Cari nama, jawatan atau PTJ...') }}"
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
                            {{ $onTime->count() }} {{ __('orang') }}
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
                @include('admin::report.partials.table', ['rows' => $onTime, 'emptyText' => __('Tiada pegawai tepat masa untuk sesi ini.'), 'tone' => 'ontime'])
            </section>

            <section data-section="late" class="rounded-2xl border border-amber-200/70 bg-amber-50/50 p-4 shadow-sm dark:border-amber-900/60 dark:bg-amber-950/25 sm:p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-amber-900 dark:text-amber-100">
                        {{ __('Pegawai Lewat') }}
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/50 dark:text-amber-100">
                            {{ $late->count() }} {{ __('orang') }}
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
                @include('admin::report.partials.table', ['rows' => $late, 'emptyText' => __('Tiada pegawai lewat untuk sesi ini.'), 'tone' => 'late'])
            </section>
        </div>

        @push('scripts')
            <script>
                (() => {
                    const searchInput = document.getElementById('report-search');
                    const filterButtons = Array.from(document.querySelectorAll('.js-section-filter'));
                    const sections = Array.from(document.querySelectorAll('[data-section]'));
                    const rows = Array.from(document.querySelectorAll('.js-report-row'));

                    if (!searchInput || rows.length === 0) {
                        return;
                    }

                    const applySearch = () => {
                        const keyword = searchInput.value.trim().toLowerCase();

                        rows.forEach((row) => {
                            const haystack = (row.dataset.search || '').toLowerCase();
                            row.classList.toggle('hidden', keyword !== '' && !haystack.includes(keyword));
                        });
                    };

                    const applySectionFilter = (target) => {
                        sections.forEach((section) => {
                            const shouldShow = target === 'all' || section.dataset.section === target;
                            section.classList.toggle('hidden', !shouldShow);
                        });

                        filterButtons.forEach((button) => {
                            const active = button.dataset.filter === target;
                            button.classList.toggle('ring-2', active);
                            button.classList.toggle('ring-offset-2', active);
                            button.classList.toggle('ring-ring', active);
                            button.setAttribute('aria-pressed', active ? 'true' : 'false');
                        });
                    };

                    searchInput.addEventListener('input', applySearch);
                    filterButtons.forEach((button) => {
                        button.addEventListener('click', () => applySectionFilter(button.dataset.filter || 'all'));
                    });
                    applySectionFilter('all');
                })();
            </script>
        @endpush
    </x-kawalan-shell>
</x-dashboard-layout>
