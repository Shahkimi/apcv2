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

        Schema::create('sesi_majlis', function (Blueprint $table) {
            $table->id();
            $table->string('sesi');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('countdown_start_late')->nullable();
            $table->timestamps();
        });

        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_kp')->unique();
            $table->foreignId('ptj_id')->constrained('ptjs')->cascadeOnDelete();
            $table->foreignId('jawatan_id')->constrained('jawatans')->cascadeOnDelete();
            $table->foreignId('gred_id')->constrained('greds')->cascadeOnDelete();
            $table->foreignId('sesi_majlis_id')
                ->nullable()
                ->constrained('sesi_majlis')
                ->nullOnDelete();
            $table->boolean('rsvp')->default(false);
            $table->unsignedInteger('no_kerusi')->nullable();
            $table->unsignedInteger('no_sijil')->nullable();
            $table->unsignedInteger('no_meja')->nullable();
            $table->unsignedInteger('no_panggilan_lewat')->nullable();
            $table->boolean('is_attend')->default(false);
            $table->boolean('is_late')->default(false);
            $table->unsignedTinyInteger('s_kehadiran')
                ->default(0)
                ->comment('0 = pagi, 1 = petang');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawais');
        Schema::dropIfExists('sesi_majlis');
        Schema::dropIfExists('greds');
        Schema::dropIfExists('jawatans');
        Schema::dropIfExists('ptjs');
    }
};
