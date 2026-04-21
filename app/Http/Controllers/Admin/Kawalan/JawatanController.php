<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\Jawatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class JawatanController extends Controller
{
    public function index(): View
    {
        return view('admin::kawalan.jawatan.index');
    }

    public function datatable()
    {
        return DataTables::of(Jawatan::query())
            ->addColumn('created_at', fn (Jawatan $jawatan) => $jawatan->created_at?->format('d M Y') ?? '')
            ->addColumn('action', fn (Jawatan $jawatan) => view('admin::kawalan.jawatan.actions', ['jawatan' => $jawatan])->render())
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'desc_jawatan' => ['required', 'string', 'max:255', 'unique:jawatans,desc_jawatan'],
        ]);

        Jawatan::create($validated);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Jawatan $jawatan): JsonResponse
    {
        $validated = $request->validate([
            'desc_jawatan' => ['required', 'string', 'max:255', 'unique:jawatans,desc_jawatan,'.$jawatan->id],
        ]);

        $jawatan->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Jawatan $jawatan): JsonResponse
    {
        $jawatan->delete();

        return response()->json(['success' => true]);
    }
}
