<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('senarai_announced_officers') || ! Schema::hasTable('senarai_progress')) {
            return;
        }

        DB::table('senarai_progress')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $raw = $row->announced_officers ?? null;
                if ($raw === null || $raw === '') {
                    continue;
                }

                $entries = is_string($raw) ? json_decode($raw, true) : $raw;
                if (! is_array($entries)) {
                    continue;
                }

                foreach ($entries as $entry) {
                    if (! is_array($entry) || ! isset($entry['pegawai_id'])) {
                        continue;
                    }

                    $pegawaiId = (int) $entry['pegawai_id'];
                    if ($pegawaiId <= 0) {
                        continue;
                    }

                    $announcedAt = $entry['announced_at'] ?? null;
                    $timestamp = is_string($announcedAt) && $announcedAt !== ''
                        ? Carbon::parse($announcedAt)
                        : Carbon::now();

                    DB::table('senarai_announced_officers')->insertOrIgnore([
                        'scope_key' => $row->scope_key,
                        'sesi_majlis_id' => $row->sesi_majlis_id,
                        'pegawai_id' => $pegawaiId,
                        'announced_at' => $timestamp,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty: do not delete relational rows on rollback of this migration,
        // as the previous migration dropping the table would remove them anyway.
    }
};
