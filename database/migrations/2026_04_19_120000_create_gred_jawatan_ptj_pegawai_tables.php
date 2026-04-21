<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ptjs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ptj');
            $table->timestamps();
        });

        Schema::create('jawatans', function (Blueprint $table) {
            $table->id();
            $table->string('desc_jawatan');
            $table->timestamps();
        });

        Schema::create('greds', function (Blueprint $table) {
            $table->id();
            $table->string('desc_gred');
            $table->timestamps();
        });

        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_kp')->unique();
            $table->foreignId('ptj_id')->constrained('ptjs')->cascadeOnDelete();
            $table->foreignId('jawatan_id')->constrained('jawatans')->cascadeOnDelete();
            $table->foreignId('gred_id')->constrained('greds')->cascadeOnDelete();
            $table->boolean('confirmation_invitation')->default(false);
            $table->string('no_kerusi')->nullable();
            $table->string('no_meja')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawais');
        Schema::dropIfExists('greds');
        Schema::dropIfExists('jawatans');
        Schema::dropIfExists('ptjs');
    }
};
