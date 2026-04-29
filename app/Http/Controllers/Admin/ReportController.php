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
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    private const EXPORT_ALL = 'all';

    private const EXPORT_ONTIME = 'ontime';

    private const EXPORT_LATE = 'late';

    private const EXPORT_NOTATTEND = 'notattend';

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
        $reportCounts = $this->reportPreviewTableService->counts($sesi);

        return view('admin::report.preview', compact('sesi', 'reportCounts'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sesi_id' => ['required', 'integer', 'exists:sesi_majlis,id'],
            'section' => ['required', 'string', 'in:ontime,late,notattend'],
        ]);

        $sesi = SesiMajlis::query()->findOrFail((int) $validated['sesi_id']);
        $query = $this->reportPreviewTableService
            ->queryFor($sesi, $validated['section'])
            ->with(['ptj']);

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
            ->addColumn('ptj_name', fn (Pegawai $pegawai) => e((string) ($pegawai->ptj?->nama_ptj ?? '-')))
            ->removeColumn('ptj')
            ->removeColumn('no_kp')
            ->make(true);
    }

    public function download(Request $request): Response
    {
        $sesi = SesiMajlis::query()->findOrFail($this->validatedSesiId($request));
        $exportType = $this->validatedExportType($request);
        [$onTime, $late, $notAttendSlot] = $this->reportData($sesi, $exportType);
        $pdf = Pdf::loadView('admin::report.pdf', compact('sesi', 'onTime', 'late', 'notAttendSlot', 'exportType'))
            ->setPaper('a4', 'portrait');

        $filenameSuffix = match ($exportType) {
            self::EXPORT_ONTIME => 'tepat-masa',
            self::EXPORT_LATE => 'lewat',
            self::EXPORT_NOTATTEND => 'tidak-hadir-slot',
            default => 'semua',
        };

        return $pdf->download('laporan-kehadiran-'.$sesi->sesi.'-'.$filenameSuffix.'.pdf');
    }

    /**
     * @return array{0: Collection<int, Pegawai>, 1: Collection<int, Pegawai>, 2: Collection<int, Pegawai>}
     */
    private function reportData(SesiMajlis $sesi, string $exportType): array
    {
        $sesiId = (int) $sesi->id;
        $slot = (int) $sesi->s_kehadiran;

        $query = Pegawai::query()
            ->with(['ptj:id,nama_ptj'])
            ->where('sesi_majlis_id', $sesiId)
            ->select(['id', 'nama', 'ptj_id', 'no_kerusi', 'no_meja', 'is_late']);

        $onTime = collect();
        $late = collect();
        $notAttendSlot = collect();

        if ($exportType !== self::EXPORT_LATE && $exportType !== self::EXPORT_NOTATTEND) {
            $onTime = (clone $query)
                ->where('is_late', false)
                ->orderBy('no_kerusi')
                ->get();
        }

        if ($exportType !== self::EXPORT_ONTIME && $exportType !== self::EXPORT_NOTATTEND) {
            $late = (clone $query)
                ->where('is_late', true)
                ->orderBy('no_panggilan_lewat')
                ->orderBy('no_kerusi')
                ->get();
        }

        if ($exportType !== self::EXPORT_ONTIME && $exportType !== self::EXPORT_LATE) {
            $notAttendSlot = Pegawai::query()
                ->with(['ptj:id,nama_ptj'])
                ->where('s_kehadiran', $slot)
                ->where('is_attend', false)
                ->orderBy('no_kerusi')
                ->select(['id', 'nama', 'ptj_id', 'no_kerusi', 'no_meja', 'is_late'])
                ->get();
        }

        return [
            $onTime,
            $late,
            $notAttendSlot,
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
            'export_type' => ['nullable', 'in:all,ontime,late,notattend'],
        ]);

        return $validated['export_type'] ?? self::EXPORT_ALL;
    }
}
