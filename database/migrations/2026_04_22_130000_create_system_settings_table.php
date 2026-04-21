<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            'key' => 'display.show_table_number_in_dialog',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Show table number (No. Meja) in attendance confirmation dialog',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
