<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\SesiMajlis;
use App\Services\Report\ReportPreviewTableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    private const EXPORT_ALL = 'all';

    private const EXPORT_MC = 'mc';

    private const PDF_RELATIONS = ['ptj:id,nama_ptj', 'bersara:id,jenis_bersara'];

    /** Rough rows per A4 page of the MC PDF, for the print panel estimate. */
    private const MC_ROWS_PER_PAGE = 30;

    public function __construct(
        private readonly ReportPreviewTableService $reportPreviewTableService
    ) {}

    public function index(): View
    {
        return view('admin::report.index', [
            'sesiList' => SesiMajlis::query()->orderBy('id')->get(),
        ]);
    }

    public function preview(Request $request): View
    {
        $sesi = SesiMajlis::query()->findOrFail($this->validatedSesiId($request));

        return view('admin::report.preview', [
            'sesi' => $sesi,
            'sections' => $this->reportPreviewTableService->sections(),
            'reportCounts' => $this->reportPreviewTableService->counts($sesi),
            'mcGroups' => $this->reportPreviewTableService->mcGroups($sesi),
            'announcedIds' => $this->reportPreviewTableService->announcedIds($sesi),
        ]);
    }

    /**
     * Live summary for the MC print panel (range / skip announced) before downloading the PDF.
     */
    public function mcPreview(Request $request): JsonResponse
    {
        $sesi = SesiMajlis::query()->findOrFail($this->validatedSesiId($request));
        [$from, $to, $skipAnnounced] = $this->validatedMcRange($request);
        $selection = $this->reportPreviewTableService->mcSelection($sesi, $from, $to, $skipAnnounced);
        $rows = $selection['rows'];
        $groupCount = count($this->reportPreviewTableService->groupRows($rows, $selection['all']));
        $point = fn (?array $row): ?array => $row === null ? null : ['bil' => $row['bil'], 'nama' => (string) $row['pegawai']->nama];

        return response()->json([
            'total' => $selection['total'],
            'from' => $selection['from'],
            'to' => $selection['to'],
            'count' => $selection['count'],
            'skipped' => $selection['skipped'],
            'announced_count' => $selection['announcedCount'],
            'remaining' => $selection['total'] - $selection['announcedCount'],
            'all_announced' => $selection['total'] > 0 && $selection['announcedCount'] >= $selection['total'],
            'first_unannounced' => $selection['firstUnannounced'],
            'last_announced' => $selection['lastAnnounced'],
            // No "next" once the last announced officer is the end of the list.
            'next_start' => $selection['lastAnnounced'] === null || $selection['lastAnnounced']['bil'] >= $selection['total']
                ? null
                : $selection['lastAnnounced']['bil'] + 1,
            'first' => $point($rows->first()),
            'last' => $point($rows->last()),
            'sample' => $rows->take(5)->map($point)->values(),
            'pages_estimate' => $rows->isEmpty() ? 0 : (int) ceil(($rows->count() + $groupCount) / self::MC_ROWS_PER_PAGE),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function datatable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sesi_id' => ['required', 'integer', 'exists:sesi_majlis,id'],
            'section' => ['required', 'string', Rule::in(array_keys($this->reportPreviewTableService->sections()))],
        ]);

        $sesi = SesiMajlis::query()->findOrFail((int) $validated['sesi_id']);
        $query = $this->reportPreviewTableService
            ->queryFor($sesi, $validated['section'])
            ->with(['ptj', 'bersara']);

        return DataTables::of($query)
            ->filterColumn('nama', function (Builder $query, string $keyword): void {
                if (trim($keyword) === '') {
                    return;
                }

                $like = '%'.mb_strtolower($keyword, 'UTF-8').'%';

                $query->where(function ($q) use ($like): void {
                    $q->whereRaw('LOWER(nama) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(no_kp) LIKE ?', [$like]);
                });
            })
            ->filterColumn('ptj_name', function (Builder $query, string $keyword): void {
                if (trim($keyword) === '') {
                    return;
                }

                $like = '%'.mb_strtolower($keyword, 'UTF-8').'%';

                $query->whereHas('ptj', function (Builder $q) use ($like): void {
                    $q->whereRaw('LOWER(nama_ptj) LIKE ?', [$like]);
                });
            })
            ->editColumn('nama', fn (Pegawai $pegawai) => e((string) $pegawai->nama))
            ->editColumn('no_kerusi', fn (Pegawai $pegawai) => e((string) ($pegawai->no_kerusi ?? '-')))
            ->editColumn('no_meja', fn (Pegawai $pegawai) => e((string) ($pegawai->no_meja ?? '-')))
            ->editColumn('tarikh_bersara', fn (Pegawai $pegawai) => e($pegawai->tarikh_bersara?->format('d/m/Y') ?? '-'))
            ->editColumn('tempoh_berkhidmat', fn (Pegawai $pegawai) => e((string) ($pegawai->tempoh_berkhidmat ?? '-')))
            ->addColumn('ptj_name', fn (Pegawai $pegawai) => e((string) ($pegawai->ptj?->nama_ptj ?? '-')))
            ->addColumn('bersara_name', fn (Pegawai $pegawai) => e((string) ($pegawai->bersara?->jenis_bersara ?? '-')))
            ->addColumn('status_label', fn (Pegawai $pegawai) => $pegawai->is_late ? e(__('Lewat')) : e(__('Tepat masa')))
            ->removeColumn('ptj')
            ->removeColumn('bersara')
            ->removeColumn('no_kp')
            ->make(true);
    }

    public function download(Request $request): Response
    {
        $sesi = SesiMajlis::query()->findOrFail($this->validatedSesiId($request));
        $exportType = $this->validatedExportType($request);

        if ($exportType === self::EXPORT_MC) {
            return $this->downloadMcList($request, $sesi);
        }

        $sections = $this->reportPreviewTableService->sections();
        $selected = $exportType === self::EXPORT_ALL ? array_keys($sections) : [$exportType];
        $reportSections = [];

        foreach ($selected as $key) {
            $reportSections[$key] = [
                'label' => $sections[$key]['label'],
                'rows' => $this->reportPreviewTableService->queryFor($sesi, $key)->with(self::PDF_RELATIONS)->get(),
            ];
        }

        $scopeLabel = $exportType === self::EXPORT_ALL ? __('Semua') : $sections[$exportType]['short'];
        $pdf = Pdf::loadView('admin::report.pdf', compact('sesi', 'reportSections', 'scopeLabel'))
            ->setPaper('a4', 'portrait');

        $filenameSuffix = $exportType === self::EXPORT_ALL ? 'semua' : $exportType;

        return $pdf->download('laporan-kehadiran-'.$sesi->sesi.'-'.$filenameSuffix.'.pdf');
    }

    private function downloadMcList(Request $request, SesiMajlis $sesi): Response
    {
        [$from, $to, $skipAnnounced] = $this->validatedMcRange($request);
        $selection = $this->reportPreviewTableService->mcSelection($sesi, $from, $to, $skipAnnounced);

        $filename = 'senarai-mc-'.$sesi->sesi;
        if ($selection['isRange']) {
            $filename .= '-bil-'.$selection['from'].'-'.$selection['to'];
        }
        if ($skipAnnounced) {
            $filename .= '-belum-diumumkan';
        }

        return Pdf::loadView('admin::report.mc-pdf', [
            'sesi' => $sesi,
            'mcGroups' => $this->reportPreviewTableService->groupRows($selection['rows'], $selection['all']),
            'range' => $selection,
            'priorityLabels' => array_column(array_filter(
                $this->reportPreviewTableService->sections(),
                fn (array $section): bool => $section['attended'],
            ), 'short'),
        ])
            ->setPaper('a4', 'portrait')
            ->download($filename.'.pdf');
    }

    /**
     * Optional MC print range: Bil from / to (inclusive, to empty = end of list) and skip announced.
     *
     * @return array{0: int|null, 1: int|null, 2: bool}
     */
    private function validatedMcRange(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'integer', 'min:1'],
            'to' => array_filter(['nullable', 'integer', 'min:1', $request->filled('from') ? 'gte:from' : null]),
            'skip_announced' => ['nullable', 'boolean'],
        ]);

        return [
            isset($validated['from']) ? (int) $validated['from'] : null,
            isset($validated['to']) ? (int) $validated['to'] : null,
            (bool) ($validated['skip_announced'] ?? false),
        ];
    }

    private function validatedSesiId(Request $request): int
    {
        /** @var array{sesi_id:int|string} $validated */
        $validated = $request->validate([
            'sesi_id' => ['required', 'integer', 'exists:sesi_majlis,id'],
        ]);

        return (int) $validated['sesi_id'];
    }

    private function validatedExportType(Request $request): string
    {
        /** @var array{export_type?: string} $validated */
        $validated = $request->validate([
            'export_type' => ['nullable', Rule::in([
                self::EXPORT_ALL,
                self::EXPORT_MC,
                ...array_keys($this->reportPreviewTableService->sections()),
            ])],
        ]);

        return $validated['export_type'] ?? self::EXPORT_ALL;
    }
}
