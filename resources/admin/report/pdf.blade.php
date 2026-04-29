<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Laporan Kehadiran Pegawai') }}</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #1f2937; font-size: 11px; }
        h1, h2, p { margin: 0; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 14px; padding-bottom: 8px; }
        .title { font-size: 18px; font-weight: 700; letter-spacing: .2px; }
        .meta { margin-top: 4px; font-size: 10px; color: #4b5563; }
        /* Avoid page-break-inside on whole section: long tables get pushed to page 2 and leave a blank first page in DomPDF. */
        .section { margin-top: 16px; page-break-inside: auto; }
        .section-title { font-size: 13px; font-weight: 700; margin-bottom: 8px; page-break-after: avoid; }
        .count { font-weight: 400; color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: auto; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-size: 10px; }
        td { font-size: 10px; word-wrap: break-word; }
        .w-no { width: 6%; }
        .w-name { width: 30%; }
        .w-ptj { width: 32%; }
        .w-kerusi { width: 16%; text-align: center; }
        .w-meja { width: 16%; text-align: center; }
        .empty { border: 1px dashed #9ca3af; padding: 8px; color: #6b7280; font-size: 10px; }
        .footer { margin-top: 14px; border-top: 1px solid #d1d5db; padding-top: 6px; color: #6b7280; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    @php
        $scope = $exportType ?? 'all';
        $showOnTime = $scope === 'all' || $scope === 'ontime';
        $showLate = $scope === 'all' || $scope === 'late';
        $showNotAttendSlot = $scope === 'all' || $scope === 'notattend';
        $scopeLabel = match ($scope) {
            'ontime' => __('Tepat Masa'),
            'late' => __('Lewat'),
            'notattend' => __('Tidak Hadir (Slot)'),
            default => __('Semua'),
        };
    @endphp

    <header class="header">
        <h1 class="title">{{ __('Laporan Kehadiran Pegawai') }}</h1>
        <p class="meta">
            {{ __('Sesi: :sesi', ['sesi' => $sesi->sesi]) }} |
            {{ __('Skop: :scope', ['scope' => $scopeLabel]) }} |
            {{ __('Dijana: :time', ['time' => now()->format('d/m/Y H:i:s')]) }} |
            {{ __('Saiz: A4 Portrait') }}
        </p>
    </header>

    @if ($showOnTime)
        <section class="section">
            <h2 class="section-title">{{ __('Pegawai Tepat Masa') }} <span class="count">({{ $onTime->count() }} {{ __('orang') }})</span></h2>
            @if ($onTime->isEmpty())
                <p class="empty">{{ __('Tiada pegawai tepat masa untuk sesi ini.') }}</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th class="w-no">#</th>
                            <th class="w-name">{{ __('Nama') }}</th>
                            <th class="w-ptj">{{ __('PTJ') }}</th>
                            <th class="w-kerusi">{{ __('No. Kerusi / No. Sijil') }}</th>
                            <th class="w-meja">{{ __('Meja') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($onTime as $index => $pegawai)
                            <tr>
                                <td class="w-no">{{ $index + 1 }}</td>
                                <td class="w-name">{{ $pegawai->nama }}</td>
                                <td class="w-ptj">{{ $pegawai->ptj?->nama_ptj ?? '-' }}</td>
                                <td class="w-kerusi">{{ $pegawai->no_kerusi ?? '-' }}</td>
                                <td class="w-meja">{{ $pegawai->no_meja ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif

    @if ($showLate)
        <section class="section">
            <h2 class="section-title">{{ __('Pegawai Lewat') }} <span class="count">({{ $late->count() }} {{ __('orang') }})</span></h2>
            @if ($late->isEmpty())
                <p class="empty">{{ __('Tiada pegawai lewat untuk sesi ini.') }}</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th class="w-no">#</th>
                            <th class="w-name">{{ __('Nama') }}</th>
                            <th class="w-ptj">{{ __('PTJ') }}</th>
                            <th class="w-kerusi">{{ __('No. Kerusi / No. Sijil') }}</th>
                            <th class="w-meja">{{ __('Meja') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($late as $index => $pegawai)
                            <tr>
                                <td class="w-no">{{ $index + 1 }}</td>
                                <td class="w-name">{{ $pegawai->nama }}</td>
                                <td class="w-ptj">{{ $pegawai->ptj?->nama_ptj ?? '-' }}</td>
                                <td class="w-kerusi">{{ $pegawai->no_kerusi ?? '-' }}</td>
                                <td class="w-meja">{{ $pegawai->no_meja ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif

    @if ($showNotAttendSlot)
        <section class="section">
            <h2 class="section-title">{{ __('Pegawai Tidak Hadir (Slot Sesi)') }} <span class="count">({{ $notAttendSlot->count() }} {{ __('orang') }})</span></h2>
            @if ($notAttendSlot->isEmpty())
                <p class="empty">{{ __('Tiada pegawai dalam kategori ini untuk sesi ini.') }}</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th class="w-no">#</th>
                            <th class="w-name">{{ __('Nama') }}</th>
                            <th class="w-ptj">{{ __('PTJ') }}</th>
                            <th class="w-kerusi">{{ __('No. Kerusi / No. Sijil') }}</th>
                            <th class="w-meja">{{ __('Meja') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notAttendSlot as $index => $pegawai)
                            <tr>
                                <td class="w-no">{{ $index + 1 }}</td>
                                <td class="w-name">{{ $pegawai->nama }}</td>
                                <td class="w-ptj">{{ $pegawai->ptj?->nama_ptj ?? '-' }}</td>
                                <td class="w-kerusi">{{ $pegawai->no_kerusi ?? '-' }}</td>
                                <td class="w-meja">{{ $pegawai->no_meja ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif

    <footer class="footer">
        {{ __('Laporan ini dijana secara automatik oleh sistem APCv2.') }}
    </footer>
</body>
</html>
