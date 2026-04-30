<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sesi_majlis')) {
            return;
        }

        Schema::create('sesi_majlis', function (Blueprint $table) {
            $table->id();
            $table->string('sesi');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('countdown_start_late')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // When created in 2026_04_19_120000, tear-down runs there (after pegawais).
    }
};
