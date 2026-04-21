<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SesiMajlis;
use App\Services\Kehadiran\KehadiranCallingService;

final class SesiMajlisObserver
{
    public function __construct(
        private readonly KehadiranCallingService $callingService,
    ) {}

    public function updating(SesiMajlis $sesiMajlis): void
    {
        if (! $sesiMajlis->isDirty('is_active')) {
            return;
        }

        $wasActive = (bool) $sesiMajlis->getOriginal('is_active');

        if (! $wasActive || $sesiMajlis->is_active !== false) {
            return;
        }

        if (! $sesiMajlis->is_late) {
            return;
        }

        $this->callingService->batchAssignLateNumbersFromSession($sesiMajlis);
    }
}
