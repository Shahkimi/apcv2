<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kehadiran;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Kehadiran\Concerns\RendersOfficerCell;
use App\Models\Pegawai;
use App\Models\SesiMajlis;
use App\Services\Kehadiran\KehadiranCallingService;
use App\Services\SettingsService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

abstract class AbstractKehadiranController extends Controller
{
    use RendersOfficerCell;

    public function __construct(
        private readonly KehadiranCallingService $callingService,
        private readonly SettingsService $settings,
    ) {}

    abstract protected function bladeNamespace(): string;

    public function index(): View
    {
        $counts = $this->kehadiranSummaryCounts();

        return $this->kehadiranView('index', [
            'totalPegawai' => $counts['totalPegawai'],
            'totalRsvp' => $counts['totalRsvp'],
            'totalHadir' => $counts['totalHadir'],
            'lateSessionOnAir' => $this->callingService->lateSessionOnAirExists(),
            'allSesis' => SesiMajlis::query()->select(['id', 'sesi'])->orderBy('id')->get(),
        ]);
    }

    public function stats(): JsonResponse
    {
        $counts = $this->kehadiranSummaryCounts();

        return response()->json([
            'total_pegawai' => $counts['totalPegawai'],
            'total_rsvp' => $counts['totalRsvp'],
            'total_hadir' => $counts['totalHadir'],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function datatable()
    {
        $query = Pegawai::query()
            ->select(['id', 'nama', 'no_kp', 'ptj_id', 'jawatan_id', 'rsvp', 'no_kerusi', 'is_attend', 's_kehadiran'])
            ->with(['ptj:id,nama_ptj', 'jawatan:id,desc_jawatan']);

        if (request()->filled('sesi_majlis_id')) {
            $query->where('sesi_majlis_id', request()->integer('sesi_majlis_id'));
        }

        return DataTables::of($query)
            ->order(fn ($query) => $this->callingService->applyKehadiranDataTableOrder($query))
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
            ->editColumn('nama', fn (Pegawai $pegawai) => $this->renderOfficerCell($pegawai))
            ->removeColumn('no_kp')
            ->addColumn('rsvp_sesi_label', function (Pegawai $pegawai) {
                if ($pegawai->rsvp) {
                    $rsvp = '<span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-emerald-100 to-cyan-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 shadow-sm shadow-emerald-100 dark:from-emerald-900/40 dark:to-cyan-900/40 dark:text-emerald-200 dark:shadow-emerald-900/30"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500/75 dark:bg-emerald-300/80"></span>'.e(__('Ya')).'</span>';
                } else {
                    $rsvp = '<span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-rose-100 to-fuchsia-100 px-2.5 py-1 text-xs font-semibold text-rose-800 shadow-sm shadow-rose-100 dark:from-rose-900/40 dark:to-fuchsia-900/40 dark:text-rose-200 dark:shadow-rose-900/30"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500/75 dark:bg-rose-300/80"></span>'.e(__('Tidak')).'</span>';
                }

                $sesiText = (int) $pegawai->s_kehadiran === Pegawai::S_KEHADIRAN_PETANG
                    ? e(__('Petang'))
                    : e(__('Pagi'));

                return '<div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 align-middle text-center">'.$rsvp.'<span class="text-muted-foreground shrink-0">'.e(' - ').'</span><span class="text-sm font-medium text-foreground">'.$sesiText.'</span></div>';
            })
            ->addColumn('no_kerusi', fn (Pegawai $pegawai) => e((string) ($pegawai->no_kerusi ?? '-')))
            ->addColumn('ptj_name', fn (Pegawai $pegawai) => e((string) ($pegawai->ptj?->nama_ptj ?? '-')))
            ->addColumn('action', fn (Pegawai $pegawai) => $this->renderActionCell($pegawai))
            ->rawColumns(['nama', 'rsvp_sesi_label', 'action'])
            ->make(true);
    }

    public function getDetails(Pegawai $pegawai): JsonResponse
    {
        $pegawai->loadMissing(['ptj:id,nama_ptj', 'sesiMajlis:id,sesi']);
        $activeSesi = $this->callingService->activeOnAirSesi();
        $previewLateNumber = null;
        $previewNoMeja = $activeSesi !== null
            ? ($this->callingService->calculateTableNumber($pegawai->no_kerusi, $activeSesi) ?? '-')
            : ($pegawai->no_meja ?? '-');
        if (! $pegawai->rsvp && ! $pegawai->is_attend && $activeSesi !== null) {
            $previewLateNumber = $this->callingService->previewNextLateCallingNumber($activeSesi);
        }

        return response()->json([
            'success' => true,
            'show_table_number' => $this->settings->showTableNumberInDialog(),
            'active_sesi_s_kehadiran' => $activeSesi !== null ? (int) $activeSesi->s_kehadiran : null,
            'active_sesi_name' => $activeSesi?->sesi,
            'pegawai' => [
                'id' => $pegawai->id,
                'nama' => $pegawai->nama,
                'no_kp' => $pegawai->no_kp,
                'rsvp' => (bool) $pegawai->rsvp,
                's_kehadiran' => (int) $pegawai->s_kehadiran,
                'sesi_name' => $pegawai->sesiMajlis?->sesi ?? '—',
                'ptj_name' => $pegawai->ptj?->nama_ptj ?? '-',
                'no_kerusi' => $pegawai->no_kerusi ?? '-',
                'no_meja' => $previewNoMeja,
                'no_panggilan_lewat' => $this->jsonNoPanggilanLewatForDetails($pegawai->no_panggilan_lewat, $previewLateNumber),
                'is_attend' => (bool) $pegawai->is_attend,
            ],
        ]);
    }

    public function verify(Pegawai $pegawai): JsonResponse
    {
        $intent = request()->has('is_attend') ? request()->boolean('is_attend') : null;
        $willAttend = $intent ?? ! $pegawai->is_attend;
        $activeSesi = null;

        if ($willAttend) {
            $activeSesi = $this->callingService->activeOnAirSesi();
            if ($activeSesi === null) {
                return response()->json([
                    'success' => false,
                    'message' => __('Tiada sesi aktif. Sila aktifkan sesi terlebih dahulu.'),
                ], 422);
            }

            if ((int) $pegawai->s_kehadiran !== (int) $activeSesi->s_kehadiran) {
                $pegawaiType = (int) $pegawai->s_kehadiran === Pegawai::S_KEHADIRAN_PAGI
                    ? __('pagi')
                    : __('petang');
                $sesiType = (int) $activeSesi->s_kehadiran === SesiMajlis::S_KEHADIRAN_PAGI
                    ? __('pagi')
                    : __('petang');

                return response()->json([
                    'success' => false,
                    'message' => __('Pegawai ini berdaftar untuk sesi :pegawai_type tetapi sesi semasa adalah :sesi_type.', [
                        'pegawai_type' => $pegawaiType,
                        'sesi_type' => $sesiType,
                    ]),
                ], 422);
            }
        }

        try {
            $pegawai = DB::transaction(function () use ($pegawai, $willAttend, $activeSesi): Pegawai {
                /** @var Pegawai $locked */
                $locked = Pegawai::query()->whereKey($pegawai->getKey())->lockForUpdate()->firstOrFail();

                // Idempotent: a duplicate submit (double-click, retry after a 503) is a no-op
                // instead of flipping the state back.
                if ($locked->is_attend === $willAttend) {
                    return $locked;
                }

                $locked->is_attend = $willAttend;

                if ($locked->is_attend && $activeSesi !== null) {
                    $locked->hadir_at = now();
                    $locked->sesi_majlis_id = $activeSesi->id;
                    if (! $locked->rsvp) {
                        $locked->no_kerusi = null;
                        $locked->no_meja = null;
                        $this->callingService->assignLateCallingNumberIfApplicable($locked, $activeSesi);
                    } else {
                        $locked->no_meja = $this->callingService->calculateTableNumber($locked->no_kerusi, $activeSesi);
                        if ($activeSesi->is_late) {
                            $this->callingService->assignLateCallingNumberIfApplicable($locked, $activeSesi);
                        } else {
                            $locked->is_late = false;
                            $locked->no_panggilan_lewat = null;
                        }
                    }
                } else {
                    $this->callingService->clearLateCallingOnCancel($locked);
                }

                $locked->save();

                return $locked;
            });
        } catch (QueryException $e) {
            $driverErrno = $e->errorInfo[1] ?? null;
            if (in_array($driverErrno, [1205, 1213], true)) {
                report($e);

                return response()->json([
                    'success' => false,
                    'message' => __('Sistem sedang sibuk. Sila cuba lagi.'),
                ], 503);
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'is_attend' => (bool) $pegawai->is_attend,
            'no_meja' => $pegawai->no_meja,
            'no_panggilan_lewat' => $this->jsonNoPanggilanLewat($pegawai->no_panggilan_lewat),
            'message' => $pegawai->is_attend ? __('Kehadiran disahkan.') : __('Kehadiran dibatalkan.'),
        ]);
    }

    /**
     * @return int|string Positive late number, or '-' when unset / placeholder zero.
     */
    private function jsonNoPanggilanLewat(mixed $value): int|string
    {
        $n = (int) ($value ?? 0);

        return $n > 0 ? $n : '-';
    }

    /**
     * @return int|string Stored positive number, else preview for walk-ins, else '-'.
     */
    private function jsonNoPanggilanLewatForDetails(mixed $stored, ?int $previewLateNumber): int|string
    {
        $n = (int) ($stored ?? 0);
        if ($n > 0) {
            return $n;
        }

        if ($previewLateNumber !== null && $previewLateNumber > 0) {
            return $previewLateNumber;
        }

        return '-';
    }

    /**
     * @return array{totalPegawai: int, totalRsvp: int, totalHadir: int}
     */
    private function kehadiranSummaryCounts(): array
    {
        $row = Pegawai::query()
            ->selectRaw(
                'COUNT(*) AS total_pegawai, '
                .'COALESCE(SUM(CASE WHEN rsvp = 1 THEN 1 ELSE 0 END), 0) AS total_rsvp, '
                .'COALESCE(SUM(CASE WHEN is_attend = 1 THEN 1 ELSE 0 END), 0) AS total_hadir'
            )
            ->first();

        return [
            'totalPegawai' => (int) $row->total_pegawai,
            'totalRsvp' => (int) $row->total_rsvp,
            'totalHadir' => (int) $row->total_hadir,
        ];
    }

    private function kehadiranView(string $name, array $data = []): View
    {
        return view($this->bladeNamespace().'::kehadiran.'.$name, $data);
    }

    private function renderActionCell(Pegawai $pegawai): string
    {
        $isAttended = (bool) $pegawai->is_attend;
        $btnClass = $isAttended
            ? 'btn btn-sm rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-200'
            : 'btn btn-sm rounded-lg bg-primary text-primary-foreground shadow-sm hover:brightness-105';
        $icon = $isAttended ? 'ri-checkbox-circle-line' : 'ri-check-line';
        $label = $isAttended ? __('Hadir') : __('Sahkan');
        $title = $isAttended ? __('Tandakan belum hadir') : __('Sahkan kehadiran');

        return '<div class="flex w-full items-center justify-center">'
            .'<button type="button" class="'.e($btnClass).' js-verify-kehadiran"'
            .' data-id="'.e((string) $pegawai->id).'"'
            .' data-nama="'.e($pegawai->nama).'"'
            .' data-is-attend="'.($isAttended ? 1 : 0).'"'
            .' title="'.e($title).'">'
            .'<i class="'.e($icon).'"></i>'
            .'<span>'.e($label).'</span>'
            .'</button>'
            .'</div>';
    }
}
