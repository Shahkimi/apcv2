<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * s_kehadiran: 0 = pagi, 1 = petang (sesi kehadiran).
     */
    public function up(): void
    {
        if (! Schema::hasTable('pegawais') || Schema::hasColumn('pegawais', 's_kehadiran')) {
            return;
        }

        Schema::table('pegawais', function (Blueprint $table) {
            $table->unsignedTinyInteger('s_kehadiran')
                ->default(0)
                ->comment('0 = pagi, 1 = petang');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pegawais') || ! Schema::hasColumn('pegawais', 's_kehadiran')) {
            return;
        }

        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropColumn('s_kehadiran');
        });
    }
};
