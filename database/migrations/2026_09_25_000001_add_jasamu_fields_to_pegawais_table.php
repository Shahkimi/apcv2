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
            $table->date('tarikh_bersara')->nullable()->after('s_kehadiran');
            $table->foreignId('bersara_id')
                ->nullable()
                ->after('tarikh_bersara')
                ->constrained('bersaras')
                ->nullOnDelete();
            $table->unsignedSmallInteger('tempoh_berkhidmat')->nullable()->after('bersara_id');
        });
    }

    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bersara_id');
            $table->dropColumn(['tarikh_bersara', 'tempoh_berkhidmat']);
        });
    }
};
