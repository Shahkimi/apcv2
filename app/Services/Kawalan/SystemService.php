<?php

declare(strict_types=1);

namespace App\Services\Kawalan;

use App\Models\AnnouncedOfficer;
use App\Models\Pegawai;
use Illuminate\Support\Facades\DB;

final class SystemService
{
    /**
     * Reset attendance-related fields for all pegawai records and clear senarai announcement rows.
     *
     * @return int Number of pegawai rows updated
     */
    public function resetAllPegawai(): int
    {
        return (int) DB::transaction(function (): int {
            AnnouncedOfficer::query()->delete();

            return Pegawai::query()->update([
                'sesi_majlis_id' => null,
                'no_meja' => 0,
                'no_panggilan_lewat' => 0,
                'is_attend' => false,
                'is_late' => false,
            ]);
        });
    }
}
