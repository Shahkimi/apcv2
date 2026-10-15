<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Font uploaded by media for the Layar Utama slideshow. The file lives on the
 * private disk and is streamed through media.fonts.file, so it works without
 * storage:link and under a sub-path.
 */
#[Fillable(['name', 'file_path', 'format', 'file_size'])]
class PresentationFont extends Model
{
    public const MAX_FONTS = 30;

    /** Max upload size in KB (10 MB). */
    public const MAX_FILE_KB = 10240;

    public const DISK = 'local';

    /**
     * CSS @font-face format() keyword => [extension, Content-Type].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const FORMATS = [
        'woff2' => ['woff2', 'font/woff2'],
        'woff' => ['woff', 'font/woff'],
        'truetype' => ['ttf', 'font/ttf'],
        'opentype' => ['otf', 'font/otf'],
    ];

    protected $table = 'presentation_fonts';

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /**
     * Generated CSS family name; the user-entered name never goes into CSS.
     */
    public function cssFamily(): string
    {
        return 'apcfont-'.$this->id;
    }

    /**
     * Root-relative URL (keeps the request base path), versioned so a replaced file busts the cache.
     */
    public function url(): string
    {
        return route('media.fonts.file', [
            'presentationFont' => $this->id,
            'v' => $this->updated_at?->getTimestamp() ?? 0,
        ], false);
    }

    public function formatLabel(): string
    {
        return strtoupper(self::FORMATS[$this->format][0] ?? $this->format);
    }

    public function contentType(): string
    {
        return self::FORMATS[$this->format][1] ?? 'application/octet-stream';
    }
}
