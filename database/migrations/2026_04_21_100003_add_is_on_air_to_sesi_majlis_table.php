<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // is_on_air removed; column dropped by 2026_04_22_100000_drop_is_on_air_from_sesi_majlis_table.
    }

    public function down(): void
    {
        //
    }
};
