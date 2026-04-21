<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meja;
use App\Models\Pegawai;
use App\Models\SesiMajlis;
use App\Services\Kehadiran\KehadiranCallingService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class KehadiranController extends Controller
{
    public function __construct(
        private readonly KehadiranCallingService $callingService,
    ) {}

    public function index(): View
    {
        return view('admin::kehadiran.index', [
            'totalPegawai' => Pegawai::count(),
            'totalRsvp' => Pegawai::where('rsvp', true)->count(),
            'totalHadir' => Pegawai::where('is_attend', true)->count(),
            'lateSessionOnAir' => $this->callingService->lateSessionOnAirExists(),
            'allSesis' => SesiMajlis::query()->orderBy('id')->get(),
        ]);
    }

    public function datatable()
    {
        $query = Pegawai::query()->with(['ptj', 'sesiMajlis']);

        if (request()->filled('sesi_majlis_id')) {
            $query->where('sesi_majlis_id', request()->integer('sesi_majlis_id'));
        }

        $this->callingService->applyDefaultCallingOrder($query);

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
            ->addColumn('rsvp_label', function (Pegawai $pegawai) {
                if ($pegawai->rsvp) {
                    return '<span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-emerald-100 to-cyan-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 shadow-sm shadow-emerald-100 dark:from-emerald-900/40 dark:to-cyan-900/40 dark:text-emerald-200 dark:shadow-emerald-900/30"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500/75 dark:bg-emerald-300/80"></span>'.e(__('Ya')).'</span>';
                }

                return '<span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-rose-100 to-fuchsia-100 px-2.5 py-1 text-xs font-semibold text-rose-800 shadow-sm shadow-rose-100 dark:from-rose-900/40 dark:to-fuchsia-900/40 dark:text-rose-200 dark:shadow-rose-900/30"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500/75 dark:bg-rose-300/80"></span>'.e(__('Tidak')).'</span>';
            })
            ->addColumn('sesi_name', fn (Pegawai $pegawai) => e((string) ($pegawai->sesiMajlis?->sesi ?? '—')))
            ->addColumn('no_kerusi', fn (Pegawai $pegawai) => e((string) ($pegawai->no_kerusi ?? '-')))
            ->addColumn('no_panggilan_lewat', function (Pegawai $pegawai) {
                return $pegawai->no_panggilan_lewat !== null
                    ? e((string) $pegawai->no_panggilan_lewat)
                    : '—';
            })
            ->addColumn('ptj_name', fn (Pegawai $pegawai) => e((string) ($pegawai->ptj?->nama_ptj ?? '-')))
            ->addColumn('action', fn (Pegawai $pegawai) => view('admin::kehadiran.actions', ['pegawai' => $pegawai])->render())
            ->rawColumns(['nama', 'rsvp_label', 'action'])
            ->make(true);
    }

    public function getDetails(Pegawai $pegawai): JsonResponse
    {
        $pegawai->loadMissing(['ptj', 'sesiMajlis']);

        return response()->json([
            'success' => true,
            'pegawai' => [
                'id' => $pegawai->id,
                'nama' => $pegawai->nama,
                'no_kp' => $pegawai->no_kp,
                'sesi_name' => $pegawai->sesiMajlis?->sesi ?? '—',
                'ptj_name' => $pegawai->ptj?->nama_ptj ?? '-',
                'no_kerusi' => $pegawai->no_kerusi ?? '-',
                'no_meja' => $this->resolveTableNumber($pegawai->no_kerusi) ?? '-',
                'no_panggilan_lewat' => $pegawai->no_panggilan_lewat ?? '-',
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
        }

        $pegawai->is_attend = $willAttend;

        if ($pegawai->is_attend && $activeSesi !== null) {
            $pegawai->sesi_majlis_id = $activeSesi->id;
            $pegawai->no_meja = $this->resolveTableNumber($pegawai->no_kerusi);
            if ($activeSesi->is_late) {
                $this->callingService->assignLateCallingNumberIfApplicable($pegawai, $activeSesi);
            } else {
                $pegawai->is_late = false;
                $pegawai->no_panggilan_lewat = null;
            }
        } else {
            $this->callingService->clearLateCallingOnCancel($pegawai);
        }

        $pegawai->save();

        return response()->json([
            'success' => true,
            'is_attend' => (bool) $pegawai->is_attend,
            'no_meja' => $pegawai->no_meja,
            'no_panggilan_lewat' => $pegawai->no_panggilan_lewat ?? '-',
            'message' => $pegawai->is_attend ? __('Kehadiran disahkan.') : __('Kehadiran dibatalkan.'),
        ]);
    }

    private function renderOfficerCell(Pegawai $pegawai): string
    {
        $nama = e($pegawai->nama);
        $kp = e((string) ($pegawai->no_kp ?? '—'));
        $initials = e($this->officerInitials($pegawai->nama));
        $kpLabel = e(__('No. KP'));

        return '<div class="kawalan-dt-officer flex max-w-[20rem] items-start gap-3">'
            .'<span class="kawalan-dt-officer-avatar" aria-hidden="true">'.$initials.'</span>'
            .'<div class="min-w-0 flex-1">'
            .'<p class="kawalan-dt-officer-name leading-snug">'.$nama.'</p>'
            .'<p class="kawalan-dt-officer-kp-line mt-1">'
            .'<span class="kawalan-dt-officer-kp-label">'.$kpLabel.'</span>'
            .'<span class="kawalan-dt-officer-kp">'.$kp.'</span>'
            .'</p>'
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

    private function resolveTableNumber(int|string|null $seat): ?int
    {
        if ($seat === null || $seat === '' || ! is_numeric((string) $seat)) {
            return null;
        }

        $capacity = Meja::query()->orderBy('id')->value('sizing');

        if (! is_numeric($capacity) || (int) $capacity < 1) {
            return null;
        }

        $seatNumber = (int) $seat;

        if ($seatNumber < 1) {
            return null;
        }

        return (int) floor(($seatNumber - 1) / (int) $capacity) + 1;
    }
}
