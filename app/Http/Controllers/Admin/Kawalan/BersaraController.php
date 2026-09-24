<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\Bersara;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BersaraController extends Controller
{
    public function index(): View
    {
        return view('admin::kawalan.bersara.index');
    }

    public function datatable()
    {
        return DataTables::of(Bersara::query())
            ->addColumn('created_at', fn (Bersara $bersara) => $bersara->created_at?->format('d M Y') ?? '')
            ->addColumn('action', fn (Bersara $bersara) => view('admin::kawalan.bersara.actions', ['bersara' => $bersara])->render())
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenis_bersara' => ['required', 'string', 'max:255', 'unique:bersaras,jenis_bersara'],
        ]);

        Bersara::create($validated);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Bersara $bersara): JsonResponse
    {
        $validated = $request->validate([
            'jenis_bersara' => ['required', 'string', 'max:255', 'unique:bersaras,jenis_bersara,'.$bersara->id],
        ]);

        $bersara->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Bersara $bersara): JsonResponse
    {
        $bersara->delete();

        return response()->json(['success' => true]);
    }
}
