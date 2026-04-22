<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Kawalan;

use App\Http\Controllers\Controller;
use App\Models\Backdrop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BackdropController extends Controller
{
    public function index(): View
    {
        return view('admin::kawalan.backdrop.index');
    }

    public function datatable()
    {
        return DataTables::of(Backdrop::query())
            ->addColumn('preview', function (Backdrop $backdrop) {
                $url = e($backdrop->image_url);

                return '<img src="'.$url.'" alt="" class="js-backdrop-preview-thumb h-10 w-16 cursor-pointer rounded object-cover ring-primary/40 transition hover:ring-2 hover:ring-offset-2" loading="lazy" title="'.e(__('Klik untuk pratonton penuh')).'">';
            })
            ->editColumn('is_active', fn (Backdrop $backdrop) => $backdrop->is_active ? __('Ya') : __('Tidak'))
            ->addColumn('action', fn (Backdrop $backdrop) => view('admin::kawalan.backdrop.actions', ['backdrop' => $backdrop])->render())
            ->rawColumns(['preview', 'action'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'backdrop_name' => ['required', 'string', 'max:255', 'unique:backdrops,backdrop_name'],
            'backdrop_file' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:51200'],
        ]);

        $file = $request->file('backdrop_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = hash('sha256', $file->getClientOriginalName().microtime(true)).'.'.$extension;
        $path = $file->storeAs('backdrops', $filename, 'public');

        Backdrop::query()->create([
            'backdrop_name' => $validated['backdrop_name'],
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'is_active' => true,
        ]);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Backdrop $backdrop): JsonResponse
    {
        $validated = $request->validate([
            'backdrop_name' => ['required', 'string', 'max:255', 'unique:backdrops,backdrop_name,'.$backdrop->id],
            'backdrop_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:51200'],
        ]);

        $oldPath = $backdrop->file_path;

        if ($request->hasFile('backdrop_file')) {
            $file = $request->file('backdrop_file');
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = hash('sha256', $file->getClientOriginalName().microtime(true)).'.'.$extension;
            $path = $file->storeAs('backdrops', $filename, 'public');
            $backdrop->file_path = $path;
            $backdrop->file_size = $file->getSize();
            $backdrop->mime_type = $file->getMimeType();
        }

        $backdrop->backdrop_name = $validated['backdrop_name'];
        $backdrop->save();

        if ($request->hasFile('backdrop_file') && $oldPath !== '' && $oldPath !== $backdrop->file_path) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Backdrop $backdrop): JsonResponse
    {
        Storage::disk('public')->delete($backdrop->file_path);
        $backdrop->delete();

        return response()->json(['success' => true]);
    }

    public function toggleActive(Backdrop $backdrop): JsonResponse
    {
        $backdrop->update([
            'is_active' => ! $backdrop->is_active,
        ]);

        return response()->json(['success' => true]);
    }
}
