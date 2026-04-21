<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->unsignedInteger('seat_offset')
                ->default(0)
                ->after('countdown_start_late');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->dropColumn('seat_offset');
        });
    }
};
