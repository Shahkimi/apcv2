<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\SesiMajlis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class SesiMajlisController extends Controller
{
    public function index(): View
    {
        return view('admin::kawalan.sesi-majlis.index');
    }

    public function datatable()
    {
        return DataTables::of(SesiMajlis::query())
            ->addColumn('is_active_label', function (SesiMajlis $sesi) {
                $label = e($sesi->is_active ? __('Ya') : __('Tidak'));

                if ($sesi->is_active) {
                    $pillClasses = 'bg-gradient-to-r from-emerald-100 to-cyan-100 text-emerald-800 shadow-sm shadow-emerald-100 dark:from-emerald-900/40 dark:to-cyan-900/40 dark:text-emerald-200 dark:shadow-emerald-900/30';
                    $dotClasses = 'bg-emerald-500/75 dark:bg-emerald-300/80';
                    $dotAnimation = 'animate-pulse';
                } else {
                    $pillClasses = 'bg-gradient-to-r from-rose-100 to-fuchsia-100 text-rose-800 shadow-sm shadow-rose-100 dark:from-rose-900/40 dark:to-fuchsia-900/40 dark:text-rose-200 dark:shadow-rose-900/30';
                    $dotClasses = 'bg-rose-500/75 dark:bg-rose-300/80';
                    $dotAnimation = '';
                }

                return '<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold '.$pillClasses.'">'
                    .'<span class="h-1.5 w-1.5 shrink-0 rounded-full '.$dotClasses.' '.$dotAnimation.'"></span>'
                    .$label
                    .'</span>';
            })
            ->addColumn('is_late_label', function (SesiMajlis $sesi) {
                $label = e($sesi->is_late ? __('Ya') : __('Tidak'));

                if ($sesi->is_late) {
                    $pillClasses = 'bg-amber-100 text-amber-800 shadow-sm dark:bg-amber-900/40 dark:text-amber-200';
                    $dotClasses = 'bg-amber-500/80 dark:bg-amber-300/80';
                } else {
                    $pillClasses = 'bg-slate-100 text-slate-700 shadow-sm dark:bg-slate-800/50 dark:text-slate-200';
                    $dotClasses = 'bg-slate-400/80 dark:bg-slate-300/80';
                }

                return '<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold '.$pillClasses.'">'
                    .'<span class="h-1.5 w-1.5 shrink-0 rounded-full '.$dotClasses.'"></span>'
                    .$label
                    .'</span>';
            })
            ->addColumn('countdown_start_late', fn (SesiMajlis $sesi) => $sesi->countdown_start_late ?? '-')
            ->addColumn('seat_offset', fn (SesiMajlis $sesi) => e((string) ($sesi->seat_offset ?? 0)))
            ->addColumn('created_at', fn (SesiMajlis $sesi) => $sesi->created_at?->format('d M Y') ?? '')
            ->addColumn('action', fn (SesiMajlis $sesi) => view('admin::kawalan.sesi-majlis.actions', ['sesiMajlis' => $sesi])->render())
            ->rawColumns(['action', 'is_active_label', 'is_late_label'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sesi' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_late' => ['required', 'boolean'],
            'countdown_start_late' => ['nullable', 'integer', 'min:0'],
            'seat_offset' => ['required', 'integer', 'min:0'],
        ]);

        SesiMajlis::create([
            'sesi' => $validated['sesi'],
            'is_active' => $request->boolean('is_active'),
            'is_late' => $request->boolean('is_late'),
            'countdown_start_late' => $validated['countdown_start_late'] ?? null,
            'seat_offset' => (int) $validated['seat_offset'],
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, SesiMajlis $sesi_majlis): JsonResponse
    {
        $validated = $request->validate([
            'sesi' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_late' => ['required', 'boolean'],
            'countdown_start_late' => ['nullable', 'integer', 'min:0'],
            'seat_offset' => ['required', 'integer', 'min:0'],
        ]);

        $sesi_majlis->update([
            'sesi' => $validated['sesi'],
            'is_active' => $request->boolean('is_active'),
            'is_late' => $request->boolean('is_late'),
            'countdown_start_late' => $validated['countdown_start_late'] ?? null,
            'seat_offset' => (int) $validated['seat_offset'],
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(SesiMajlis $sesi_majlis): JsonResponse
    {
        $sesi_majlis->delete();

        return response()->json(['success' => true]);
    }
}
