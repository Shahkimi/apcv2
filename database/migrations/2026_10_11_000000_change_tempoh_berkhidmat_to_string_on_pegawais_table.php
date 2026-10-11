<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * tempoh_berkhidmat becomes free text (e.g. "25 tahun 3 bulan"); existing year counts keep their unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->string('tempoh_berkhidmat', 100)->nullable()->change();
        });

        DB::table('pegawais')
            ->whereNotNull('tempoh_berkhidmat')
            ->update(['tempoh_berkhidmat' => DB::raw($this->concat('tempoh_berkhidmat', "' tahun'"))]);
    }

    public function down(): void
    {
        DB::table('pegawais')
            ->where('tempoh_berkhidmat', 'like', '% tahun')
            ->update(['tempoh_berkhidmat' => DB::raw("REPLACE(tempoh_berkhidmat, ' tahun', '')")]);

        DB::table('pegawais')
            ->whereNotNull('tempoh_berkhidmat')
            ->get(['id', 'tempoh_berkhidmat'])
            ->reject(fn ($row) => ctype_digit((string) $row->tempoh_berkhidmat) && (int) $row->tempoh_berkhidmat <= 65535)
            ->each(fn ($row) => DB::table('pegawais')->where('id', $row->id)->update(['tempoh_berkhidmat' => null]));

        Schema::table('pegawais', function (Blueprint $table) {
            $table->unsignedSmallInteger('tempoh_berkhidmat')->nullable()->change();
        });
    }

    private function concat(string $left, string $right): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "{$left} || {$right}"
            : "CONCAT({$left}, {$right})";
    }
};
