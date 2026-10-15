<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Kawalan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\Kawalan\PresentationFontRequest;
use App\Models\PresentationFont;
use App\Services\Presentation\PresentationFontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PresentationFontController extends Controller
{
    public function __construct(
        private readonly PresentationFontService $fonts,
    ) {}

    public function index(): View
    {
        $fonts = PresentationFont::query()->orderBy('name')->get();

        return view('media::kawalan.fon.index', [
            'fontsPayload' => $this->payload($fonts),
            'fontFaceCss' => $this->fonts->fontFaceCss($fonts),
            'maxFonts' => PresentationFont::MAX_FONTS,
            'maxFileKb' => PresentationFont::MAX_FILE_KB,
        ]);
    }

    public function list(): JsonResponse
    {
        $fonts = PresentationFont::query()->orderBy('name')->get();

        return response()->json([
            'fonts' => $this->payload($fonts),
            'font_face_css' => $this->fonts->fontFaceCss($fonts),
        ]);
    }

    public function store(PresentationFontRequest $request): JsonResponse
    {
        if (PresentationFont::query()->count() >= PresentationFont::MAX_FONTS) {
            return response()->json([
                'message' => __('Had :max fon dicapai. Padam fon yang tidak digunakan dahulu.', ['max' => PresentationFont::MAX_FONTS]),
                'errors' => ['font_file' => [__('Had :max fon dicapai.', ['max' => PresentationFont::MAX_FONTS])]],
            ], 422);
        }

        $font = $this->fonts->create($request->string('name')->trim()->value(), $request->file('font_file'));

        return response()->json([
            'success' => true,
            'message' => __('Fon ":name" dimuat naik.', ['name' => $font->name]),
            'font' => $this->fontPayload($font),
        ]);
    }

    public function update(PresentationFontRequest $request, PresentationFont $presentationFont): JsonResponse
    {
        $font = $this->fonts->update(
            $presentationFont,
            $request->string('name')->trim()->value(),
            $request->file('font_file'),
        );

        return response()->json([
            'success' => true,
            'message' => __('Fon dikemas kini.'),
            'font' => $this->fontPayload($font),
        ]);
    }

    public function destroy(PresentationFont $presentationFont): JsonResponse
    {
        $this->fonts->delete($presentationFont);

        return response()->json(['success' => true, 'message' => __('Fon dipadam.')]);
    }

    /**
     * Streams the font file; URLs are versioned (?v=updated_at) so caching can be long.
     */
    public function file(PresentationFont $presentationFont): StreamedResponse
    {
        $disk = Storage::disk(PresentationFont::DISK);
        abort_unless($disk->exists($presentationFont->file_path), 404);

        return $disk->response($presentationFont->file_path, $presentationFont->cssFamily().'.'.PresentationFont::FORMATS[$presentationFont->format][0], [
            'Content-Type' => $presentationFont->contentType(),
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @param  Collection<int, PresentationFont>  $fonts
     * @return list<array<string, mixed>>
     */
    private function payload($fonts): array
    {
        return $fonts->map(fn (PresentationFont $font) => $this->fontPayload($font))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function fontPayload(PresentationFont $font): array
    {
        return [
            'id' => $font->id,
            'name' => $font->name,
            'family' => $font->cssFamily(),
            'url' => $font->url(),
            'format' => $font->format,
            'format_label' => $font->formatLabel(),
            'size' => (int) $font->file_size,
            'updated_human' => $font->updated_at?->diffForHumans() ?? '',
            'usage' => $this->fonts->usage($font),
        ];
    }
}
