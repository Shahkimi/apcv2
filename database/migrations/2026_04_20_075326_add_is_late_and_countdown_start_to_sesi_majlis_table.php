<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('is_active');
            $table->unsignedInteger('countdown_start')->nullable()->after('is_late');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sesi_majlis', function (Blueprint $table) {
            $table->dropColumn(['is_late', 'countdown_start']);
        });
    }
};
