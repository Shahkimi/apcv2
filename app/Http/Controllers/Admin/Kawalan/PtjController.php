<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\Ptj;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PtjController extends Controller
{
    public function index(): View
    {
        return view('admin::kawalan.ptj.index');
    }

    public function datatable()
    {
        return DataTables::of(Ptj::query())
            ->addColumn('created_at', fn (Ptj $ptj) => $ptj->created_at?->format('d M Y') ?? '')
            ->addColumn('action', fn (Ptj $ptj) => view('admin::kawalan.ptj.actions', ['ptj' => $ptj])->render())
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_ptj' => ['required', 'string', 'max:255', 'unique:ptjs,nama_ptj'],
        ]);

        Ptj::create($validated);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Ptj $ptj): JsonResponse
    {
        $validated = $request->validate([
            'nama_ptj' => ['required', 'string', 'max:255', 'unique:ptjs,nama_ptj,'.$ptj->id],
        ]);

        $ptj->update($validated);

        return response()->json(['success' => true]);
    }

    public function destroy(Ptj $ptj): JsonResponse
    {
        $ptj->delete();

        return response()->json(['success' => true]);
    }
}
