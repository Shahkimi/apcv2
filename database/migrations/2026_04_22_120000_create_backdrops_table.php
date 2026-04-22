<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backdrops', function (Blueprint $table) {
            $table->id();
            $table->string('backdrop_name')->unique();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('display_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backdrops');
    }
};
