<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pegawais', 'no_meja')) {
            Schema::table('pegawais', function (Blueprint $table) {
                $table->unsignedInteger('no_meja')->nullable()->after('no_sijil');
            });
        }

        if (! Schema::hasColumn('pegawais', 'no_panggilan_lewat')) {
            Schema::table('pegawais', function (Blueprint $table) {
                $table->unsignedInteger('no_panggilan_lewat')->nullable()->after('no_meja');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pegawais', 'no_panggilan_lewat')) {
            Schema::table('pegawais', function (Blueprint $table) {
                $table->dropColumn('no_panggilan_lewat');
            });
        }
    }
};
