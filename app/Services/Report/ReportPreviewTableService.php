<?php

declare(strict_types=1);

namespace App\Services\Report;

use App\Models\Pegawai;
use App\Models\SesiMajlis;
use App\Services\Kehadiran\CallingPriorityService;
use App\Services\Kehadiran\KehadiranCallingService;
use App\Services\Kehadiran\SenaraiProgressService;
use App\Services\Kehadiran\SenaraiQueueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Report sections follow the call-out priority (Kawalan → Susunan Panggilan): one section per
 * category of attended officers, in admin order, then officers not attending the slot.
 */
final class ReportPreviewTableService
{
    public const SECTION_NOTATTEND = 'notattend';

    public function __construct(
        private readonly CallingPriorityService $callingPriority,
        private readonly KehadiranCallingService $callingService,
        private readonly SenaraiProgressService $senaraiProgress,
        private readonly SenaraiQueueService $senaraiQueue,
    ) {}

    /**
     * Sections in display order.
     *
     * @return array<string, array{label: string, short: string, description: string, icon: string, attended: bool}>
     */
    public function sections(): array
    {
        $sections = [];

        foreach ($this->callingPriority->orderedOptions() as $group => $option) {
            $sections[$group] = [...$option, 'attended' => true];
        }

        $sections[self::SECTION_NOTATTEND] = [
            'label' => __('Pegawai Tidak Hadir (Slot Sesi)'),
            'short' => __('Tidak Hadir'),
            'description' => __('Pegawai dalam slot sesi yang belum hadir.'),
            'icon' => 'ri-user-unfollow-line',
            'attended' => false,
        ];

        return $sections;
    }

    /**
     * @return array<string, int>
     */
    public function counts(SesiMajlis $sesi): array
    {
        return collect(array_keys($this->sections()))
            ->mapWithKeys(fn (string $section) => [$section => $this->queryFor($sesi, $section)->count()])
            ->all();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function queryFor(SesiMajlis $sesi, string $section): Builder
    {
        if ($section === self::SECTION_NOTATTEND) {
            return $this->notAttendQuery($sesi);
        }

        if (! in_array($section, CallingPriorityService::DEFAULT_ORDER, true)) {
            throw new InvalidArgumentException('Invalid report section.');
        }

        $query = Pegawai::query()
            ->where('sesi_majlis_id', (int) $sesi->id)
            ->where('is_attend', true);

        $this->callingPriority->applyGroupFilter($query, $section);
        $this->callingService->applyCallingQueueOrder($query);

        return $query;
    }

    /**
     * MC announcement list: every attended officer of the sesi in call-out order (same order as Layar Utama).
     *
     * @return Collection<int, Pegawai>
     */
    public function mcList(SesiMajlis $sesi): Collection
    {
        return $this->senaraiQueue->officers((int) $sesi->id);
    }

    /**
     * Full MC list numbered 1..N; Bil is the call-out position (same as the Layar Utama slide number).
     *
     * @return Collection<int, array{bil: int, group: string, pegawai: Pegawai}>
     */
    public function mcRows(SesiMajlis $sesi): Collection
    {
        return $this->mcList($sesi)
            ->values()
            ->map(fn (Pegawai $pegawai, int $index) => [
                'bil' => $index + 1,
                'group' => $this->callingPriority->groupOf($pegawai),
                'pegawai' => $pegawai,
            ]);
    }

    /**
     * Officers already shown on Layar Utama for this sesi, keyed by pegawai id.
     *
     * @return array<int, true>
     */
    public function announcedIds(SesiMajlis $sesi): array
    {
        return $this->senaraiProgress->announcedOfficersQuery((int) $sesi->id)
            ->pluck('pegawai_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }

    /**
     * Part of the MC list for a reprint: Bil range (inclusive, original numbering kept),
     * optionally without officers already announced on Layar Utama.
     *
     * @return array{
     *   all: Collection<int, array{bil: int, group: string, pegawai: Pegawai}>,
     *   rows: Collection<int, array{bil: int, group: string, pegawai: Pegawai}>,
     *   total: int, from: int, to: int, count: int, skipped: int, skipAnnounced: bool, isRange: bool,
     *   announcedCount: int, lastAnnounced: array{bil: int, nama: string}|null, firstUnannounced: int|null
     * }
     */
    public function mcSelection(SesiMajlis $sesi, ?int $from = null, ?int $to = null, bool $skipAnnounced = false): array
    {
        $all = $this->mcRows($sesi);
        $announced = $this->announcedIds($sesi);
        $total = $all->count();
        $from = max(1, $from ?? 1);
        $to = $to === null ? $total : min($to, $total);

        $inRange = $all->filter(fn (array $row) => $row['bil'] >= $from && $row['bil'] <= $to);
        $rows = $skipAnnounced
            ? $inRange->reject(fn (array $row) => isset($announced[$row['pegawai']->id]))
            : $inRange;

        $isAnnounced = fn (array $row): bool => isset($announced[$row['pegawai']->id]);
        $lastAnnounced = $all->filter($isAnnounced)->last();

        return [
            'all' => $all,
            'rows' => $rows->values(),
            'total' => $total,
            'from' => $from,
            'to' => $to,
            'count' => $rows->count(),
            'skipped' => $inRange->count() - $rows->count(),
            'skipAnnounced' => $skipAnnounced,
            'isRange' => $from > 1 || $to < $total,
            'announcedCount' => $all->filter($isAnnounced)->count(),
            'lastAnnounced' => $lastAnnounced === null ? null : [
                'bil' => $lastAnnounced['bil'],
                'nama' => (string) $lastAnnounced['pegawai']->nama,
            ],
            'firstUnannounced' => $all->reject($isAnnounced)->first()['bil'] ?? null,
        ];
    }

    /**
     * Rows split into consecutive category blocks. `continued` marks a block that does not start
     * at its category's first Bil in the full list (printed heading shows "sambungan").
     *
     * @param  Collection<int, array{bil: int, group: string, pegawai: Pegawai}>  $rows
     * @param  Collection<int, array{bil: int, group: string, pegawai: Pegawai}>|null  $all
     * @return list<array{group: string, label: string, continued: bool, rows: Collection<int, array{bil: int, group: string, pegawai: Pegawai}>}>
     */
    public function groupRows(Collection $rows, ?Collection $all = null): array
    {
        $options = CallingPriorityService::options();
        $firstBil = ($all ?? $rows)->groupBy('group')->map(fn (Collection $g) => $g->min('bil'));
        $groups = [];

        foreach ($rows as $row) {
            $last = array_key_last($groups);

            if ($last === null || $groups[$last]['group'] !== $row['group']) {
                $groups[] = [
                    'group' => $row['group'],
                    'label' => $options[$row['group']]['label'],
                    'continued' => $row['bil'] > ($firstBil[$row['group']] ?? $row['bil']),
                    'rows' => collect(),
                ];
                $last = array_key_last($groups);
            }

            $groups[$last]['rows']->push($row);
        }

        return $groups;
    }

    /**
     * Whole MC list grouped for the report page.
     *
     * @return list<array{group: string, label: string, continued: bool, rows: Collection<int, array{bil: int, group: string, pegawai: Pegawai}>}>
     */
    public function mcGroups(SesiMajlis $sesi): array
    {
        return $this->groupRows($this->mcRows($sesi));
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
