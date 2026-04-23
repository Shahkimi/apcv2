<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['scope_key', 'sesi_majlis_id', 'current_index', 'announced_officers', 'last_updated_at'])]
class SenaraiProgress extends Model
{
    use HasFactory;

    protected $table = 'senarai_progress';

    protected function casts(): array
    {
        return [
            'sesi_majlis_id' => 'integer',
            'current_index' => 'integer',
            'announced_officers' => 'array',
            'last_updated_at' => 'datetime',
        ];
    }

    public function sesiMajlis(): BelongsTo
    {
        return $this->belongsTo(SesiMajlis::class);
    }
}
