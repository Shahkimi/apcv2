<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senarai_announced_officers', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key')->index();
            $table->unsignedBigInteger('sesi_majlis_id')->nullable()->index();
            $table->unsignedBigInteger('pegawai_id');
            $table->timestamp('announced_at')->useCurrent();

            $table->unique(['scope_key', 'pegawai_id']);
            $table->index(['scope_key', 'announced_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senarai_announced_officers');
    }
};
