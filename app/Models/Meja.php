<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['sizing'])]
class Meja extends Model
{
    use HasFactory;

    protected $table = 'mejas';

    protected function casts(): array
    {
        return [
            'sizing' => 'integer',
        ];
    }
}
