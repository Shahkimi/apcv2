<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'config'])]
class PresentationProfile extends Model
{
    use HasFactory;

    public const MAX_PROFILES = 10;

    protected $table = 'presentation_profiles';

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }
}
