<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sesi_majlis') || ! Schema::hasColumn('sesi_majlis', 'is_on_air')) {
            return;
        }

        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->dropColumn('is_on_air');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sesi_majlis') || Schema::hasColumn('sesi_majlis', 'is_on_air')) {
            return;
        }

        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->boolean('is_on_air')->default(false)->after('is_active');
        });
    }
};
