<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['desc_gred'])]
class Gred extends Model
{
    use HasFactory;

    protected $table = 'greds';

    public function pegawais(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }
}
