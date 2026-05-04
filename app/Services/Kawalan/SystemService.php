<?php

declare(strict_types=1);

namespace App\Services\Kawalan;

use App\Models\Pegawai;

final class SystemService
{
    /**
     * Reset attendance-related fields for all pegawai records.
     *
     * @return int Number of rows updated
     */
    public function resetAllPegawai(): int
    {
        return Pegawai::query()->update([
            'sesi_majlis_id' => null,
            'no_meja' => 0,
            'no_panggilan_lewat' => 0,
            'is_attend' => false,
            'is_late' => false,
        ]);
    }
}
