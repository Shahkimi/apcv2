<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncedOfficer extends Model
{
    public $timestamps = false;

    protected $table = 'senarai_announced_officers';

    protected $fillable = ['scope_key', 'sesi_majlis_id', 'pegawai_id', 'announced_at'];

    protected function casts(): array
    {
        return [
            'announced_at' => 'datetime',
            'pegawai_id' => 'integer',
            'sesi_majlis_id' => 'integer',
        ];
    }
}
