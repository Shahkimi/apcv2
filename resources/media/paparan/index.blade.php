<x-dashboard-layout :title="__('Paparan kehadiran')" role="media">
    <div class="paparan-kehadiran min-h-[calc(100dvh-8rem)] rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-slate-100 shadow-inner sm:p-10 dark:from-slate-950 dark:to-slate-900">
        <header class="mb-8 text-center">
            <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">
                {{ __('Senarai kehadiran pegawai') }}
            </h1>

            <form method="get" action="{{ route('media.paparan.index') }}" class="mx-auto mt-6 max-w-xl" id="paparan-sesi-form">
                <label class="mb-2 block text-sm font-medium text-slate-300" for="paparan-sesi-select">
                    {{ __('Pilih sesi') }}
                </label>
                <select
                    id="paparan-sesi-select"
                    name="sesi_id"
                    class="w-full rounded-lg border border-slate-600 bg-slate-800/80 px-4 py-3 text-lg text-white focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/40"
                    onchange="this.form.submit()"
                >
                    <option value="" @selected($selectedSesi === null)>{{ __('Semua sesi') }}</option>
                    @foreach ($allSesis as $sesi)
                        <option value="{{ $sesi->id }}" @selected($selectedSesi?->id === $sesi->id)>
                            {{ $sesi->sesi }}
                        </option>
                    @endforeach
                </select>
            </form>

            @if ($selectedSesi)
                <p class="mt-4 text-2xl text-slate-300">
                    {{ __('Sesi') }}: {{ $selectedSesi->sesi }}
                    @if ($isLateSesi)
                        <span class="font-semibold text-amber-400">({{ __('Lewat') }})</span>
                    @endif
                </p>
            @endif

            <div class="mx-auto mt-6 grid max-w-2xl grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-600/80 bg-slate-800/50 px-4 py-3">
                    <p class="text-sm font-medium text-slate-400">{{ __('Jumlah hadir') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-white">{{ number_format($pegawais->count()) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-700/50 bg-emerald-950/40 px-4 py-3">
                    <p class="text-sm font-medium text-emerald-300/90">{{ __('Tepat masa') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-emerald-200">{{ number_format($ontimeCount) }}</p>
                </div>
                <div class="rounded-xl border border-amber-700/50 bg-amber-950/40 px-4 py-3">
                    <p class="text-sm font-medium text-amber-300/90">{{ __('Lewat') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-amber-200">{{ number_format($lateCount) }}</p>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-[1600px] overflow-x-auto rounded-xl border border-slate-700/80 bg-slate-900/40">
            <table class="w-full min-w-[720px] border-collapse text-left">
                <thead>
                    <tr class="border-b-2 border-slate-600 bg-slate-800/60">
                        <th class="p-4 text-xl font-bold text-white sm:text-2xl">{{ __('Bil.') }}</th>
                        <th class="p-4 text-xl font-bold text-amber-200 sm:text-2xl">{{ __('No. panggilan lewat') }}</th>
                        <th class="p-4 text-xl font-bold text-white sm:text-2xl">{{ __('Nama pegawai') }}</th>
                        <th class="p-4 text-center text-xl font-bold text-white sm:text-2xl">{{ __('No. kerusi') }}</th>
                        <th class="p-4 text-center text-xl font-bold text-white sm:text-2xl">{{ __('No. meja') }}</th>
                        <th class="p-4 text-xl font-bold text-white sm:text-2xl">{{ __('PTJ') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pegawais as $index => $pegawai)
                        <tr @class([
                            'border-b border-slate-700/90',
                            'bg-slate-800/25' => $index % 2 === 1,
                        ])>
                            <td class="p-4 text-lg tabular-nums text-slate-200 sm:text-xl">{{ $index + 1 }}</td>
                            <td class="p-4 text-lg font-semibold tabular-nums text-amber-300 sm:text-xl">
                                {{ $pegawai->no_panggilan_lewat ?? '—' }}
                            </td>
                            <td class="p-4 text-lg font-medium text-white sm:text-xl">{{ $pegawai->nama }}</td>
                            <td class="p-4 text-center text-lg tabular-nums text-slate-200 sm:text-xl">
                                {{ $pegawai->no_kerusi ?? '—' }}
                            </td>
                            <td class="p-4 text-center text-lg tabular-nums text-slate-200 sm:text-xl">
                                {{ $pegawai->no_meja ?? '—' }}
                            </td>
                            <td class="p-4 text-lg text-slate-200 sm:text-xl">{{ $pegawai->ptj?->nama_ptj ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-xl text-slate-400">
                                {{ __('Tiada pegawai yang ditanda hadir buat masa ini.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            setTimeout(function() {
                window.location.reload();
            }, {{ (int) $refreshIntervalMs }});
        </script>
    @endpush
</x-dashboard-layout>
