<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Bersara;
use App\Models\Jawatan;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Single-column lookup tables that can be bulk-replaced from a sheet (Jawatan, Bersara).
 */
final class LookupDataset
{
    public const JAWATAN = 'jawatan';

    public const BERSARA = 'bersara';

    /**
     * @param  list<string>  $aliases  normalised header names used to auto-map the column
     */
    private function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $table,
        public readonly string $column,
        public readonly string $columnLabel,
        public readonly array $aliases,
        public readonly bool $deletesPegawai,
        public readonly string $sample,
    ) {}

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [self::JAWATAN, self::BERSARA];
    }

    public static function find(string $key): self
    {
        return match ($key) {
            self::JAWATAN => new self(
                key: self::JAWATAN,
                label: 'Jawatan',
                table: 'jawatans',
                column: 'desc_jawatan',
                columnLabel: 'Nama jawatan',
                aliases: ['descjawatan', 'jawatan', 'namajawatan', 'nama', 'desc'],
                deletesPegawai: true,
                sample: 'Pegawai Tadbir',
            ),
            self::BERSARA => new self(
                key: self::BERSARA,
                label: 'Bersara',
                table: 'bersaras',
                column: 'jenis_bersara',
                columnLabel: 'Jenis persaraan',
                aliases: ['jenisbersara', 'bersara', 'jenispersaraan', 'persaraan', 'jenis'],
                deletesPegawai: false,
                sample: 'Wajib',
            ),
            default => throw new InvalidArgumentException("Dataset tidak dikenali: {$key}"),
        };
    }

    public function sessionKey(): string
    {
        return 'admin_lookup_import_'.$this->key;
    }

    public function storageDirectory(): string
    {
        return 'imports/'.$this->key;
    }

    public function existingCount(): int
    {
        return (int) ($this->modelClass())::query()->count();
    }

    /**
     * Pegawai deleted (jawatan: FK cascade) or unlinked (bersara: FK nullOnDelete) by a replace.
     */
    public function pegawaiImpact(): int
    {
        return $this->deletesPegawai
            ? Pegawai::query()->count()
            : Pegawai::query()->whereNotNull('bersara_id')->count();
    }

    /**
     * @return class-string<Model>
     */
    private function modelClass(): string
    {
        return $this->key === self::JAWATAN ? Jawatan::class : Bersara::class;
    }
}
