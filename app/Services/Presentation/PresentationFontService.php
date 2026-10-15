<?php

declare(strict_types=1);

namespace App\Services\Presentation;

use App\Models\PresentationFont;
use App\Models\PresentationProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Font library for the slideshow: upload (with file signature check), rename,
 * replace, delete (scrubbing the font out of the live config and profiles) and
 * the @font-face CSS for pages that render the fonts.
 */
final class PresentationFontService
{
    public function __construct(
        private readonly PresentationDisplayConfigService $displayConfig,
    ) {}

    public function create(string $name, UploadedFile $file): PresentationFont
    {
        $format = $this->detectFormat($file);
        $path = $this->storeFile($file, $format);

        $font = PresentationFont::query()->create([
            'name' => $name,
            'file_path' => $path,
            'format' => $format,
            'file_size' => $file->getSize(),
        ]);

        $this->displayConfig->forgetFontIds();

        return $font;
    }

    public function update(PresentationFont $font, string $name, ?UploadedFile $file): PresentationFont
    {
        $oldPath = null;

        if ($file !== null) {
            $format = $this->detectFormat($file);
            $oldPath = $font->file_path;
            $font->file_path = $this->storeFile($file, $format);
            $font->format = $format;
            $font->file_size = $file->getSize();
        }

        $font->name = $name;
        $font->save();

        if ($oldPath !== null && $oldPath !== $font->file_path) {
            Storage::disk(PresentationFont::DISK)->delete($oldPath);
        }

        return $font;
    }

    /**
     * Delete the font; slide parts that used it (live and in profiles) fall back to the default font.
     */
    public function delete(PresentationFont $font): void
    {
        $fontId = (int) $font->id;
        $path = $font->file_path;

        DB::transaction(function () use ($font, $fontId): void {
            $font->delete();
            $this->displayConfig->forgetFontIds();

            // resolve() drops ids that no longer exist, so re-saving scrubs the reference.
            $live = $this->displayConfig->live();
            $this->displayConfig->saveLive($live);

            PresentationProfile::query()->get()->each(function (PresentationProfile $profile) use ($fontId): void {
                $typography = is_array($profile->config['typography'] ?? null) ? $profile->config['typography'] : [];
                $usesFont = collect($typography)->contains(
                    fn ($style) => is_array($style) && (int) ($style['font_id'] ?? 0) === $fontId,
                );

                if ($usesFont) {
                    $profile->config = $this->displayConfig->resolve($profile->config);
                    $profile->save();
                }
            });
        });

        Storage::disk(PresentationFont::DISK)->delete($path);
    }

    /**
     * Where a font is used: slide parts of the live config and profile names.
     *
     * @return array{parts: list<string>, profiles: list<string>}
     */
    public function usage(PresentationFont $font): array
    {
        $fontId = (int) $font->id;
        $partLabels = $this->displayConfig->typographyParts();

        $parts = array_map(
            static fn (string $part) => $partLabels[$part]['label'],
            $this->displayConfig->partsUsingFont($this->displayConfig->live(), $fontId),
        );

        $profiles = PresentationProfile::query()->orderBy('name')->get()
            ->filter(fn (PresentationProfile $profile) => $this->displayConfig->partsUsingFont(
                $this->displayConfig->resolve($profile->config),
                $fontId,
            ) !== [])
            ->pluck('name')
            ->values()
            ->all();

        return ['parts' => $parts, 'profiles' => $profiles];
    }

    /**
     * @param  Collection<int, PresentationFont>  $fonts
     */
    public function fontFaceCss(Collection $fonts): string
    {
        return $fonts->map(fn (PresentationFont $font) => sprintf(
            '@font-face{font-family:"%s";src:url("%s") format("%s");font-display:swap;}',
            $font->cssFamily(),
            str_replace(['"', '\\', "\n", "\r"], '', $font->url()),
            $font->format,
        ))->implode("\n");
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, PresentationFont>
     */
    public function fontsByIds(array $ids): Collection
    {
        return $ids === [] ? collect() : PresentationFont::query()->whereKey($ids)->get();
    }

    /**
     * Font format from the file signature (not the client extension).
     *
     * @throws ValidationException
     */
    private function detectFormat(UploadedFile $file): string
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $magic = $handle === false ? '' : (string) fread($handle, 4);
        if ($handle !== false) {
            fclose($handle);
        }

        $format = match (true) {
            $magic === 'wOF2' => 'woff2',
            $magic === 'wOFF' => 'woff',
            $magic === "\x00\x01\x00\x00", $magic === 'true' => 'truetype',
            $magic === 'OTTO' => 'opentype',
            default => null,
        };

        if ($format === null) {
            throw ValidationException::withMessages([
                'font_file' => __('Fail ini bukan fon yang sah. Gunakan fail TTF, OTF, WOFF atau WOFF2.'),
            ]);
        }

        return $format;
    }

    private function storeFile(UploadedFile $file, string $format): string
    {
        $extension = PresentationFont::FORMATS[$format][0];
        $filename = hash('sha256', $file->getClientOriginalName().microtime(true).random_bytes(8)).'.'.$extension;

        return $file->storeAs('fonts', $filename, PresentationFont::DISK);
    }
}
