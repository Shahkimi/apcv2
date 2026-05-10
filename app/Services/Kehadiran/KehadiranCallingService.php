<?php

declare(strict_types=1);

namespace App\Services\Kehadiran;

use App\Models\Meja;
use App\Models\Pegawai;
use App\Models\SesiMajlis;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class KehadiranCallingService
{
    public function lateSessionOnAirExists(): bool
    {
        $active = $this->activeOnAirSesi();

        return $active !== null && $active->is_late;
    }

    public function activeOnAirSesi(): ?SesiMajlis
    {
        return SesiMajlis::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * Table number from seat, relative to session seat_offset (0 = seats start at 1).
     */
    public function calculateTableNumber(int|string|null $seatNumber, ?SesiMajlis $session): ?int
    {
        if ($seatNumber === null || $seatNumber === '' || ! is_numeric((string) $seatNumber)) {
            return null;
        }

        $seat = (int) $seatNumber;

        if ($seat < 1) {
            return null;
        }

        $capacity = Meja::query()->orderBy('id')->value('sizing');

        if (! is_numeric($capacity) || (int) $capacity < 1) {
            return null;
        }

        $offset = $session?->seat_offset ?? 0;
        $relativeSeat = $seat - $offset;

        if ($relativeSeat < 1) {
            return null;
        }

        return (int) floor(($relativeSeat - 1) / (int) $capacity) + 1;
    }

    /**
     * Kehadiran table / general list: on-time first (no late number), by seat; then late by calling number.
     */
    public function applyDefaultCallingOrder(Builder $query): void
    {
        $query->orderByRaw('CASE WHEN no_panggilan_lewat IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw(
                'CASE WHEN no_panggilan_lewat IS NULL THEN COALESCE(no_kerusi, 2147483647) ELSE COALESCE(no_panggilan_lewat, 2147483647) END'
            )
            ->orderBy('no_kerusi');
    }

    /**
     * Kehadiran checklist DataTable: belum hadir first (is_attend asc), then by id asc; hadir rows at bottom, by id asc.
     */
    public function applyKehadiranDataTableOrder(Builder $query): void
    {
        $query->orderBy('is_attend')->orderBy('id');
    }

    /**
     * Paparan: hadir only — on-time (is_late false) by no_kerusi ASC, then late by no_panggilan_lewat ASC.
     */
    public function applyPaparanDisplayOrder(Builder $query): void
    {
        $query->orderByRaw('CASE WHEN COALESCE(is_late, 0) = 0 THEN 0 ELSE 1 END')
            ->orderByRaw(
                'CASE WHEN COALESCE(is_late, 0) = 0 THEN COALESCE(no_kerusi, 2147483647) ELSE COALESCE(no_panggilan_lewat, 2147483647) END'
            );
    }

    /**
     * @return Collection<int, Pegawai>
     */
    public function attendedPegawaiForDisplay(?int $sesiId = null): Collection
    {
        $query = Pegawai::query()
            ->with(['ptj', 'sesiMajlis', 'jawatan'])
            ->where('is_attend', true);

        if ($sesiId !== null) {
            $query->where('sesi_majlis_id', $sesiId);
        }

        $this->applyPaparanDisplayOrder($query);

        return $query->get();
    }

    /**
     * When a session moves to inactive late (is_active false, is_late true),
     * assign late calling numbers to everyone already marked hadir, by no_kerusi, from countdown_start_late.
     */
    public function batchAssignLateNumbersFromSession(SesiMajlis $sesiMajlis): void
    {
        $startNumber = $this->lateCallingStartNumber($sesiMajlis);
        $sesiId = $sesiMajlis->id;

        DB::transaction(function () use ($startNumber, $sesiId): void {
            $attendedOfficers = Pegawai::query()
                ->where('is_attend', true)
                ->where('sesi_majlis_id', $sesiId)
                ->where(function (Builder $q): void {
                    $q->whereNull('no_panggilan_lewat')
                        ->orWhere('no_panggilan_lewat', '<=', 0);
                })
                ->orderByRaw('no_kerusi + 0')
                ->lockForUpdate()
                ->get();

            $currentNumber = $startNumber;
            foreach ($attendedOfficers as $pegawai) {
                $pegawai->no_panggilan_lewat = $currentNumber;
                $pegawai->is_late = true;
                $pegawai->save();
                $currentNumber++;
            }
        });
    }

    public function assignLateCallingNumberIfApplicable(Pegawai $pegawai, ?SesiMajlis $activeSesi): void
    {
        if (! $pegawai->is_attend || ((int) ($pegawai->no_panggilan_lewat ?? 0)) > 0) {
            return;
        }

        if ($activeSesi === null) {
            return;
        }

        $requiresLateNumber = ! $pegawai->rsvp || $activeSesi->is_late;
        if (! $requiresLateNumber) {
            return;
        }

        /*
         * Serialize allocation per session: lock one sesi_majlis row instead of lockForUpdate on many
         * pegawai rows (avoids lock pile-up when many users verify at once). Caller must run inside a transaction.
         */
        $lockedSesi = SesiMajlis::query()
            ->whereKey($activeSesi->id)
            ->lockForUpdate()
            ->first();

        if ($lockedSesi === null) {
            return;
        }

        $max = (int) Pegawai::query()
            ->where('sesi_majlis_id', $lockedSesi->id)
            ->where('no_panggilan_lewat', '>', 0)
            ->max('no_panggilan_lewat');
        $startNumber = $this->lateCallingStartNumber($lockedSesi);
        $nextNumber = max($startNumber, $max + 1);
        $pegawai->no_panggilan_lewat = $nextNumber;
        $pegawai->is_late = true;
    }

    public function previewNextLateCallingNumber(?SesiMajlis $activeSesi): ?int
    {
        if ($activeSesi === null) {
            return null;
        }

        $max = (int) Pegawai::query()
            ->where('sesi_majlis_id', $activeSesi->id)
            ->where('no_panggilan_lewat', '>', 0)
            ->max('no_panggilan_lewat');
        $startNumber = $this->lateCallingStartNumber($activeSesi);

        return max($startNumber, $max + 1);
    }

    /**
     * First late-calling number for the session: countdown_start_late when set, otherwise seat_offset if positive, else 1.
     */
    private function lateCallingStartNumber(SesiMajlis $sesi): int
    {
        if ($sesi->countdown_start_late !== null) {
            return max(1, (int) $sesi->countdown_start_late);
        }

        $offset = (int) ($sesi->seat_offset ?? 0);

        return $offset > 0 ? $offset : 1;
    }

    public function clearLateCallingOnCancel(Pegawai $pegawai): void
    {
        $pegawai->no_panggilan_lewat = null;
        $pegawai->is_late = false;
        $pegawai->sesi_majlis_id = null;
    }
}
