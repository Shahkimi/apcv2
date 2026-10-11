<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EventModeService;
use App\Services\Kehadiran\KehadiranCallingService;
use Illuminate\Http\JsonResponse;

class SidebarStatusController extends Controller
{
    public function __construct(
        private readonly KehadiranCallingService $calling,
        private readonly EventModeService $eventMode,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'sesi' => $this->calling->activeOnAirSesi()?->sesi,
            'mode' => EventModeService::options()[$this->eventMode->current()] ?? null,
        ])->header('Cache-Control', 'no-store');
    }
}
