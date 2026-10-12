<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Kawalan\SystemWipeRequest;
use App\Services\Kawalan\SystemService;
use App\Services\Kehadiran\KehadiranCallingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class SystemController extends Controller
{
    public function __construct(
        private readonly SystemService $systemService
    ) {}

    public function index(KehadiranCallingService $calling): View
    {
        return view('admin::kawalan.system.index', [
            'overview' => $this->systemService->overview(),
            'activeSesi' => $calling->activeOnAirSesi()?->sesi,
        ]);
    }

    public function reset(): JsonResponse
    {
        try {
            return $this->jsonResetOk($this->systemService->resetAllPegawai());
        } catch (Throwable $e) {
            report($e);

            return $this->jsonResetFail();
        }
    }

    public function wipe(SystemWipeRequest $request): JsonResponse
    {
        try {
            $deleted = $this->systemService->wipe($request->tables());
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('Padam data gagal. Sila cuba lagi.'),
            ], 500);
        }

        // No audit log in the app: keep a trace of who wiped what.
        Log::warning('Admin wiped data', ['user_id' => $request->user()?->id, 'deleted' => $deleted]);

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
            'counts' => $this->systemService->counts(),
            'overview' => $this->systemService->overview(),
            'message' => __('Berjaya memadam :summary.', ['summary' => $this->wipeSummary($deleted)]),
        ]);
    }

    /**
     * @param  array<string, int>  $deleted
     */
    private function wipeSummary(array $deleted): string
    {
        $labels = [
            'pegawai' => __('pegawai'),
            'jawatan' => __('jawatan'),
            'gred' => __('gred'),
        ];
        $parts = [];
        foreach ($deleted as $key => $count) {
            $parts[] = number_format($count).' '.($labels[$key] ?? $key);
        }

        return implode(', ', $parts);
    }

    private function jsonResetOk(int $affected): JsonResponse
    {
        return response()->json([
            'success' => true,
            'affected' => $affected,
            'overview' => $this->systemService->overview(),
            'message' => __('Berjaya menetapkan semula :count rekod pegawai.', ['count' => $affected]),
        ]);
    }

    private function jsonResetFail(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => __('Reset gagal. Sila cuba lagi.'),
        ], 500);
    }
}
