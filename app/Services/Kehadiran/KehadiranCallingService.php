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
            ->with(['ptj', 'sesiMajlis'])
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
        $startNumber = max(1, (int) ($sesiMajlis->countdown_start_late ?? 1));
        $sesiId = $sesiMajlis->id;

        DB::transaction(function () use ($startNumber, $sesiId): void {
            $attendedOfficers = Pegawai::query()
                ->where('is_attend', true)
                ->where('sesi_majlis_id', $sesiId)
                ->whereNull('no_panggilan_lewat')
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
        if (! $pegawai->is_attend || $pegawai->no_panggilan_lewat !== null) {
            return;
        }

        if ($activeSesi === null || ! $activeSesi->is_late) {
            return;
        }

        DB::transaction(function () use ($pegawai, $activeSesi): void {
            $max = (int) Pegawai::query()
                ->where('sesi_majlis_id', $activeSesi->id)
                ->lockForUpdate()
                ->max('no_panggilan_lewat');
            $startNumber = max(1, (int) ($activeSesi->countdown_start_late ?? 1));
            $nextNumber = $max > 0 ? ($max + 1) : $startNumber;
            $pegawai->no_panggilan_lewat = $nextNumber;
            $pegawai->is_late = true;
        });
    }

    public function clearLateCallingOnCancel(Pegawai $pegawai): void
    {
        $pegawai->no_panggilan_lewat = null;
        $pegawai->is_late = false;
        $pegawai->sesi_majlis_id = null;
    }
}
