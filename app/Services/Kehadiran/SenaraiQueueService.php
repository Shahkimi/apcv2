<?php

declare(strict_types=1);

namespace App\Services\Kehadiran;

use App\Models\Pegawai;
use App\Models\SesiMajlis;
use Illuminate\Support\Collection;

/**
 * Layar Utama / MC call-out queue.
 *
 * While the session's Lewat is on and the presentation has started, the queue is
 * [already announced, in announce order] + [not yet announced whose slot is still ahead, in calling order]
 * + [not yet announced whose slot was already passed, in arrival order]. A late arrival therefore takes
 * their normal slot when it is still coming up, otherwise goes to the end (after OKU), and nobody is skipped.
 */
final class SenaraiQueueService
{
    public function __construct(
        private readonly KehadiranCallingService $callingService,
        private readonly CallingPriorityService $callingPriority,
        private readonly SenaraiProgressService $progress,
    ) {}

    /**
     * @return Collection<int, Pegawai>
     */
    public function officers(?int $sesiId): Collection
    {
        $ordered = $this->callingService->attendedPegawaiForDisplay($sesiId);

        if (! $this->lateRuleActive($sesiId)) {
            return $ordered;
        }

        return $this->arrange($ordered, $this->progress->announcedPegawaiIds($sesiId));
    }

    /**
     * Cheap fingerprint of everything that can reorder the queue, so screens only refetch on change.
     */
    public function version(?int $sesiId): string
    {
        $attended = Pegawai::query()
            ->where('is_attend', true)
            ->when($sesiId !== null, fn ($query) => $query->where('sesi_majlis_id', $sesiId))
            ->selectRaw('COUNT(*) AS total, MAX(updated_at) AS touched')
            ->first();

        $announced = $this->progress->announcedOfficersQuery($sesiId)
            ->selectRaw('COUNT(*) AS total, MAX(id) AS last_id')
            ->first();

        $sesi = $this->ruleSesi($sesiId);

        return sha1((string) json_encode([
            (int) $attended?->total,
            (string) $attended?->touched,
            (int) $announced?->total,
            (int) $announced?->last_id,
            $this->callingPriority->order(),
            $sesi?->id,
            (bool) $sesi?->is_late,
            $sesi?->updated_at?->toIso8601String(),
        ]));
    }

    /**
     * @param  Collection<int, Pegawai>  $ordered  Attended officers in calling order
     * @param  list<int>  $announcedIds  Announced officers, first announcement first
     * @return Collection<int, Pegawai>
     */
    private function arrange(Collection $ordered, array $announcedIds): Collection
    {
        if ($announcedIds === []) {
            return $ordered;
        }

        $ordered = $ordered->values();
        $slotOf = $ordered->mapWithKeys(fn (Pegawai $pegawai, int $slot) => [$pegawai->id => $slot]);
        $announced = collect($announcedIds)->filter(fn (int $id) => $slotOf->has($id))->values();

        // Furthest calling slot the presentation has reached.
        $frontier = (int) $announced->map(fn (int $id) => $slotOf[$id])->max();
        $announcedSet = $announced->flip();

        $waiting = $ordered->reject(fn (Pegawai $pegawai) => $announcedSet->has($pegawai->id));
        [$ahead, $passed] = $waiting->partition(fn (Pegawai $pegawai) => $slotOf[$pegawai->id] > $frontier);

        return $announced->map(fn (int $id) => $ordered[$slotOf[$id]])
            ->concat($ahead)
            ->concat($passed->sortBy([
                fn (Pegawai $a, Pegawai $b) => ($a->hadir_at?->getTimestamp() ?? PHP_INT_MAX) <=> ($b->hadir_at?->getTimestamp() ?? PHP_INT_MAX),
                fn (Pegawai $a, Pegawai $b) => $a->id <=> $b->id,
            ]))
            ->values();
    }

    private function lateRuleActive(?int $sesiId): bool
    {
        return (bool) $this->ruleSesi($sesiId)?->is_late;
    }

    /**
     * Session whose Lewat switch governs the queue: the presented session, or the on-air one for "all".
     */
    private function ruleSesi(?int $sesiId): ?SesiMajlis
    {
        return $sesiId !== null
            ? SesiMajlis::query()->find($sesiId)
            : $this->callingService->activeOnAirSesi();
    }
}
