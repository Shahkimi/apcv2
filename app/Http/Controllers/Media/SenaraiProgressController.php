<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\AnnouncedOfficer;
use App\Services\Kehadiran\KehadiranCallingService;
use App\Services\Kehadiran\SenaraiProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SenaraiProgressController extends Controller
{
    public function __construct(
        private readonly KehadiranCallingService $callingService,
        private readonly SenaraiProgressService $progressService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $sesiId = $request->filled('sesi_id') ? $request->integer('sesi_id') : null;
        $totalOfficers = $this->callingService->attendedPegawaiCount($sesiId);

        return response()->json($this->progressService->getProgress($sesiId, $totalOfficers));
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'sesi_id' => ['nullable', 'integer', 'min:1'],
            'index' => ['required', 'integer', 'min:0'],
            'pegawai_id' => ['required', 'integer', 'min:1'],
        ]);

        $sesiId = isset($payload['sesi_id']) ? (int) $payload['sesi_id'] : null;
        $totalOfficers = $this->callingService->attendedPegawaiCount($sesiId);

        return response()->json(
            $this->progressService->updateProgress(
                $sesiId,
                (int) $payload['index'],
                (int) $payload['pegawai_id'],
                $totalOfficers
            )
        );
    }

    public function analytics(Request $request): JsonResponse
    {
        $sesiId = $request->filled('sesi_id') ? $request->integer('sesi_id') : null;
        $totalOfficers = $this->callingService->attendedPegawaiCount($sesiId);

        return response()->json($this->progressService->getProgress($sesiId, $totalOfficers));
    }

    public function announcedDatatable(Request $request): JsonResponse
    {
        $sesiId = $request->filled('sesi_id') ? $request->integer('sesi_id') : null;

        $query = $this->progressService->announcedOfficersQuery($sesiId)
            ->with([
                'pegawai:id,nama,jawatan_id,ptj_id',
                'pegawai.jawatan:id,desc_jawatan',
                'pegawai.ptj:id,nama_ptj',
            ]);

        $totalOfficers = $this->callingService->attendedPegawaiCount($sesiId);

        return DataTables::of($query)
            ->addIndexColumn()
            ->filterColumn('nama', function ($query, $keyword) {
                $like = '%'.$keyword.'%';
                $query->whereHas('pegawai', function ($q) use ($like) {
                    $q->where('nama', 'like', $like);
                });
            })
            ->addColumn('nama', fn (AnnouncedOfficer $r) => e($r->pegawai?->nama ?? '—'))
            ->addColumn('jawatan', fn (AnnouncedOfficer $r) => e($r->pegawai?->jawatan?->desc_jawatan ?? '—'))
            ->addColumn('ptj', fn (AnnouncedOfficer $r) => e($r->pegawai?->ptj?->nama_ptj ?? '—'))
            ->addColumn('announced_at_iso', fn (AnnouncedOfficer $r) => $r->announced_at?->toIso8601String())
            ->editColumn('announced_at', fn (AnnouncedOfficer $r) => e($r->announced_at?->format('d/m/Y H:i:s') ?? '—'))
            ->removeColumn('pegawai')
            ->with('stats', $this->progressService->getProgress($sesiId, $totalOfficers))
            ->make(true);
    }
}
