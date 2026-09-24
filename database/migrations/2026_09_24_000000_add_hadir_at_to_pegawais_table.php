<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->timestamp('hadir_at')->nullable()->after('is_late');
            $table->index(['is_attend', 'hadir_at'], 'pegawais_is_attend_hadir_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropIndex('pegawais_is_attend_hadir_at_index');
            $table->dropColumn('hadir_at');
        });
    }
};
