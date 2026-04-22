<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['backdrop_name', 'file_path', 'file_size', 'mime_type', 'is_active', 'display_order'])]
class Backdrop extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'file_size' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /**
     * Root-relative URL so previews work regardless of APP_URL host/port (e.g. localhost vs 127.0.0.1:8000).
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if (! filled($this->file_path)) {
                return '';
            }

            $path = str_replace('\\', '/', (string) $this->file_path);
            $path = ltrim($path, '/');

            return '/storage/'.$path;
        });
    }
}
