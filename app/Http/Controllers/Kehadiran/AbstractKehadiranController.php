<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kehadiran;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\SesiMajlis;
use App\Services\Kehadiran\KehadiranCallingService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

abstract class AbstractKehadiranController extends Controller
{
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
            'allSesis' => SesiMajlis::query()->orderBy('id')->get(),
        ]);
    }

    public function stats(): JsonResponse
    {
        $counts = $this->kehadiranSummaryCounts();

        return response()->json([
            'total_pegawai' => $counts['totalPegawai'],
            'total_rsvp' => $counts['totalRsvp'],
            'total_hadir' => $counts['totalHadir'],
        ]);
    }

    public function datatable()
    {
        $query = Pegawai::query()->with(['ptj', 'jawatan']);

        if (request()->filled('sesi_majlis_id')) {
            $query->where('sesi_majlis_id', request()->integer('sesi_majlis_id'));
        }

        $this->callingService->applyKehadiranDataTableOrder($query);
        $actionsView = $this->bladeNamespace().'::kehadiran.actions';

        return DataTables::of($query)
            ->filterColumn('nama', function ($query, $keyword) {
                if (trim((string) $keyword) === '') {
                    return;
                }

                $like = '%'.mb_strtolower((string) $keyword, 'UTF-8').'%';

                $query->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(nama) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(no_kp) LIKE ?', [$like]);
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
            ->removeColumn('no_panggilan_lewat')
            ->addColumn('action', fn (Pegawai $pegawai) => view($actionsView, ['pegawai' => $pegawai])->render())
            ->rawColumns(['nama', 'rsvp_sesi_label', 'action'])
            ->make(true);
    }

    public function getDetails(Pegawai $pegawai): JsonResponse
    {
        $pegawai->loadMissing(['ptj', 'sesiMajlis']);
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
        $willAttend = ! $pegawai->is_attend;
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

        DB::transaction(function () use ($pegawai, $willAttend, $activeSesi): void {
            $pegawai->is_attend = $willAttend;

            if ($pegawai->is_attend && $activeSesi !== null) {
                $pegawai->sesi_majlis_id = $activeSesi->id;
                if (! $pegawai->rsvp) {
                    $pegawai->no_kerusi = null;
                    $pegawai->no_meja = null;
                    $this->callingService->assignLateCallingNumberIfApplicable($pegawai, $activeSesi);
                } else {
                    $pegawai->no_meja = $this->callingService->calculateTableNumber($pegawai->no_kerusi, $activeSesi);
                    if ($activeSesi->is_late) {
                        $this->callingService->assignLateCallingNumberIfApplicable($pegawai, $activeSesi);
                    } else {
                        $pegawai->is_late = false;
                        $pegawai->no_panggilan_lewat = null;
                    }
                }
            } else {
                $this->callingService->clearLateCallingOnCancel($pegawai);
            }

            $pegawai->save();
        });

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
        return [
            'totalPegawai' => Pegawai::query()->count(),
            'totalRsvp' => Pegawai::query()->where('rsvp', true)->count(),
            'totalHadir' => Pegawai::query()->where('is_attend', true)->count(),
        ];
    }

    private function kehadiranView(string $name, array $data = []): View
    {
        return view($this->bladeNamespace().'::kehadiran.'.$name, $data);
    }

    private function renderOfficerCell(Pegawai $pegawai): string
    {
        $nama = e($pegawai->nama);
        $kp = e((string) ($pegawai->no_kp ?? '—'));
        $initials = e($this->officerInitials($pegawai->nama));
        $kpLabel = e(__('No. KP'));
        $jawatanLine = '';
        $descJawatan = $pegawai->jawatan?->desc_jawatan;
        if (filled($descJawatan)) {
            $jawatanLine = '<p class="kawalan-dt-officer-jawatan">'.e((string) $descJawatan).'</p>';
        }

        return '<div class="kawalan-dt-officer flex max-w-[20rem] items-start gap-3">'
            .'<span class="kawalan-dt-officer-avatar" aria-hidden="true">'.$initials.'</span>'
            .'<div class="min-w-0 flex-1">'
            .'<p class="kawalan-dt-officer-name leading-snug">'.$nama.'</p>'
            .'<p class="kawalan-dt-officer-kp-line mt-1">'
            .'<span class="kawalan-dt-officer-kp-label">'.$kpLabel.'</span>'
            .'<span class="kawalan-dt-officer-kp">'.$kp.'</span>'
            .'</p>'
            .$jawatanLine
            .'</div>'
            .'</div>';
    }

    private function officerInitials(string $nama): string
    {
        $trimmed = trim($nama);
        if ($trimmed === '') {
            return '?';
        }

        $parts = preg_split('/\s+/u', $trimmed) ?: [];
        if (count($parts) >= 2) {
            $first = mb_substr($parts[0], 0, 1);
            $last = mb_substr($parts[count($parts) - 1], 0, 1);

            return mb_strtoupper($first.$last, 'UTF-8');
        }

        return mb_strtoupper(mb_substr($trimmed, 0, min(2, mb_strlen($trimmed))), 'UTF-8');
    }
}
