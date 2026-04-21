<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Services\Kehadiran\KehadiranCallingService;
use Illuminate\View\View;

class PaparanController extends Controller
{
    public function __construct(
        private readonly KehadiranCallingService $callingService,
    ) {}

    public function index(): View
    {
        return view('admin::paparan.index', [
            'pegawais' => $this->callingService->attendedPegawaiForDisplay(),
            'activeSesi' => $this->callingService->activeOnAirSesi(),
            'isLateSesi' => $this->callingService->lateSessionOnAirExists(),
            'layoutRole' => 'media',
        ]);
    }
}
