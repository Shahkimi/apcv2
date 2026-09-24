@php
    /** @var \Illuminate\Support\Collection $rows */
@endphp

@if ($rows->isEmpty())
    <p class="empty">{{ $emptyText }}</p>
@else
    <table>
        <thead>
            <tr>
                <th class="w-no">#</th>
                <th class="w-name">{{ __('Nama') }}</th>
                <th class="w-ptj">{{ __('PTJ') }}</th>
                @if ($isJasamu)
                    <th class="w-tarikh">{{ __('Tarikh Bersara') }}</th>
                    <th class="w-jenis">{{ __('Jenis Persaraan') }}</th>
                    <th class="w-tempoh">{{ __('Tempoh (thn)') }}</th>
                @endif
                <th class="w-kerusi">{{ __('No. Kerusi / No. Sijil') }}</th>
                <th class="w-meja">{{ __('Meja') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $pegawai)
                <tr>
                    <td class="w-no">{{ $index + 1 }}</td>
                    <td class="w-name">{{ $pegawai->nama }}</td>
                    <td class="w-ptj">{{ $pegawai->ptj?->nama_ptj ?? '-' }}</td>
                    @if ($isJasamu)
                        <td class="w-tarikh">{{ $pegawai->tarikh_bersara?->format('d/m/Y') ?? '-' }}</td>
                        <td class="w-jenis">{{ $pegawai->bersara?->jenis_bersara ?? '-' }}</td>
                        <td class="w-tempoh">{{ $pegawai->tempoh_berkhidmat ?? '-' }}</td>
                    @endif
                    <td class="w-kerusi">{{ $pegawai->no_kerusi ?? '-' }}</td>
                    <td class="w-meja">{{ $pegawai->no_meja ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
