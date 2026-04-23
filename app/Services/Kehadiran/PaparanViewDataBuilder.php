<?php

declare(strict_types=1);

namespace App\Services\Kehadiran;

use App\Models\SesiMajlis;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class PaparanViewDataBuilder
{
    public function __construct(
        private readonly KehadiranCallingService $callingService,
    ) {}

    /**
     * @return array{
     *   pegawais: Collection,
     *   selectedSesi: SesiMajlis|null,
     *   allSesis: \Illuminate\Database\Eloquent\Collection,
     *   isLateSesi: bool,
     *   ontimeCount: int,
     *   lateCount: int,
     *   refreshIntervalMs: int
     * }
     */
    public function buildForRequest(Request $request): array
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

        return [
            'pegawais' => $pegawais,
            'selectedSesi' => $selectedSesi,
            'allSesis' => SesiMajlis::query()->orderBy('id')->get(),
            'isLateSesi' => (bool) ($selectedSesi?->is_late),
            'ontimeCount' => $ontimeCount,
            'lateCount' => $lateCount,
            'refreshIntervalMs' => (int) Config::get('media.paparan_refresh_ms', 30_000),
        ];
    }
}
