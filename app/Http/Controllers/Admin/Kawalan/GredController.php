<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\Gred;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class GredController extends Controller
{
    public function index(): View
    {
        return view('admin::kawalan.gred.index');
    }

    public function datatable()
    {
        return DataTables::of(Gred::query())
            ->addColumn('created_at', fn (Gred $gred) => $gred->created_at?->format('d M Y') ?? '')
            ->addColumn('action', fn (Gred $gred) => view('admin::kawalan.gred.actions', ['gred' => $gred])->render())
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'desc_gred' => ['required', 'string', 'max:255', 'unique:greds,desc_gred'],
        ]);

        Gred::create($validated);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Gred $gred): JsonResponse
    {
        $validated = $request->validate([
            'desc_gred' => ['required', 'string', 'max:255', 'unique:greds,desc_gred,'.$gred->id],
        ]);

        $gred->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Gred $gred): JsonResponse
    {
        $gred->delete();

        return response()->json(['success' => true]);
    }
}
