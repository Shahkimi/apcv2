<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sesi_majlis') || Schema::hasColumn('sesi_majlis', 's_kehadiran')) {
            return;
        }

        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->unsignedTinyInteger('s_kehadiran')
                ->default(0)
                ->comment('0 = pagi, 1 = petang')
                ->after('seat_offset');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sesi_majlis') || ! Schema::hasColumn('sesi_majlis', 's_kehadiran')) {
            return;
        }

        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->dropColumn('s_kehadiran');
        });
    }
};
