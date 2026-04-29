<?php

declare(strict_types=1);

namespace App\Services\Report;

use App\Models\Pegawai;
use App\Models\SesiMajlis;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class ReportPreviewTableService
{
    public const SECTION_ONTIME = 'ontime';

    public const SECTION_LATE = 'late';

    public const SECTION_NOTATTEND = 'notattend';

    /**
     * @return array{onTime: int, late: int, notAttend: int}
     */
    public function counts(SesiMajlis $sesi): array
    {
        return [
            'onTime' => $this->onTimeQuery($sesi)->count(),
            'late' => $this->lateQuery($sesi)->count(),
            'notAttend' => $this->notAttendQuery($sesi)->count(),
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function queryFor(SesiMajlis $sesi, string $section): Builder
    {
        return match ($section) {
            self::SECTION_ONTIME => $this->onTimeQuery($sesi),
            self::SECTION_LATE => $this->lateQuery($sesi),
            self::SECTION_NOTATTEND => $this->notAttendQuery($sesi),
            default => throw new InvalidArgumentException('Invalid report section.'),
        };
    }

    private function onTimeQuery(SesiMajlis $sesi): Builder
    {
        $sesiId = (int) $sesi->id;

        return Pegawai::query()
            ->where('sesi_majlis_id', $sesiId)
            ->where('is_late', false)
            ->orderBy('no_kerusi');
    }

    private function lateQuery(SesiMajlis $sesi): Builder
    {
        $sesiId = (int) $sesi->id;

        return Pegawai::query()
            ->where('sesi_majlis_id', $sesiId)
            ->where('is_late', true)
            ->orderBy('no_panggilan_lewat')
            ->orderBy('no_kerusi');
    }

    private function notAttendQuery(SesiMajlis $sesi): Builder
    {
        $slot = (int) $sesi->s_kehadiran;

        return Pegawai::query()
            ->where('s_kehadiran', $slot)
            ->where('is_attend', false)
            ->orderBy('no_kerusi');
    }
}
