<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Services\Kehadiran\KehadiranCallingService;
use App\Services\Kehadiran\SenaraiProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SenaraiProgressController extends Controller
{
    public function __construct(
        private readonly KehadiranCallingService $callingService,
        private readonly SenaraiProgressService $progressService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $sesiId = $request->filled('sesi_id') ? $request->integer('sesi_id') : null;
        $totalOfficers = $this->callingService->attendedPegawaiForDisplay($sesiId)->count();

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
        $totalOfficers = $this->callingService->attendedPegawaiForDisplay($sesiId)->count();

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
        $totalOfficers = $this->callingService->attendedPegawaiForDisplay($sesiId)->count();
        $progress = $this->progressService->getProgress($sesiId, $totalOfficers);
        $announcedOfficers = $this->progressService->getAnnouncedOfficers($sesiId);

        return response()->json([
            ...$progress,
            'announced_officers' => $announcedOfficers,
        ]);
    }
}
