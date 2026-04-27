<x-dashboard-layout :title="__('Analitik Senarai Kehadiran')" role="media">
    <div class="min-h-[calc(100dvh-6rem)] space-y-6 bg-[#F9FAFB] p-4 sm:p-6">
        {{-- Filter bar --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <form method="get" action="{{ route('media.senarai.analytics') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <label class="mb-2 block text-xs font-medium text-gray-500" for="analytics-sesi">
                        {{ __('Pilih sesi') }}
                    </label>
                    <select
                        id="analytics-sesi"
                        name="sesi_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 shadow-sm focus:border-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-900/10"
                    >
                        <option value="">{{ __('Semua sesi') }}</option>
                        @foreach ($allSesis as $sesi)
                            <option value="{{ $sesi->id }}" @selected($selectedSesiId === $sesi->id)>{{ $sesi->sesi }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="shrink-0 rounded-lg bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                    {{ __('Lihat Analitik') }}
                </button>
            </form>
        </div>

        {{-- Summary stats --}}
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">{{ __('Jumlah pegawai') }}</p>
                <p id="stat-total" class="mt-3 text-3xl font-bold tabular-nums text-gray-900">{{ number_format((int) $progress['total_officers']) }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/90 p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-800">{{ __('Telah diumumkan') }}</p>
                <p id="stat-announced" class="mt-3 text-3xl font-bold tabular-nums text-emerald-900">{{ number_format((int) $progress['announced_count']) }}</p>
            </div>
            <div class="rounded-xl border border-orange-200/80 bg-orange-50/90 p-5 shadow-sm">
                <p class="text-sm font-medium text-orange-900/90">{{ __('Kemajuan') }}</p>
                <p id="stat-percent" class="mt-3 text-3xl font-bold tabular-nums text-orange-950">{{ number_format((float) $progress['progress_percent'], 1) }}%</p>
            </div>
        </div>

        {{-- Table card --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Senarai pengumuman (dengan masa)') }}</h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('Kedudukan semasa') }}: <span id="stat-position" class="font-medium text-gray-900">{{ ((int) $progress['current_index']) + 1 }}</span>
            </p>

            <div id="announced-table-wrapper" class="mt-5 max-h-[28rem] overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="sticky top-0 bg-gray-50 text-left">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Bil') }}</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Nama') }}</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Jawatan') }}</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('PTJ') }}</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600">{{ __('Masa diumumkan') }}</th>
                        </tr>
                    </thead>
                    <tbody id="announced-table" class="divide-y divide-gray-100 bg-white text-gray-900">
                        @forelse ($announcedOfficers as $idx => $officer)
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-4 py-3 tabular-nums text-gray-700">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3 font-medium">{{ $officer['nama'] }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $officer['jawatan'] }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $officer['ptj'] }}</td>
                                <td class="px-4 py-3 tabular-nums text-gray-700">{{ $officer['announced_at'] ? \Carbon\Carbon::parse($officer['announced_at'])->format('d/m/Y H:i:s') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('Belum ada pengumuman direkodkan.') }}</td>
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
                        tableBody.innerHTML = `<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">${escapeHtml(emptyMessage)}</td></tr>`;
                        return;
                    }

                    officers.forEach((officer, idx) => {
                        const row = document.createElement('tr');
                        row.className = 'hover:bg-gray-50/80';
                        row.innerHTML = `
                            <td class="px-4 py-3 tabular-nums text-gray-700">${idx + 1}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">${escapeHtml(officer.nama)}</td>
                            <td class="px-4 py-3 text-gray-700">${escapeHtml(officer.jawatan)}</td>
                            <td class="px-4 py-3 text-gray-700">${escapeHtml(officer.ptj)}</td>
                            <td class="px-4 py-3 tabular-nums text-gray-700">${formatTimestamp(officer.announced_at)}</td>
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
