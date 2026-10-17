<?php

declare(strict_types=1);

namespace App\Services\Kehadiran;

use App\Models\Pegawai;

/**
 * Seat numbers for officers moved to RSVP by an admin after import.
 */
final class RsvpSeatService
{
    /**
     * Highest seat held by an RSVP officer (0 when none).
     */
    public function lastRsvpSeat(): int
    {
        return (int) Pegawai::query()
            ->where('rsvp', true)
            ->where('no_kerusi', '>', 0)
            ->max('no_kerusi');
    }

    /**
     * Next seat after the last RSVP seat, skipping seats already held by anyone
     * (e.g. Jasamu walk-ins seated by their late calling number).
     */
    public function suggestNextSeat(?int $ignoreId = null): int
    {
        $candidate = $this->lastRsvpSeat() + 1;

        $taken = Pegawai::query()
            ->where('no_kerusi', '>=', $candidate)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->pluck('no_kerusi')
            ->map(fn ($seat): int => (int) $seat)
            ->flip();

        while ($taken->has($candidate)) {
            $candidate++;
        }

        return $candidate;
    }

    /**
     * Another officer already holding this seat, if any.
     */
    public function seatOwner(int $seat, int $ignoreId): ?Pegawai
    {
        return Pegawai::query()
            ->select(['id', 'nama'])
            ->where('no_kerusi', $seat)
            ->whereKeyNot($ignoreId)
            ->first();
    }
}
