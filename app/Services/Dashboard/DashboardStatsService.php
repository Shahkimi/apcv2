<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Bersara;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use App\Models\User;
use App\Services\EventModeService;
use App\Services\Kehadiran\KehadiranCallingService;
use App\Services\Kehadiran\SenaraiProgressService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class DashboardStatsService
{
    private const BUCKET_MINUTES = 15;

    private const RECENT_LIMIT = 10;

    public function __construct(
        private readonly KehadiranCallingService $calling,
        private readonly SenaraiProgressService $progress,
        private readonly EventModeService $eventMode,
    ) {}

    /**
     * Missing `sesi_id` means the on-air session; present but empty means all sessions.
     */
    public function resolveSesiId(Request $request): ?int
    {
        if (! $request->has('sesi_id')) {
            return $this->calling->activeOnAirSesi()?->id;
        }

        $value = $request->input('sesi_id');

        return $value === null || $value === '' ? null : (int) $value;
    }

    /**
     * @return array{stats: array, charts: array, tables: array, status: array}
     */
    public function forAdmin(?int $sesiId): array
    {
        $attendance = $this->attendance($sesiId);
        $announcement = $this->announcement($sesiId, $attendance['hadir']);
        $isJasamu = $this->eventMode->isJasamu();

        return [
            'stats' => $this->stats($attendance, $announcement, $sesiId),
            'charts' => [
                'timeline' => $this->timeline($this->checkInTimes($sesiId), __('Kehadiran')),
                'sesi' => $this->bySesi(),
                'status' => $this->statusMix($attendance),
                'group' => $isJasamu ? $this->byBersara($sesiId) : $this->topPtj($sesiId),
            ],
            'tables' => ['checkins' => $this->recentCheckIns($sesiId)],
            'status' => $this->status(true),
        ];
    }

    /**
     * @return array{stats: array, charts: array, tables: array, status: array}
     */
    public function forMedia(?int $sesiId): array
    {
        $attendance = $this->attendance($sesiId);
        $announcement = $this->announcement($sesiId, $attendance['hadir']);

        return [
            'stats' => $this->stats($attendance, $announcement, $sesiId),
            'charts' => [
                'announce_timeline' => $this->timeline($this->announcedTimes($sesiId), __('Diumumkan')),
                'announce_split' => [
                    'type' => 'donut',
                    'labels' => [__('Telah diumumkan'), __('Belum diumumkan')],
                    'series' => [$announcement['announced'], $announcement['baki']],
                ],
            ],
            'tables' => ['announcements' => $this->recentAnnouncements($sesiId)],
            'status' => $this->status(false),
        ];
    }

    /**
     * @return array{stats: array, charts: array, tables: array, status: array}
     */
    public function forUser(?int $sesiId): array
    {
        $attendance = $this->attendance($sesiId);
        $announcement = $this->announcement($sesiId, $attendance['hadir']);

        return [
            'stats' => $this->stats($attendance, $announcement, $sesiId),
            'charts' => [
                'timeline' => $this->timeline($this->checkInTimes($sesiId), __('Kehadiran')),
                'sesi' => $this->bySesi(),
            ],
            'tables' => ['checkins' => $this->recentCheckIns($sesiId)],
            'status' => $this->status(false),
        ];
    }

    /**
     * @return array{total: int, rsvp: int, hadir: int, lewat: int, tepat: int, belum_hadir: int, tiada_rsvp: int}
     */
    public function attendance(?int $sesiId): array
    {
        $row = $this->scoped(Pegawai::query(), $sesiId)
            ->selectRaw(
                'COUNT(*) AS total, '
                .'COALESCE(SUM(CASE WHEN rsvp = 1 THEN 1 ELSE 0 END), 0) AS total_rsvp, '
                .'COALESCE(SUM(CASE WHEN is_attend = 1 THEN 1 ELSE 0 END), 0) AS hadir, '
                .'COALESCE(SUM(CASE WHEN is_attend = 1 AND is_late = 1 THEN 1 ELSE 0 END), 0) AS lewat, '
                .'COALESCE(SUM(CASE WHEN rsvp = 1 AND is_attend = 0 THEN 1 ELSE 0 END), 0) AS belum_hadir'
            )
            ->first();

        $total = (int) ($row->total ?? 0);
        $hadir = (int) ($row->hadir ?? 0);
        $lewat = (int) ($row->lewat ?? 0);
        $belum = (int) ($row->belum_hadir ?? 0);

        return [
            'total' => $total,
            'rsvp' => (int) ($row->total_rsvp ?? 0),
            'hadir' => $hadir,
            'lewat' => $lewat,
            'tepat' => $hadir - $lewat,
            'belum_hadir' => $belum,
            'tiada_rsvp' => max(0, $total - $hadir - $belum),
        ];
    }

    /**
     * @return array{announced: int, baki: int, percent: float}
     */
    public function announcement(?int $sesiId, int $hadir): array
    {
        $progress = $this->progress->getProgress($sesiId, $hadir);
        $announced = (int) $progress['announced_count'];

        return [
            'announced' => $announced,
            'baki' => max(0, $hadir - $announced),
            'percent' => (float) $progress['progress_percent'],
        ];
    }

    /**
     * @return array<string, array{value: string, raw: int|float, suffix: string, hint: string, ratio: float|null}>
     */
    private function stats(array $a, array $p, ?int $sesiId): array
    {
        return [
            'total' => $this->stat($a['total'], $sesiId === null ? __('Semua sesi') : __('Dalam sesi ini')),
            'rsvp' => $this->stat($a['rsvp'], $this->percent($a['rsvp'], $a['total']).' '.__('daripada jumlah'), $this->ratio($a['rsvp'], $a['total'])),
            'hadir' => $this->stat($a['hadir'], $this->percent($a['hadir'], $a['rsvp']).' '.__('daripada RSVP'), $this->ratio($a['hadir'], $a['rsvp'])),
            'belum_hadir' => $this->stat($a['belum_hadir'], __('RSVP belum hadir'), $this->ratio($a['belum_hadir'], $a['rsvp'])),
            'lewat' => $this->stat($a['lewat'], $this->percent($a['lewat'], $a['hadir']).' '.__('daripada hadir'), $this->ratio($a['lewat'], $a['hadir'])),
            'announced' => $this->stat($p['announced'], $this->percent($p['announced'], $a['hadir']).' '.__('daripada hadir'), $this->ratio($p['announced'], $a['hadir'])),
            'baki' => $this->stat($p['baki'], __('Hadir, belum diumumkan'), $this->ratio($p['baki'], $a['hadir'])),
            'progress' => $this->stat($p['percent'], __('Kemajuan pengumuman'), min(100.0, $p['percent']), '%'),
        ];
    }

    /**
     * `value` is display-ready; `raw` and `ratio` (0-100) let the UI animate counts, bars and rings.
     *
     * @return array{value: string, raw: int|float, suffix: string, hint: string, ratio: float|null}
     */
    private function stat(int|float $raw, string $hint, ?float $ratio = null, string $suffix = ''): array
    {
        return [
            'value' => is_int($raw) ? number_format($raw) : number_format($raw, 1).$suffix,
            'raw' => $raw,
            'suffix' => $suffix,
            'hint' => $hint,
            'ratio' => $ratio,
        ];
    }

    private function ratio(int $part, int $whole): float
    {
        return $whole > 0 ? min(100.0, round($part / $whole * 100, 1)) : 0.0;
    }

    private function percent(int $part, int $whole): string
    {
        return number_format($this->ratio($part, $whole), 1).'%';
    }

    /**
     * @return array<string, string>
     */
    private function status(bool $withUsers): array
    {
        $sesi = $this->calling->activeOnAirSesi();
        $status = [
            'sesi' => $sesi?->sesi ?? '—',
            'late' => $sesi === null ? '—' : ($sesi->is_late ? __('Sesi lewat') : __('Sesi biasa')),
            'next_late' => $sesi?->is_late ? (string) $this->calling->previewNextLateCallingNumber($sesi) : '—',
        ];

        if ($withUsers) {
            $counts = User::query()->selectRaw('role, COUNT(*) AS total')->groupBy('role')->pluck('total', 'role');
            $status['mode'] = EventModeService::options()[$this->eventMode->current()];
            $status['users'] = sprintf(
                '%d admin · %d media · %d user',
                (int) $counts->get(User::ROLE_ADMIN, 0),
                (int) $counts->get(User::ROLE_MEDIA, 0),
                (int) $counts->get(User::ROLE_USER, 0),
            );
        }

        return $status;
    }

    /**
     * @return Collection<int, CarbonInterface>
     */
    private function checkInTimes(?int $sesiId): Collection
    {
        return $this->scoped(Pegawai::query(), $sesiId)
            ->where('is_attend', true)
            ->whereNotNull('hadir_at')
            ->pluck('hadir_at');
    }

    /**
     * @return Collection<int, CarbonInterface>
     */
    private function announcedTimes(?int $sesiId): Collection
    {
        return $this->progress->announcedOfficersQuery($sesiId)
            ->whereNotNull('announced_at')
            ->pluck('announced_at');
    }

    /**
     * Counts per 15-minute slot for the most recent day that has data.
     *
     * @param  Collection<int, CarbonInterface>  $times
     * @return array{type: string, categories: list<string>, series: list<array{name: string, data: list<int>}>}
     */
    public function timeline(Collection $times, string $seriesName): array
    {
        if ($times->isEmpty()) {
            return [
                'type' => 'area',
                'categories' => [],
                'series' => [['name' => $seriesName, 'data' => []]],
            ];
        }

        $latest = $times->sortBy(fn (CarbonInterface $t) => $t->getTimestamp())->last();
        $day = $latest->toDateString();
        $slots = [];

        foreach ($times as $time) {
            if ($time->toDateString() !== $day) {
                continue;
            }
            $slot = $time->hour * 60 + intdiv($time->minute, self::BUCKET_MINUTES) * self::BUCKET_MINUTES;
            $slots[$slot] = ($slots[$slot] ?? 0) + 1;
        }

        $categories = [];
        $data = [];
        for ($m = min(array_keys($slots)); $m <= max(array_keys($slots)); $m += self::BUCKET_MINUTES) {
            $categories[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
            $data[] = $slots[$m] ?? 0;
        }

        return [
            'type' => 'area',
            'categories' => $categories,
            'series' => [['name' => $seriesName, 'data' => $data]],
        ];
    }

    private function bySesi(): array
    {
        $rows = Pegawai::query()
            ->whereNotNull('sesi_majlis_id')
            ->selectRaw(
                'sesi_majlis_id, '
                .'COALESCE(SUM(CASE WHEN is_attend = 1 AND is_late = 0 THEN 1 ELSE 0 END), 0) AS tepat, '
                .'COALESCE(SUM(CASE WHEN is_attend = 1 AND is_late = 1 THEN 1 ELSE 0 END), 0) AS lewat, '
                .'COALESCE(SUM(CASE WHEN rsvp = 1 AND is_attend = 0 THEN 1 ELSE 0 END), 0) AS belum'
            )
            ->groupBy('sesi_majlis_id')
            ->get()
            ->keyBy('sesi_majlis_id');

        $sesis = SesiMajlis::query()->select(['id', 'sesi'])->orderBy('id')->get();

        return [
            'type' => 'bar',
            'stacked' => true,
            'categories' => $sesis->pluck('sesi')->all(),
            'series' => [
                ['name' => __('Tepat masa'), 'data' => $sesis->map(fn ($s) => (int) ($rows->get($s->id)->tepat ?? 0))->all()],
                ['name' => __('Lewat'), 'data' => $sesis->map(fn ($s) => (int) ($rows->get($s->id)->lewat ?? 0))->all()],
                ['name' => __('Belum hadir'), 'data' => $sesis->map(fn ($s) => (int) ($rows->get($s->id)->belum ?? 0))->all()],
            ],
        ];
    }

    private function statusMix(array $a): array
    {
        return [
            'type' => 'donut',
            'labels' => [__('Tepat masa'), __('Lewat'), __('RSVP belum hadir'), __('Tiada RSVP')],
            'series' => [$a['tepat'], $a['lewat'], $a['belum_hadir'], $a['tiada_rsvp']],
        ];
    }

    private function topPtj(?int $sesiId): array
    {
        return $this->attendedBy('ptj_id', $sesiId, fn (Collection $ids) => Ptj::query()
            ->whereIn('id', $ids)->pluck('nama_ptj', 'id'));
    }

    private function byBersara(?int $sesiId): array
    {
        return $this->attendedBy('bersara_id', $sesiId, fn (Collection $ids) => Bersara::query()
            ->whereIn('id', $ids)->pluck('jenis_bersara', 'id'));
    }

    /**
     * @param  callable(Collection): Collection  $names
     */
    private function attendedBy(string $column, ?int $sesiId, callable $names): array
    {
        $rows = $this->scoped(Pegawai::query(), $sesiId)
            ->where('is_attend', true)
            ->whereNotNull($column)
            ->selectRaw("{$column} AS group_id, COUNT(*) AS total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit(self::RECENT_LIMIT)
            ->get();

        $labels = $names($rows->pluck('group_id'));

        return [
            'type' => 'bar',
            'horizontal' => true,
            'categories' => $rows->map(fn ($r) => (string) ($labels->get($r->group_id) ?? '—'))->all(),
            'series' => [['name' => __('Hadir'), 'data' => $rows->map(fn ($r) => (int) $r->total)->all()]],
        ];
    }

    /**
     * @return list<list<string>>
     */
    private function recentCheckIns(?int $sesiId): array
    {
        return $this->scoped(Pegawai::query(), $sesiId)
            ->where('is_attend', true)
            ->whereNotNull('hadir_at')
            ->with(['ptj:id,nama_ptj', 'sesiMajlis:id,sesi'])
            ->orderByDesc('hadir_at')
            ->limit(self::RECENT_LIMIT)
            ->get(['id', 'nama', 'ptj_id', 'sesi_majlis_id', 'hadir_at'])
            ->map(fn (Pegawai $p) => [
                (string) $p->nama,
                $p->ptj?->nama_ptj ?? '—',
                $p->sesiMajlis?->sesi ?? '—',
                $p->hadir_at->format('H:i:s'),
            ])
            ->all();
    }

    /**
     * @return list<list<string>>
     */
    private function recentAnnouncements(?int $sesiId): array
    {
        return $this->progress->announcedOfficersQuery($sesiId)
            ->with(['pegawai:id,nama,ptj_id', 'pegawai.ptj:id,nama_ptj'])
            ->orderByDesc('announced_at')
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->filter(fn ($row) => $row->pegawai !== null)
            ->map(fn ($row) => [
                (string) $row->pegawai->nama,
                $row->pegawai->ptj?->nama_ptj ?? '—',
                $row->announced_at?->format('H:i:s') ?? '—',
            ])
            ->values()
            ->all();
    }

    private function scoped(Builder $query, ?int $sesiId): Builder
    {
        return $sesiId === null ? $query : $query->where('sesi_majlis_id', $sesiId);
    }
}
