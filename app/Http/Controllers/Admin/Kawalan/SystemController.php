<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Services\Kawalan\SystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class SystemController extends Controller
{
    public function __construct(
        private readonly SystemService $systemService
    ) {}

    public function index(): View
    {
        return view('admin::kawalan.system.index');
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

    private function jsonResetOk(int $affected): JsonResponse
    {
        return response()->json([
            'success' => true,
            'affected' => $affected,
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
