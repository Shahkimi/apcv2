<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Kawalan\CallingPriorityRequest;
use App\Models\Pegawai;
use App\Services\Kehadiran\CallingPriorityService;
use App\Services\Kehadiran\KehadiranCallingService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CallingPriorityController extends Controller
{
    public function __construct(
        private readonly CallingPriorityService $callingPriority,
    ) {}

    public function index(KehadiranCallingService $calling): View
    {
        $activeSesi = $calling->activeOnAirSesi();
        $counts = array_fill_keys(CallingPriorityService::DEFAULT_ORDER, null);

        if ($activeSesi !== null) {
            foreach (array_keys($counts) as $group) {
                $query = Pegawai::query()
                    ->where('sesi_majlis_id', $activeSesi->id)
                    ->where('is_attend', true);
                $this->callingPriority->applyGroupFilter($query, $group);
                $counts[$group] = $query->count();
            }
        }

        return view('admin::kawalan.susunan-panggilan.index', [
            'options' => CallingPriorityService::options(),
            'order' => $this->callingPriority->order(),
            'defaultOrder' => CallingPriorityService::DEFAULT_ORDER,
            'counts' => $counts,
            'activeSesi' => $activeSesi,
        ]);
    }

    public function update(CallingPriorityRequest $request): JsonResponse
    {
        $this->callingPriority->set($request->order());

        return response()->json(['success' => true, 'order' => $this->callingPriority->order()]);
    }
}
