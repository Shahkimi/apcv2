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
            $table->renameColumn('confirmation_invitation', 'rsvp');
            $table->renameColumn('no_meja', 'no_sijil');
        });

        Schema::table('pegawais', function (Blueprint $table) {
            $table->boolean('is_attend')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropColumn('is_attend');
        });

        Schema::table('pegawais', function (Blueprint $table) {
            $table->renameColumn('rsvp', 'confirmation_invitation');
            $table->renameColumn('no_sijil', 'no_meja');
        });
    }
};
