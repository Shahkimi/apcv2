<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Senarai Nama untuk MC') }}</title>
    <style>
        @page { size: A4 portrait; margin: 16mm 12mm; }
        * { box-sizing: border-box; }
        /* DejaVu Sans ships with DomPDF and has the arrow / tick glyphs Arial lacks there. */
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 12px; }
        h1, h2, p { margin: 0; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 12px; padding-bottom: 8px; }
        .title { font-size: 20px; font-weight: 700; letter-spacing: .2px; }
        .meta { margin-top: 4px; font-size: 10px; color: #4b5563; }
        .range { margin-top: 6px; padding: 5px 8px; background: #eef2ff; border-left: 3px solid #4f46e5; font-size: 11px; color: #1e1b4b; }
        .range strong { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: auto; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid #d1d5db; padding: 7px 6px; vertical-align: middle; }
        th { background: #f3f4f6; text-align: left; font-size: 11px; }
        td { word-wrap: break-word; }
        .group td { background: #e5e7eb; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
        .group .count { font-weight: 400; text-transform: none; color: #4b5563; }
        .w-no { width: 8%; text-align: center; }
        .w-name { width: 44%; font-weight: 700; }
        .w-ptj { width: 28%; font-size: 11px; color: #374151; }
        .w-seat { width: 12%; text-align: center; font-size: 11px; }
        .w-tick { width: 8%; }
        .late { font-size: 9px; color: #b45309; font-weight: 400; }
        .empty { border: 1px dashed #9ca3af; padding: 10px; color: #6b7280; font-size: 12px; }
        .footer { margin-top: 14px; border-top: 1px solid #d1d5db; padding-top: 6px; color: #6b7280; font-size: 9px; text-align: center; }
    </style>
</head>
<body>
    <header class="header">
        <h1 class="title">{{ __('Senarai Nama untuk MC') }}</h1>
        <p class="meta">
            {{ __('Sesi: :sesi', ['sesi' => $sesi->sesi]) }} |
            {{ __('Susunan: :order', ['order' => implode(' → ', $priorityLabels)]) }} |
            {{ __('Jumlah: :total orang', ['total' => $range['total']]) }} |
            {{ __('Dijana: :time', ['time' => now()->format('d/m/Y H:i:s')]) }}
        </p>
        @if ($range['isRange'] || $range['skipAnnounced'])
            <p class="range">
                @if ($range['isRange'])
                    {{ __('Julat:') }} <strong>{{ __('Bil :from – :to', ['from' => $range['from'], 'to' => $range['to']]) }}</strong> ·
                @endif
                <strong>{{ $range['count'] }}</strong> {{ __('nama dicetak daripada :total', ['total' => $range['total']]) }}
                @if ($range['skipAnnounced'])
                    · {{ __(':n telah diumumkan dilangkau', ['n' => $range['skipped']]) }}
                @endif
            </p>
        @endif
    </header>

    @if ($mcGroups === [])
        <p class="empty">{{ $range['total'] === 0 ? __('Tiada pegawai hadir untuk sesi ini.') : __('Tiada pegawai dalam julat ini.') }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th class="w-no">{{ __('Bil.') }}</th>
                    <th class="w-name">{{ __('Nama') }}</th>
                    <th class="w-ptj">{{ __('PTJ') }}</th>
                    <th class="w-seat">{{ __('Kerusi / Meja') }}</th>
                    <th class="w-tick">&#10003;</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($mcGroups as $group)
                    <tr class="group">
                        <td colspan="5">
                            {{ $group['label'] }}@if ($group['continued']) <span class="count">{{ __('(sambungan)') }}</span>@endif
                            <span class="count">({{ $group['rows']->count() }} {{ __('orang') }})</span>
                        </td>
                    </tr>
                    @foreach ($group['rows'] as $row)
                        <tr>
                            <td class="w-no">{{ $row['bil'] }}</td>
                            <td class="w-name">
                                {{ $row['pegawai']->nama }}
                                @if ($row['pegawai']->is_late)
                                    <span class="late">({{ __('Lewat') }})</span>
                                @endif
                            </td>
                            <td class="w-ptj">{{ $row['pegawai']->ptj?->nama_ptj ?? '-' }}</td>
                            <td class="w-seat">{{ $row['pegawai']->no_kerusi ?? '-' }} / {{ $row['pegawai']->no_meja ?? '-' }}</td>
                            <td class="w-tick"></td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <footer class="footer">
        {{ __('Senarai mengikut susunan panggilan (Kawalan → Susunan Panggilan), sama seperti Layar Utama.') }}
    </footer>
</body>
</html>
