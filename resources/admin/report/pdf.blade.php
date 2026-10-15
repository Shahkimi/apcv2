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
        .w-name { width: 28%; }
        .w-ptj { width: 26%; }
        .w-status { width: 12%; text-align: center; }
        .w-kerusi { width: 14%; text-align: center; }
        .w-meja { width: 14%; text-align: center; }
        .w-tarikh { width: 10%; text-align: center; }
        .w-jenis { width: 10%; }
        .w-tempoh { width: 8%; text-align: center; }
        body.jasamu .w-name { width: 20%; }
        body.jasamu .w-ptj { width: 16%; }
        body.jasamu .w-status { width: 10%; }
        body.jasamu .w-kerusi { width: 10%; }
        body.jasamu .w-meja { width: 10%; }
        .empty { border: 1px dashed #9ca3af; padding: 8px; color: #6b7280; font-size: 10px; }
        .footer { margin-top: 14px; border-top: 1px solid #d1d5db; padding-top: 6px; color: #6b7280; font-size: 9px; text-align: center; }
    </style>
</head>
<body class="{{ $isJasamu ? 'jasamu' : '' }}">
    <header class="header">
        <h1 class="title">{{ __('Laporan Kehadiran Pegawai') }}</h1>
        <p class="meta">
            {{ __('Sesi: :sesi', ['sesi' => $sesi->sesi]) }} |
            {{ __('Skop: :scope', ['scope' => $scopeLabel]) }} |
            {{ __('Dijana: :time', ['time' => now()->format('d/m/Y H:i:s')]) }} |
            {{ __('Saiz: A4 Portrait') }}
        </p>
    </header>

    @foreach ($reportSections as $key => $section)
        <section class="section">
            <h2 class="section-title">{{ $section['label'] }} <span class="count">({{ $section['rows']->count() }} {{ __('orang') }})</span></h2>
            @include('admin::report.partials.pdf-table', [
                'rows' => $section['rows'],
                'showStatus' => $key !== \App\Services\Report\ReportPreviewTableService::SECTION_NOTATTEND,
                'emptyText' => __('Tiada pegawai dalam kategori ini untuk sesi ini.'),
            ])
        </section>
    @endforeach

    <footer class="footer">
        {{ __('Laporan ini dijana secara automatik oleh sistem APCv2.') }}
    </footer>
</body>
</html>
