@php
    $isLate = ($tone ?? null) === 'late';
    $rowBadgeClass = $isLate
        ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300'
        : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
@endphp

@if ($rows->isEmpty())
    <p class="rounded-xl border border-dashed border-border/80 bg-background/70 px-3 py-2 text-sm text-muted-foreground">
        {{ $emptyText }}
    </p>
@else
    <div class="overflow-x-auto rounded-xl border border-border/70 bg-card">
        <table class="min-w-full divide-y divide-border text-sm">
            <thead class="bg-muted/40 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                <tr>
                    <th class="w-12 px-3 py-2 text-center">#</th>
                    <th class="px-3 py-2">{{ __('Nama') }}</th>
                    <th class="px-3 py-2">{{ __('PTJ') }}</th>
                    <th class="px-3 py-2 text-center">{{ __('No. Kerusi') }}</th>
                    <th class="px-3 py-2 text-center">{{ __('No. Meja') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/60 text-foreground">
                @foreach ($rows as $index => $pegawai)
                    <tr
                        class="js-report-row transition hover:bg-muted/30"
                        data-search="{{ strtolower(trim(($pegawai->nama ?? '').' '.($pegawai->ptj?->nama_ptj ?? ''))) }}"
                    >
                        <td class="px-3 py-2 text-center text-xs font-semibold text-muted-foreground">{{ $index + 1 }}</td>
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full px-1 text-[10px] font-semibold {{ $rowBadgeClass }}">
                                    {{ $index + 1 }}
                                </span>
                                <span class="font-medium text-foreground">{{ $pegawai->nama }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-muted-foreground">{{ $pegawai->ptj?->nama_ptj ?? '-' }}</td>
                        <td class="px-3 py-2 text-center font-medium tabular-nums">{{ $pegawai->no_kerusi ?? '-' }}</td>
                        <td class="px-3 py-2 text-center font-medium tabular-nums">{{ $pegawai->no_meja ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
