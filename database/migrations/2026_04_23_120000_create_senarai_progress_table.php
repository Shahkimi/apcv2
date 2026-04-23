<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senarai_progress', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key')->unique();
            $table->foreignId('sesi_majlis_id')->nullable()->constrained('sesi_majlis')->nullOnDelete();
            $table->unsignedInteger('current_index')->default(0);
            $table->json('announced_officers')->nullable();
            $table->timestamp('last_updated_at');
            $table->timestamps();
            $table->index('sesi_majlis_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senarai_progress');
    }
};
