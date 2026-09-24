<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->index(['is_attend', 'id'], 'pegawais_is_attend_id_index');
            $table->index('rsvp', 'pegawais_rsvp_index');
            $table->index(['sesi_majlis_id', 'no_panggilan_lewat'], 'pegawais_sesi_late_no_index');
        });

        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->index('is_active', 'sesi_majlis_is_active_index');
        });
    }

    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropIndex('pegawais_is_attend_id_index');
            $table->dropIndex('pegawais_rsvp_index');
            $table->dropIndex('pegawais_sesi_late_no_index');
        });

        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->dropIndex('sesi_majlis_is_active_index');
        });
    }
};
