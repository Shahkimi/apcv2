<?php

declare(strict_types=1);

namespace App\Services\Kehadiran;

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

        $announcedCount = count($progress?->announced_officers ?? []);
        $safeTotal = max(1, $totalOfficers);

        return [
            'current_index' => $this->clampIndex((int) ($progress?->current_index ?? 0), $totalOfficers),
            'announced_count' => min($announcedCount, $totalOfficers),
            'total_officers' => $totalOfficers,
            'progress_percent' => $totalOfficers > 0 ? round(($announcedCount / $safeTotal) * 100, 1) : 0.0,
            'last_updated_at' => $progress?->last_updated_at?->toIso8601String(),
        ];
    }

    public function updateProgress(?int $sesiId, int $index, int $pegawaiId, int $totalOfficers): array
    {
        $progress = SenaraiProgress::query()->firstOrCreate(
            ['scope_key' => $this->scopeKey($sesiId)],
            [
                'sesi_majlis_id' => $sesiId,
                'current_index' => 0,
                'announced_officers' => [],
                'last_updated_at' => CarbonImmutable::now(),
            ]
        );

        $progress->sesi_majlis_id = $sesiId;
        $progress->current_index = $this->clampIndex($index, $totalOfficers);

        $announced = collect($progress->announced_officers ?? []);
        if ($announced->firstWhere('pegawai_id', $pegawaiId) === null) {
            $announced->push([
                'pegawai_id' => $pegawaiId,
                'announced_at' => CarbonImmutable::now()->toIso8601String(),
            ]);
        }

        $progress->announced_officers = $announced->values()->all();
        $progress->last_updated_at = CarbonImmutable::now();
        $progress->save();

        return $this->getProgress($sesiId, $totalOfficers);
    }

    public function getAnnouncedOfficers(?int $sesiId): Collection
    {
        $progress = SenaraiProgress::query()
            ->where('scope_key', $this->scopeKey($sesiId))
            ->first();

        $announced = collect($progress?->announced_officers ?? []);
        if ($announced->isEmpty()) {
            return collect();
        }

        $announcedById = $announced->keyBy(static fn (array $row): int => (int) ($row['pegawai_id'] ?? 0));
        $idsInOrder = $announced
            ->pluck('pegawai_id')
            ->filter(static fn ($id): bool => is_numeric($id))
            ->map(static fn ($id): int => (int) $id)
            ->values();

        $pegawais = Pegawai::query()
            ->whereIn('id', $idsInOrder)
            ->with(['jawatan', 'ptj'])
            ->get()
            ->keyBy('id');

        return $idsInOrder
            ->map(function (int $pegawaiId) use ($pegawais, $announcedById): ?array {
                $pegawai = $pegawais->get($pegawaiId);
                if ($pegawai === null) {
                    return null;
                }

                $meta = (array) ($announcedById->get($pegawaiId) ?? []);

                return [
                    'id' => $pegawai->id,
                    'nama' => $pegawai->nama,
                    'jawatan' => $pegawai->jawatan?->desc_jawatan ?? '—',
                    'ptj' => $pegawai->ptj?->nama_ptj ?? '—',
                    'announced_at' => $meta['announced_at'] ?? null,
                ];
            })
            ->filter()
            ->values();
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
