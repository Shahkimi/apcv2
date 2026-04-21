<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['sesi', 'is_active', 'is_on_air', 'is_late', 'countdown_start'])]
class SesiMajlis extends Model
{
    use HasFactory;

    protected $table = 'sesi_majlis';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_on_air' => 'boolean',
            'is_late' => 'boolean',
            'countdown_start' => 'integer',
        ];
    }
}
