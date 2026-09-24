<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nama',
    'no_kp',
    'ptj_id',
    'jawatan_id',
    'gred_id',
    'sesi_majlis_id',
    'rsvp',
    'no_kerusi',
    'no_sijil',
    'no_meja',
    'no_panggilan_lewat',
    'is_attend',
    'is_late',
    'hadir_at',
    's_kehadiran',
    'tarikh_bersara',
    'bersara_id',
    'tempoh_berkhidmat',
])]
class Pegawai extends Model
{
    use HasFactory;

    public const S_KEHADIRAN_PAGI = 0;

    public const S_KEHADIRAN_PETANG = 1;

    protected $table = 'pegawais';

    protected function casts(): array
    {
        return [
            'rsvp' => 'boolean',
            'is_attend' => 'boolean',
            'is_late' => 'boolean',
            'hadir_at' => 'datetime',
            'no_kerusi' => 'integer',
            'no_sijil' => 'integer',
            'no_meja' => 'integer',
            'no_panggilan_lewat' => 'integer',
            's_kehadiran' => 'integer',
            'tarikh_bersara' => 'date',
            'tempoh_berkhidmat' => 'integer',
        ];
    }

    public function ptj(): BelongsTo
    {
        return $this->belongsTo(Ptj::class);
    }

    public function jawatan(): BelongsTo
    {
        return $this->belongsTo(Jawatan::class);
    }

    public function gred(): BelongsTo
    {
        return $this->belongsTo(Gred::class);
    }

    public function sesiMajlis(): BelongsTo
    {
        return $this->belongsTo(SesiMajlis::class);
    }

    public function bersara(): BelongsTo
    {
        return $this->belongsTo(Bersara::class);
    }
}
