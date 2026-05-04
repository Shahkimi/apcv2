<?php

declare(strict_types=1);

namespace App\Services\Kehadiran;

use App\Models\AnnouncedOfficer;
use App\Models\Pegawai;
use App\Models\SenaraiProgress;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SenaraiProgressService
{
    public function getProgress(?int $sesiId, int $totalOfficers): array
    {
        $progress = SenaraiProgress::query()
            ->where('scope_key', $this->scopeKey($sesiId))
            ->first();

        $announcedCount = AnnouncedOfficer::query()
            ->where('scope_key', $this->scopeKey($sesiId))
            ->count();

        $safeTotal = max(1, $totalOfficers);

        return [
            'current_index' => $this->clampIndex((int) ($progress?->current_index ?? 0), $totalOfficers),
            'announced_count' => min($announcedCount, $totalOfficers),
            'total_officers' => $totalOfficers,
            'progress_percent' => $totalOfficers > 0
                ? round(($announcedCount / $safeTotal) * 100, 1)
                : 0.0,
            'last_updated_at' => $progress?->last_updated_at?->toIso8601String(),
        ];
    }

    public function updateProgress(
        ?int $sesiId,
        int $index,
        int $pegawaiId,
        int $totalOfficers
    ): array {
        $scopeKey = $this->scopeKey($sesiId);

        AnnouncedOfficer::insertOrIgnore([
            'scope_key' => $scopeKey,
            'sesi_majlis_id' => $sesiId,
            'pegawai_id' => $pegawaiId,
            'announced_at' => CarbonImmutable::now(),
        ]);

        SenaraiProgress::query()->updateOrCreate(
            ['scope_key' => $scopeKey],
            [
                'sesi_majlis_id' => $sesiId,
                'current_index' => $this->clampIndex($index, $totalOfficers),
                'last_updated_at' => CarbonImmutable::now(),
            ]
        );

        return $this->getProgress($sesiId, $totalOfficers);
    }

    public function getAnnouncedOfficers(?int $sesiId): Collection
    {
        $scopeKey = $this->scopeKey($sesiId);
        $result = collect();

        AnnouncedOfficer::query()
            ->where('scope_key', $scopeKey)
            ->orderBy('announced_at')
            ->chunkById(200, function ($chunk) use (&$result) {
                $ids = $chunk->pluck('pegawai_id');
                $pegawais = Pegawai::query()
                    ->whereIn('id', $ids)
                    ->with(['jawatan', 'ptj'])
                    ->get()
                    ->keyBy('id');

                foreach ($chunk as $row) {
                    $pegawai = $pegawais->get($row->pegawai_id);
                    if ($pegawai === null) {
                        continue;
                    }
                    $result->push([
                        'id' => $pegawai->id,
                        'nama' => $pegawai->nama,
                        'jawatan' => $pegawai->jawatan?->desc_jawatan ?? '—',
                        'ptj' => $pegawai->ptj?->nama_ptj ?? '—',
                        'announced_at' => $row->announced_at?->toIso8601String(),
                    ]);
                }
            });

        return $result->values();
    }

    private function scopeKey(?int $sesiId): string
    {
        return $sesiId === null ? 'all' : 'sesi:'.$sesiId;
    }

    private function clampIndex(int $index, int $totalOfficers): int
    {
        if ($totalOfficers <= 0) {
            return 0;
        }

        return max(0, min($index, $totalOfficers - 1));
    }
}
