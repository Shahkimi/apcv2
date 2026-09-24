<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kehadiran;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Kehadiran\Concerns\RendersOfficerCell;
use App\Models\Pegawai;
use App\Models\SesiMajlis;
use App\Services\Kehadiran\KehadiranCallingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

abstract class AbstractPaparanController extends Controller
{
    use RendersOfficerCell;

    public function __construct(
        private readonly KehadiranCallingService $callingService,
    ) {}

    abstract protected function bladeNamespace(): string;

    public function index(Request $request): View
    {
        $selectedSesiId = $this->resolveSelectedSesiId($request);

        return view($this->bladeNamespace().'::paparan.index', [
            'allSesis' => SesiMajlis::query()->select(['id', 'sesi', 'is_late'])->orderBy('id')->get(),
            'selectedSesiId' => $selectedSesiId,
            'lateSessionOnAir' => $this->callingService->lateSessionOnAirExists(),
            'refreshIntervalMs' => (int) Config::get('media.paparan_refresh_ms', 30_000),
            'datatableRoute' => route($this->bladeNamespace().'.paparan.datatable'),
            'statsRoute' => route($this->bladeNamespace().'.paparan.stats'),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        return response()->json($this->computeStats($request))
            ->header('Cache-Control', 'no-store, private');
    }

    public function datatable(Request $request)
    {
        $filters = $this->buildFilters($request);

        $base = Pegawai::query()
            ->select(['id', 'nama', 'no_kp', 'ptj_id', 'jawatan_id', 'no_kerusi', 'no_meja', 'no_panggilan_lewat', 'is_late', 'hadir_at'])
            ->with(['ptj:id,nama_ptj', 'jawatan:id,desc_jawatan'])
            ->tap($filters);

        $stats = $this->computeStats($request);

        return DataTables::of($base)
            ->order(fn ($query) => $this->callingService->applyPaparanLatestFirstOrder($query))
            ->filterColumn('nama', function ($query, $keyword) {
                if (trim((string) $keyword) === '') {
                    return;
                }

                $like = '%'.$keyword.'%';

                $query->where(function ($q) use ($like) {
                    $q->where('nama', 'like', $like)
                        ->orWhere('no_kp', 'like', $like);
                });
            })
            ->editColumn('nama', fn (Pegawai $pegawai) => $this->renderOfficerCell($pegawai, showKp: false))
            ->removeColumn('no_kp')
            ->addColumn('giliran', fn (Pegawai $pegawai) => $this->positiveOrDash(
                $pegawai->is_late ? $pegawai->no_panggilan_lewat : $pegawai->no_kerusi
            ))
            ->removeColumn('no_panggilan_lewat')
            ->addColumn('tempat_duduk', fn (Pegawai $pegawai) => $this->renderTempatDudukCell($pegawai))
            ->removeColumn('no_kerusi')
            ->removeColumn('no_meja')
            ->addColumn('ptj_name', fn (Pegawai $pegawai) => e((string) ($pegawai->ptj?->nama_ptj ?? '—')))
            ->addColumn('hadir_at_label', fn (Pegawai $pegawai) => $this->formatHadirAt($pegawai->hadir_at))
            ->addColumn('status_label', fn (Pegawai $pegawai) => $this->renderStatusPill((bool) $pegawai->is_late))
            ->rawColumns(['nama', 'tempat_duduk', 'status_label'])
            ->with(['stats' => $stats])
            ->make(true);
    }

    private function buildFilters(Request $request): \Closure
    {
        return function (Builder $query) use ($request): void {
            $query->where('is_attend', true);

            if ($request->filled('sesi_majlis_id')) {
                $query->where('sesi_majlis_id', $request->integer('sesi_majlis_id'));
            }
        };
    }

    /**
     * @return array{total_hadir: int, total_tepat: int, total_lewat: int}
     */
    private function computeStats(Request $request): array
    {
        $row = Pegawai::query()
            ->tap($this->buildFilters($request))
            ->selectRaw('COUNT(*) AS total_hadir, COALESCE(SUM(CASE WHEN is_late = 1 THEN 1 ELSE 0 END), 0) AS total_lewat')
            ->first();

        $totalHadir = (int) ($row->total_hadir ?? 0);
        $totalLewat = (int) ($row->total_lewat ?? 0);

        return [
            'total_hadir' => $totalHadir,
            'total_tepat' => $totalHadir - $totalLewat,
            'total_lewat' => $totalLewat,
        ];
    }

    private function resolveSelectedSesiId(Request $request): ?int
    {
        if (! $request->has('sesi_id')) {
            return $this->callingService->activeOnAirSesi()?->id;
        }

        if ($request->query('sesi_id') === '' || $request->query('sesi_id') === null) {
            return null;
        }

        $sesiId = $request->integer('sesi_id');

        return SesiMajlis::query()->whereKey($sesiId)->exists() ? $sesiId : null;
    }

    private function positiveOrDash(mixed $value): string
    {
        return (int) $value > 0 ? (string) (int) $value : '—';
    }

    private function renderTempatDudukCell(Pegawai $pegawai): string
    {
        $kerusi = $this->positiveOrDash($pegawai->no_kerusi);
        $meja = $this->positiveOrDash($pegawai->no_meja);

        return '<div class="flex items-center justify-center gap-1.5">'
            .'<span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold tabular-nums text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300" title="'.e(__('No. Kerusi')).'">'
            .'<i class="ri-armchair-line text-sm" aria-hidden="true"></i>'.$kerusi
            .'</span>'
            .'<span class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold tabular-nums text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300" title="'.e(__('No. Meja')).'">'
            .'<i class="ri-table-line text-sm" aria-hidden="true"></i>'.$meja
            .'</span>'
            .'</div>';
    }

    private function formatHadirAt(mixed $hadirAt): string
    {
        if ($hadirAt === null) {
            return '—';
        }

        $carbon = $hadirAt instanceof \Illuminate\Support\Carbon ? $hadirAt : \Illuminate\Support\Carbon::parse($hadirAt);

        return $carbon->isToday() ? $carbon->format('H:i:s') : $carbon->format('d/m H:i');
    }

    private function renderStatusPill(bool $isLate): string
    {
        if ($isLate) {
            return '<span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-amber-100 to-orange-100 px-2.5 py-1 text-xs font-semibold text-amber-800 shadow-sm shadow-amber-100 dark:from-amber-900/40 dark:to-orange-900/40 dark:text-amber-200 dark:shadow-amber-900/30"><i class="ri-alarm-warning-line text-sm"></i>'.e(__('Lewat')).'</span>';
        }

        return '<span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-emerald-100 to-cyan-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 shadow-sm shadow-emerald-100 dark:from-emerald-900/40 dark:to-cyan-900/40 dark:text-emerald-200 dark:shadow-emerald-900/30"><i class="ri-checkbox-circle-line text-sm"></i>'.e(__('Tepat masa')).'</span>';
    }
}
