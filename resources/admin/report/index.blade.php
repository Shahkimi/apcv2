<x-dashboard-layout :title="__('Laporan Kehadiran Pegawai')" role="admin">
    <x-kawalan-shell>
        <x-crud-header
            :title="__('Laporan Kehadiran Pegawai')"
            :description="__('Pilih sesi majlis untuk pratonton dan muat turun laporan PDF A4.')"
            :show-create="false"
        />

        <div class="mx-auto max-w-4xl">
            <section class="relative overflow-hidden rounded-2xl border border-border/70 bg-gradient-to-br from-card via-card to-violet-500/[0.06] p-5 shadow-sm ring-1 ring-border/40 sm:p-6 dark:to-violet-400/[0.12]">
                <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-violet-500/15 blur-3xl dark:bg-violet-400/25" aria-hidden="true"></div>
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-500 text-white shadow-lg shadow-violet-500/30">
                        <i class="ri-file-chart-line text-2xl" aria-hidden="true"></i>
                    </span>

                    <div class="min-w-0 flex-1 space-y-4">
                        <div class="space-y-1">
                            <h2 class="text-base font-semibold tracking-tight text-foreground sm:text-lg">{{ __('Jana laporan mengikut sesi') }}</h2>
                            <p class="text-sm leading-relaxed text-muted-foreground">
                                {{ __('Laporan memaparkan senarai pegawai tepat masa dan lewat dalam dua bahagian berasingan.') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                                {{ __('Jumlah sesi: :total', ['total' => number_format($sesiList->count())]) }}
                            </span>
                            @foreach ($sesiList->where('is_active', true)->take(2) as $activeSesi)
                                <a
                                    href="{{ route('admin.report.preview', ['sesi_id' => $activeSesi->id]) }}"
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 transition hover:bg-emerald-500/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40 dark:text-emerald-300 dark:hover:bg-emerald-500/15"
                                    aria-label="{{ __('Buka pratonton laporan untuk :sesi', ['sesi' => $activeSesi->sesi]) }}"
                                >
                                    <i class="ri-broadcast-line text-xs" aria-hidden="true"></i>
                                    {{ __('On Air: :sesi', ['sesi' => $activeSesi->sesi]) }}
                                </a>
                            @endforeach
                        </div>

                        <form method="GET" action="{{ route('admin.report.preview') }}" class="space-y-4">
                            <div class="space-y-2">
                                <label for="sesi_id" class="text-sm font-medium text-foreground">{{ __('Sesi Majlis') }}</label>
                                <select
                                    id="sesi_id"
                                    name="sesi_id"
                                    required
                                    class="block w-full rounded-xl border border-border bg-background px-3 py-2.5 text-sm text-foreground shadow-sm outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20"
                                >
                                    <option value="">{{ __('-- Pilih sesi --') }}</option>
                                    @foreach ($sesiList as $sesi)
                                        <option value="{{ $sesi->id }}" @selected((string) request('sesi_id') === (string) $sesi->id)>{{ $sesi->sesi }}</option>
                                    @endforeach
                                </select>
                                @error('sesi_id')
                                    <p class="text-xs text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-violet-500/30 transition hover:bg-violet-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                                >
                                    <i class="ri-eye-line text-base"></i>
                                    {{ __('Pratonton Laporan') }}
                                </button>
                                <p class="text-xs text-muted-foreground">
                                    {{ __('Pratonton memudahkan semakan sebelum muat turun PDF.') }}
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </x-kawalan-shell>
</x-dashboard-layout>
