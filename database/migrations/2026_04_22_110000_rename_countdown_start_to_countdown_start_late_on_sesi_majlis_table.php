<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sesi_majlis')) {
            return;
        }

        if (Schema::hasColumn('sesi_majlis', 'countdown_start_late')) {
            return;
        }

        if (! Schema::hasColumn('sesi_majlis', 'countdown_start')) {
            return;
        }

        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->renameColumn('countdown_start', 'countdown_start_late');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sesi_majlis')) {
            return;
        }

        if (Schema::hasColumn('sesi_majlis', 'countdown_start')) {
            return;
        }

        if (! Schema::hasColumn('sesi_majlis', 'countdown_start_late')) {
            return;
        }

        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->renameColumn('countdown_start_late', 'countdown_start');
        });
    }
};
