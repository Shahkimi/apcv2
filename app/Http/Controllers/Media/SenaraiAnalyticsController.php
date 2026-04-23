<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\SesiMajlis;
use App\Services\Kehadiran\KehadiranCallingService;
use App\Services\Kehadiran\SenaraiProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SenaraiAnalyticsController extends Controller
{
    public function __construct(
        private readonly KehadiranCallingService $callingService,
        private readonly SenaraiProgressService $progressService,
    ) {}

    public function index(Request $request): View
    {
        $sesiId = $request->filled('sesi_id') ? $request->integer('sesi_id') : null;
        $totalOfficers = $this->callingService->attendedPegawaiForDisplay($sesiId)->count();
        $progress = $this->progressService->getProgress($sesiId, $totalOfficers);

        return view('media::senarai.analytics', [
            'allSesis' => SesiMajlis::query()->orderBy('id')->get(),
            'selectedSesiId' => $sesiId,
            'progress' => $progress,
            'announcedOfficers' => $this->progressService->getAnnouncedOfficers($sesiId),
        ]);
    }
}
