<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Kehadiran\Concerns\RendersOfficerCell;
use App\Http\Requests\Admin\PegawaiRsvpRequest;
use App\Models\Pegawai;
use App\Services\Kehadiran\RsvpSeatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class RsvpController extends Controller
{
    use RendersOfficerCell;

    public function __construct(private readonly RsvpSeatService $seats) {}

    public function index(): View
    {
        return view('admin::rsvp.index', [
            'counts' => $this->summaryCounts(),
            'lastSeat' => $this->seats->lastRsvpSeat(),
        ]);
    }

    public function datatable()
    {
        $query = Pegawai::query()
            ->select(['id', 'nama', 'no_kp', 'ptj_id', 'jawatan_id', 'rsvp', 'no_kerusi', 'is_attend'])
            ->with(['ptj:id,nama_ptj', 'jawatan:id,desc_jawatan']);

        if (in_array(request()->input('rsvp'), ['0', '1'], true)) {
            $query->where('rsvp', request()->boolean('rsvp'));
        }

        return DataTables::of($query)
            // Tidak Hadir first: those are the rows admins come here to change.
            ->order(fn ($query) => $query->orderBy('rsvp')->orderBy('id'))
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
            ->addColumn('ptj_name', fn (Pegawai $pegawai) => e((string) ($pegawai->ptj?->nama_ptj ?? '-')))
            ->addColumn('rsvp_label', fn (Pegawai $pegawai) => $this->renderRsvpPill((bool) $pegawai->rsvp))
            ->addColumn('no_kerusi', fn (Pegawai $pegawai) => e((string) ($pegawai->no_kerusi ?? '-')))
            ->addColumn('kehadiran_label', fn (Pegawai $pegawai) => $pegawai->is_attend
                ? '<span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">'.e(__('Sudah hadir')).'</span>'
                : '<span class="text-sm text-muted-foreground">'.e(__('Belum hadir')).'</span>')
            ->addColumn('action', fn (Pegawai $pegawai) => $this->renderActionCell($pegawai))
            ->rawColumns(['nama', 'rsvp_label', 'kehadiran_label', 'action'])
            ->make(true);
    }

    public function show(Pegawai $pegawai): JsonResponse
    {
        $pegawai->loadMissing(['ptj:id,nama_ptj', 'jawatan:id,desc_jawatan', 'gred:id,desc_gred']);

        return response()->json([
            'success' => true,
            'pegawai' => [
                'id' => $pegawai->id,
                'nama' => $pegawai->nama,
                'no_kp' => $pegawai->no_kp,
                'ptj_name' => $pegawai->ptj?->nama_ptj ?? '-',
                'jawatan_name' => $pegawai->jawatan?->desc_jawatan ?? '-',
                'gred_name' => $pegawai->gred?->desc_gred ?? '-',
                'sesi_name' => (int) $pegawai->s_kehadiran === Pegawai::S_KEHADIRAN_PETANG ? __('Petang') : __('Pagi'),
                'rsvp' => (bool) $pegawai->rsvp,
                'no_kerusi' => $pegawai->no_kerusi > 0 ? $pegawai->no_kerusi : null,
                'is_attend' => (bool) $pegawai->is_attend,
            ],
            'last_seat' => $this->seats->lastRsvpSeat(),
            'suggested_seat' => $this->seats->suggestNextSeat($pegawai->id),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function update(PegawaiRsvpRequest $request, Pegawai $pegawai): JsonResponse
    {
        $rsvp = $request->rsvp();
        $seat = $request->noKerusi();

        [$before, $after] = DB::transaction(function () use ($pegawai, $rsvp, $seat): array {
            /** @var Pegawai $locked */
            $locked = Pegawai::query()->whereKey($pegawai->getKey())->lockForUpdate()->firstOrFail();

            // Seats and late numbers are handed out at check-in; editing after that would desync them.
            if ($locked->is_attend) {
                throw ValidationException::withMessages([
                    'rsvp' => __('Pegawai sudah hadir. Batalkan kehadiran dahulu.'),
                ]);
            }

            if ($seat !== null && ($owner = $this->seats->seatOwner($seat, $locked->id)) !== null) {
                throw ValidationException::withMessages([
                    'no_kerusi' => __('No. kerusi :seat sudah digunakan oleh :nama.', ['seat' => $seat, 'nama' => $owner->nama]),
                ]);
            }

            $before = ['rsvp' => (bool) $locked->rsvp, 'no_kerusi' => $locked->no_kerusi];

            $locked->rsvp = $rsvp;
            $locked->no_kerusi = $seat;
            $locked->save();

            return [$before, ['rsvp' => $rsvp, 'no_kerusi' => $seat]];
        });

        // No audit log in the app: keep a trace of who changed what.
        Log::info('Admin updated RSVP', [
            'user_id' => $request->user()?->id,
            'pegawai_id' => $pegawai->id,
            'before' => $before,
            'after' => $after,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('RSVP :nama dikemas kini.', ['nama' => $pegawai->nama]),
            'counts' => $this->summaryCounts(),
            'last_seat' => $this->seats->lastRsvpSeat(),
        ]);
    }

    /**
     * @return array{total: int, rsvp_hadir: int, rsvp_tidak_hadir: int}
     */
    private function summaryCounts(): array
    {
        $row = Pegawai::query()
            ->selectRaw(
                'COUNT(*) AS total, '
                .'COALESCE(SUM(CASE WHEN rsvp = 1 THEN 1 ELSE 0 END), 0) AS rsvp_hadir'
            )
            ->first();

        return [
            'total' => (int) $row->total,
            'rsvp_hadir' => (int) $row->rsvp_hadir,
            'rsvp_tidak_hadir' => (int) $row->total - (int) $row->rsvp_hadir,
        ];
    }

    private function renderRsvpPill(bool $rsvp): string
    {
        if ($rsvp) {
            return '<span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-emerald-100 to-cyan-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 shadow-sm shadow-emerald-100 dark:from-emerald-900/40 dark:to-cyan-900/40 dark:text-emerald-200 dark:shadow-emerald-900/30"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500/75 dark:bg-emerald-300/80"></span>'.e(__('Hadir')).'</span>';
        }

        return '<span class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-rose-100 to-fuchsia-100 px-2.5 py-1 text-xs font-semibold text-rose-800 shadow-sm shadow-rose-100 dark:from-rose-900/40 dark:to-fuchsia-900/40 dark:text-rose-200 dark:shadow-rose-900/30"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-rose-500/75 dark:bg-rose-300/80"></span>'.e(__('Tidak Hadir')).'</span>';
    }

    private function renderActionCell(Pegawai $pegawai): string
    {
        $locked = (bool) $pegawai->is_attend;
        $title = $locked ? __('Sudah hadir — batalkan kehadiran dahulu') : __('Kemas kini RSVP');

        return '<div class="flex w-full items-center justify-center">'
            .'<button type="button" class="btn btn-sm btn-outline js-edit-rsvp"'
            .' data-id="'.e((string) $pegawai->id).'"'
            .($locked ? ' disabled aria-disabled="true"' : '')
            .' title="'.e($title).'">'
            .'<i class="'.($locked ? 'ri-lock-line' : 'ri-edit-line').'"></i>'
            .'<span>'.e(__('Edit')).'</span>'
            .'</button>'
            .'</div>';
    }
}
