<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jasamu Dikenang: walk-ins (rsvp=0) already marked hadir sit at the seat matching their
 * late calling number. Fill no_kerusi / no_meja for rows verified before that rule existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_settings') || ! Schema::hasTable('mejas') || ! Schema::hasTable('sesi_majlis')) {
            return;
        }

        $mode = DB::table('system_settings')->where('key', 'event.mode')->value('value');
        if ($mode !== 'jasamu') {
            return;
        }

        $capacity = (int) DB::table('mejas')->orderBy('id')->value('sizing');
        $offsets = DB::table('sesi_majlis')->pluck('seat_offset', 'id');

        DB::table('pegawais')
            ->where('rsvp', false)
            ->where('is_attend', true)
            ->where('no_panggilan_lewat', '>', 0)
            ->whereNull('no_kerusi')
            ->select(['id', 'sesi_majlis_id', 'no_panggilan_lewat'])
            ->chunkById(200, function ($rows) use ($capacity, $offsets): void {
                foreach ($rows as $row) {
                    $seat = (int) $row->no_panggilan_lewat;

                    DB::table('pegawais')->where('id', $row->id)->update([
                        'no_kerusi' => $seat,
                        'no_meja' => $this->tableNumber($seat, (int) ($offsets[$row->sesi_majlis_id] ?? 0), $capacity),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Data-only backfill: nothing to undo.
    }

    /**
     * Mirrors KehadiranCallingService::calculateTableNumber().
     */
    private function tableNumber(int $seat, int $offset, int $capacity): ?int
    {
        if ($capacity < 1) {
            return null;
        }

        $relativeSeat = $seat - $offset;

        return $relativeSeat < 1 ? null : (int) floor(($relativeSeat - 1) / $capacity) + 1;
    }
};
