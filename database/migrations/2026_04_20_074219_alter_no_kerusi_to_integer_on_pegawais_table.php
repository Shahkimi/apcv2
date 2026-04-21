<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE `pegawais` SET `no_kerusi` = NULL WHERE `no_kerusi` IS NOT NULL AND `no_kerusi` NOT REGEXP '^[0-9]+$'");
        DB::statement('ALTER TABLE `pegawais` MODIFY `no_kerusi` INT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE `pegawais` MODIFY `no_kerusi` VARCHAR(255) NULL');
    }
};
