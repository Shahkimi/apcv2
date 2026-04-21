<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\Meja;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class MejaController extends Controller
{
    public function index(): View
    {
        return view('admin::kawalan.meja.index');
    }

    public function datatable()
    {
        return DataTables::of(Meja::query())
        ->addColumn('seats_display', function (Meja $meja) {
            $n = e((string) $meja->sizing);
        
            return '<div class="inline-flex items-center gap-3 py-1">'
        
                // Icon container — slightly larger, deeper gradient, softer shadow
                . '<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl '
                .     'bg-gradient-to-br from-primary/20 via-primary/10 to-transparent '
                .     'text-primary shadow ring-1 ring-inset ring-primary/25 '
                .     'dark:from-primary/25 dark:via-primary/15 dark:to-transparent">'
                .     '<i class="ri-armchair-line text-lg leading-none" aria-hidden="true"></i>'
                . '</span>'
        
                // Badge pill wrapping both number + label
                . '<span class="inline-flex items-baseline gap-1.5 rounded-full '
                .     'bg-primary/8 px-3 py-1 ring-1 ring-inset ring-primary/15 '
                .     'dark:bg-primary/12 dark:ring-primary/20">'
                .     '<span class="text-base font-bold tabular-nums tracking-tight text-foreground">' . $n . '</span>'
                .     '<span class="text-xs font-medium uppercase tracking-wide text-primary/70">' . e(__('kerusi')) . '</span>'
                . '</span>'
        
                . '</div>';
        })
            ->addColumn('created_at', fn (Meja $meja) => $meja->created_at?->format('d M Y') ?? '')
            ->addColumn('action', fn (Meja $meja) => view('admin::kawalan.meja.actions', ['meja' => $meja])->render())
            ->rawColumns(['action', 'seats_display'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sizing' => ['required', 'integer', 'min:1'],
        ]);

        Meja::create($validated);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Meja $meja): JsonResponse
    {
        $validated = $request->validate([
            'sizing' => ['required', 'integer', 'min:1'],
        ]);

        $meja->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Meja $meja): JsonResponse
    {
        $meja->delete();

        return response()->json(['success' => true]);
    }
}
