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
            $table->foreignId('sesi_majlis_id')
                ->nullable()
                ->after('gred_id')
                ->constrained('sesi_majlis')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropForeign(['sesi_majlis_id']);
        });
    }
};
