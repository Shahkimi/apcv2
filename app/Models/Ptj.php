<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_ptj'])]
class Ptj extends Model
{
    use HasFactory;

    protected $table = 'ptjs';

    public function pegawais(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }
}
