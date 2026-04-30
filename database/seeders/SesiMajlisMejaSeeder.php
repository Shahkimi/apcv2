<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Meja;
use App\Models\SesiMajlis;
use Illuminate\Database\Seeder;

class SesiMajlisMejaSeeder extends Seeder
{
    public function run(): void
    {
        SesiMajlis::query()->updateOrCreate(
            ['sesi' => 'Pagi'],
            [
                'is_active' => true,
                'is_late' => false,
                'countdown_start_late' => 1600,
                'seat_offset' => 0,
                's_kehadiran' => SesiMajlis::S_KEHADIRAN_PAGI,
            ],
        );

        SesiMajlis::query()->updateOrCreate(
            ['sesi' => 'Petang'],
            [
                'is_active' => false,
                'is_late' => false,
                'countdown_start_late' => 2600,
                'seat_offset' => 10,
                's_kehadiran' => SesiMajlis::S_KEHADIRAN_PETANG,
            ],
        );

        Meja::query()->firstOrCreate(
            ['sizing' => 10],
        );
    }
}
