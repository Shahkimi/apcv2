<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sesi', 'is_active', 'is_late', 'countdown_start_late', 'seat_offset', 's_kehadiran'])]
class SesiMajlis extends Model
{
    use HasFactory;

    public const S_KEHADIRAN_PAGI = 0;

    public const S_KEHADIRAN_PETANG = 1;

    protected $table = 'sesi_majlis';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_late' => 'boolean',
            'countdown_start_late' => 'integer',
            'seat_offset' => 'integer',
            's_kehadiran' => 'integer',
        ];
    }

    public function pegawais(): HasMany
    {
        return $this->hasMany(Pegawai::class);
    }
}
