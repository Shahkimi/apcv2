<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SesiMajlis;
use App\Services\Kehadiran\KehadiranCallingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaparanController extends Controller
{
    public function __construct(
        private readonly KehadiranCallingService $callingService,
    ) {}

    public function index(Request $request): View
    {
        if (! $request->has('sesi_id')) {
            $activeSesi = $this->callingService->activeOnAirSesi();
            $selectedSesi = $activeSesi;
            $selectedSesiId = $activeSesi?->id;
        } elseif ($request->query('sesi_id') === '' || $request->query('sesi_id') === null) {
            $selectedSesi = null;
            $selectedSesiId = null;
        } else {
            $selectedSesiId = $request->integer('sesi_id');
            $selectedSesi = SesiMajlis::query()->find($selectedSesiId);
            if ($selectedSesi === null) {
                $selectedSesiId = null;
            }
        }

        $pegawais = $this->callingService->attendedPegawaiForDisplay($selectedSesiId);
        $ontimeCount = $pegawais->where('is_late', false)->count();
        $lateCount = $pegawais->where('is_late', true)->count();

        return view('admin::paparan.index', [
            'pegawais' => $pegawais,
            'selectedSesi' => $selectedSesi,
            'allSesis' => SesiMajlis::query()->orderBy('id')->get(),
            'isLateSesi' => (bool) ($selectedSesi?->is_late),
            'ontimeCount' => $ontimeCount,
            'lateCount' => $lateCount,
            'layoutRole' => 'admin',
        ]);
    }
}
