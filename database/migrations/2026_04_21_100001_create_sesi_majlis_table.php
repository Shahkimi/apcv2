<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        Schema::dropIfExists('sesi_majlis');
    }
};
