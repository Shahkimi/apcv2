@php
    $role = $layoutRole ?? 'admin';
@endphp
<x-dashboard-layout :title="__('Paparan kehadiran')" :role="$role">
    <div class="paparan-kehadiran min-h-[calc(100dvh-8rem)] rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-6 text-slate-100 shadow-inner sm:p-10 dark:from-slate-950 dark:to-slate-900">
        <header class="mb-8 text-center">
            <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">
                {{ __('Senarai kehadiran pegawai') }}
            </h1>
            @if ($activeSesi)
                <p class="mt-4 text-2xl text-slate-300">
                    {{ __('Sesi') }}: {{ $activeSesi->sesi }}
                    @if ($isLateSesi)
                        <span class="font-semibold text-amber-400">({{ __('Lewat') }})</span>
                    @endif
                </p>
            @endif
            <p class="mt-3 text-xl tabular-nums text-slate-400">
                {{ __('Jumlah hadir') }}: {{ number_format($pegawais->count()) }}
            </p>
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
            }, 30000);
        </script>
    @endpush
</x-dashboard-layout>
