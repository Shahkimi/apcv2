<x-dashboard-layout :title="__('Analitik Senarai Kehadiran')" role="media">
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-700/60 bg-slate-900/70 p-6 text-slate-100">
            <form method="get" action="{{ route('media.senarai.analytics') }}" class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-300" for="analytics-sesi">
                        {{ __('Pilih sesi') }}
                    </label>
                    <select
                        id="analytics-sesi"
                        name="sesi_id"
                        class="w-full rounded-lg border border-slate-600 bg-slate-800/80 px-4 py-3 text-white focus:border-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-400/40"
                    >
                        <option value="">{{ __('Semua sesi') }}</option>
                        @foreach ($allSesis as $sesi)
                            <option value="{{ $sesi->id }}" @selected($selectedSesiId === $sesi->id)>{{ $sesi->sesi }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-purple-600 px-5 py-3 font-semibold text-white hover:bg-purple-500">
                    {{ __('Lihat Analitik') }}
                </button>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-700/60 bg-slate-900/70 p-5">
                <p class="text-sm text-slate-400">{{ __('Jumlah pegawai') }}</p>
                <p id="stat-total" class="mt-2 text-3xl font-bold text-white">{{ number_format((int) $progress['total_officers']) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-700/60 bg-emerald-950/40 p-5">
                <p class="text-sm text-emerald-300">{{ __('Telah diumumkan') }}</p>
                <p id="stat-announced" class="mt-2 text-3xl font-bold text-emerald-200">{{ number_format((int) $progress['announced_count']) }}</p>
            </div>
            <div class="rounded-xl border border-amber-700/60 bg-amber-950/40 p-5">
                <p class="text-sm text-amber-300">{{ __('Kemajuan') }}</p>
                <p id="stat-percent" class="mt-2 text-3xl font-bold text-amber-200">{{ number_format((float) $progress['progress_percent'], 1) }}%</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-700/60 bg-slate-900/70 p-6">
            <h2 class="text-lg font-semibold text-white">{{ __('Senarai pengumuman (dengan masa)') }}</h2>
            <p class="mt-1 text-sm text-slate-400">
                {{ __('Kedudukan semasa') }}: <span id="stat-position">{{ ((int) $progress['current_index']) + 1 }}</span>
            </p>

            <div id="announced-table-wrapper" class="mt-4 max-h-[28rem] overflow-auto">
                <table class="min-w-full divide-y divide-slate-700 text-sm">
                    <thead class="text-left text-slate-300">
                        <tr>
                            <th class="px-3 py-2">{{ __('Bil') }}</th>
                            <th class="px-3 py-2">{{ __('Nama') }}</th>
                            <th class="px-3 py-2">{{ __('Jawatan') }}</th>
                            <th class="px-3 py-2">{{ __('PTJ') }}</th>
                            <th class="px-3 py-2">{{ __('Masa diumumkan') }}</th>
                        </tr>
                    </thead>
                    <tbody id="announced-table" class="divide-y divide-slate-800 text-slate-200">
                        @forelse ($announcedOfficers as $idx => $officer)
                            <tr>
                                <td class="px-3 py-2">{{ $idx + 1 }}</td>
                                <td class="px-3 py-2">{{ $officer['nama'] }}</td>
                                <td class="px-3 py-2">{{ $officer['jawatan'] }}</td>
                                <td class="px-3 py-2">{{ $officer['ptj'] }}</td>
                                <td class="px-3 py-2">{{ $officer['announced_at'] ? \Carbon\Carbon::parse($officer['announced_at'])->format('d/m/Y H:i:s') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-slate-400">{{ __('Belum ada pengumuman direkodkan.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const sesiId = @json($selectedSesiId);
                const apiUrl = @json(route('media.senarai.progress.analytics'));
                const tableBody = document.getElementById('announced-table');
                const tableWrapper = document.getElementById('announced-table-wrapper');
                const emptyMessage = @json(__('Belum ada pengumuman direkodkan.'));
                const stats = {
                    total: document.getElementById('stat-total'),
                    announced: document.getElementById('stat-announced'),
                    percent: document.getElementById('stat-percent'),
                    position: document.getElementById('stat-position'),
                };

                let lastAnnouncedCount = @json((int) $progress['announced_count']);

                function escapeHtml(text) {
                    const div = document.createElement('div');
                    div.textContent = text ?? '—';
                    return div.innerHTML;
                }

                function formatTimestamp(value) {
                    if (!value) return '—';

                    const dt = new Date(value);
                    if (Number.isNaN(dt.getTime())) return '—';

                    return dt.toLocaleString('ms-MY', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    });
                }

                function renderRows(officers) {
                    if (!tableBody) return;

                    tableBody.innerHTML = '';

                    if (!Array.isArray(officers) || officers.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="5" class="px-3 py-6 text-center text-slate-400">${escapeHtml(emptyMessage)}</td></tr>`;
                        return;
                    }

                    officers.forEach((officer, idx) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="px-3 py-2">${idx + 1}</td>
                            <td class="px-3 py-2">${escapeHtml(officer.nama)}</td>
                            <td class="px-3 py-2">${escapeHtml(officer.jawatan)}</td>
                            <td class="px-3 py-2">${escapeHtml(officer.ptj)}</td>
                            <td class="px-3 py-2">${formatTimestamp(officer.announced_at)}</td>
                        `;
                        tableBody.appendChild(row);
                    });
                }

                async function pollAnalytics() {
                    const query = sesiId ? `?sesi_id=${encodeURIComponent(String(sesiId))}` : '';

                    try {
                        const response = await fetch(`${apiUrl}${query}`, { headers: { 'Accept': 'application/json' } });
                        if (!response.ok) return;

                        const data = await response.json();
                        const announcedCount = Number(data.announced_count ?? 0);

                        if (stats.total) stats.total.textContent = Number(data.total_officers ?? 0).toLocaleString();
                        if (stats.announced) stats.announced.textContent = announcedCount.toLocaleString();
                        if (stats.percent) stats.percent.textContent = `${Number(data.progress_percent ?? 0).toFixed(1)}%`;
                        if (stats.position) stats.position.textContent = String((Number(data.current_index ?? 0) + 1));

                        if (announcedCount !== lastAnnouncedCount) {
                            renderRows(data.announced_officers ?? []);
                            lastAnnouncedCount = announcedCount;

                            if (tableWrapper) {
                                tableWrapper.scrollTop = tableWrapper.scrollHeight;
                            }
                        }
                    } catch (error) {
                        /* retry on next interval */
                    }
                }

                setInterval(pollAnalytics, 5000);
            })();
        </script>
    @endpush
</x-dashboard-layout>
