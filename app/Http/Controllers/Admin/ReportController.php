<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\SesiMajlis;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const EXPORT_ALL = 'all';

    private const EXPORT_ONTIME = 'ontime';

    private const EXPORT_LATE = 'late';

    public function index(): View
    {
        return view('admin::report.index', [
            'sesiList' => SesiMajlis::query()->orderBy('id')->get(),
        ]);
    }

    public function preview(Request $request): View
    {
        $sesi = SesiMajlis::query()->findOrFail($this->validatedSesiId($request));
        [$onTime, $late] = $this->reportData((int) $sesi->id, self::EXPORT_ALL);

        return view('admin::report.preview', compact('sesi', 'onTime', 'late'));
    }

    public function download(Request $request): Response
    {
        $sesi = SesiMajlis::query()->findOrFail($this->validatedSesiId($request));
        $exportType = $this->validatedExportType($request);
        [$onTime, $late] = $this->reportData((int) $sesi->id, $exportType);
        $pdf = Pdf::loadView('admin::report.pdf', compact('sesi', 'onTime', 'late', 'exportType'))
            ->setPaper('a4', 'portrait');

        $filenameSuffix = match ($exportType) {
            self::EXPORT_ONTIME => 'tepat-masa',
            self::EXPORT_LATE => 'lewat',
            default => 'semua',
        };

        return $pdf->download('laporan-kehadiran-'.$sesi->sesi.'-'.$filenameSuffix.'.pdf');
    }

    private function reportData(int $sesiId, string $exportType): array
    {
        $query = Pegawai::query()
            ->with(['ptj:id,nama_ptj'])
            ->where('sesi_majlis_id', $sesiId)
            ->select(['id', 'nama', 'ptj_id', 'no_kerusi', 'no_meja', 'is_late']);

        $onTime = collect();
        $late = collect();

        if ($exportType !== self::EXPORT_LATE) {
            $onTime = (clone $query)
                ->where('is_late', false)
                ->orderBy('no_kerusi')
                ->get();
        }

        if ($exportType !== self::EXPORT_ONTIME) {
            $late = (clone $query)
                ->where('is_late', true)
                ->orderBy('no_panggilan_lewat')
                ->orderBy('no_kerusi')
                ->get();
        }

        return [
            $onTime,
            $late,
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
            'export_type' => ['nullable', 'in:all,ontime,late'],
        ]);

        return $validated['export_type'] ?? self::EXPORT_ALL;
    }
}
