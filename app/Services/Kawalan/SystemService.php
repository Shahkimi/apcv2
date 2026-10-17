<?php

declare(strict_types=1);

namespace App\Services\Kawalan;

use App\Models\AnnouncedOfficer;
use App\Models\Pegawai;
use App\Support\IdCounter;
use Illuminate\Support\Facades\DB;

final class SystemService
{
    /** Wipeable data: request key => table. Order matters: pegawais references jawatans and greds. */
    public const WIPE_TABLES = [
        'pegawai' => 'pegawais',
        'jawatan' => 'jawatans',
        'gred' => 'greds',
    ];

    /**
     * Reset attendance-related fields for all pegawai records and clear senarai announcement rows.
     *
     * @return int Number of pegawai rows updated
     */
    public function resetAllPegawai(): int
    {
        return (int) DB::transaction(function (): int {
            AnnouncedOfficer::query()->delete();
            // A stale slide index would make the late-arrival queue treat early slots as already passed.
            DB::table('senarai_progress')->update(['current_index' => 0, 'announced_officers' => null]);

            return Pegawai::query()->update([
                // Walk-ins only hold a seat from check-in (Jasamu) or stale test data; RSVP seats come from import.
                'no_kerusi' => DB::raw('CASE WHEN rsvp = 1 THEN no_kerusi ELSE 0 END'),
                'sesi_majlis_id' => null,
                'no_meja' => 0,
                'no_panggilan_lewat' => 0,
                'is_attend' => false,
                'is_late' => false,
                'hadir_at' => null,
            ]);
        });
    }

    /**
     * @return array<string, int> Row count per wipe key
     */
    public function counts(): array
    {
        return array_map(
            static fn (string $table): int => DB::table($table)->count(),
            self::WIPE_TABLES
        );
    }

    /**
     * Page overview: wipeable table counts plus the attendance state a reset clears.
     *
     * @return array{pegawai: int, jawatan: int, gred: int, hadir: int, lewat: int, kerusi: int, diumumkan: int}
     */
    public function overview(): array
    {
        return $this->counts() + [
            'hadir' => Pegawai::query()->where('is_attend', true)->count(),
            'lewat' => Pegawai::query()->where('is_late', true)->count(),
            'kerusi' => Pegawai::query()->where('rsvp', false)->where('no_kerusi', '>', 0)->count(),
            'diumumkan' => AnnouncedOfficer::query()->count(),
        ];
    }

    /**
     * Empties the selected tables and restarts their ids at 1 (TRUNCATE semantics, but atomic and FK-safe).
     * Wiping jawatan or gred always wipes pegawai too: pegawais.jawatan_id / gred_id cascade on delete.
     *
     * @param  list<string>  $keys  Keys of WIPE_TABLES
     * @return array<string, int> Deleted rows per wiped key
     */
    public function wipe(array $keys): array
    {
        $keys = array_values(array_intersect(array_keys(self::WIPE_TABLES), $keys));
        if (array_intersect(['jawatan', 'gred'], $keys) !== [] && ! in_array('pegawai', $keys, true)) {
            array_unshift($keys, 'pegawai');
        }

        $before = $this->counts();

        DB::transaction(function () use ($keys): void {
            foreach ($keys as $key) {
                DB::table(self::WIPE_TABLES[$key])->delete();

                if ($key === 'pegawai') {
                    // senarai_announced_officers.pegawai_id has no FK; clear it with the pegawai it points to.
                    AnnouncedOfficer::query()->delete();
                    DB::table('senarai_progress')->update(['current_index' => 0, 'announced_officers' => null]);
                }
            }
        });

        foreach ($keys as $key) {
            IdCounter::realign(self::WIPE_TABLES[$key], 0);
        }

        return array_intersect_key($before, array_flip($keys));
    }
}
