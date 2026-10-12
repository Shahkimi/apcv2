<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Kehadiran\AbstractKehadiranController;
use App\Models\Backdrop;
use App\Models\Pegawai;
use App\Services\EventModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KehadiranController extends AbstractKehadiranController
{
    protected function bladeNamespace(): string
    {
        return 'user';
    }

    /**
     * Self check-in kiosk: officers key in their own No. KP on a numpad and
     * confirm attendance (confirmation itself goes through verify()).
     */
    public function kiosk(): View
    {
        $backdrop = Backdrop::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->first();

        return view('user::kiosk.index', [
            'activeSesiName' => $this->callingService->activeOnAirSesi()?->sesi,
            'eventTitle' => EventModeService::options()[$this->eventMode->current()],
            'backdropUrl' => $backdrop?->file_path ? $backdrop->image_url : null,
            'exitUrl' => route('user.dashboard'),
        ]);
    }

    public function kioskLookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'no_kp' => ['required', 'digits:12'],
        ], [
            'no_kp.digits' => __('No. KP mesti 12 digit.'),
        ]);

        $noKp = (string) $validated['no_kp'];

        // Imported IC numbers may be stored with dashes (e.g. 900101-01-1234).
        $pegawai = Pegawai::query()->where('no_kp', $noKp)->first()
            ?? Pegawai::query()->whereRaw("REPLACE(no_kp, '-', '') = ?", [$noKp])->first();

        if ($pegawai === null) {
            return response()->json([
                'success' => false,
                'message' => __('No. KP tidak ditemui.'),
            ], 404);
        }

        return response()->json($this->detailsPayload($pegawai));
    }
}
