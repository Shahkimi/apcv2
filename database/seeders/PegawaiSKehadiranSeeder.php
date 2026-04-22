<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Pegawai;
use Illuminate\Database\Seeder;

/**
 * Sets jenis kehadiran by seat number: no_kerusi 1–19 → pagi, 20–30 → petang.
 * Aligns with ReferenceDataSeeder (30 pegawai, no_kerusi 1..30).
 */
class PegawaiSKehadiranSeeder extends Seeder
{
    public function run(): void
    {
        Pegawai::query()
            ->whereBetween('no_kerusi', [1, 19])
            ->update(['s_kehadiran' => Pegawai::S_KEHADIRAN_PAGI]);

        Pegawai::query()
            ->whereBetween('no_kerusi', [20, 30])
            ->update(['s_kehadiran' => Pegawai::S_KEHADIRAN_PETANG]);
    }
}
