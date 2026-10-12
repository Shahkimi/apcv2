<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Bersara;
use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Single-column lookup tables that can be bulk-replaced from a sheet (Jawatan, Gred, Bersara).
 */
final class LookupDataset
{
    public const JAWATAN = 'jawatan';

    public const GRED = 'gred';

    public const BERSARA = 'bersara';

    /** Mapping key of the optional id column (datasets with $importsIds). */
    public const ID_FIELD = 'id';

    /**
     * @param  list<string>  $aliases  normalised header names used to auto-map the column
     * @param  bool  $importsIds  ids may come from an id column in the sheet (pegawai import references them)
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
        public readonly bool $importsIds = false,
    ) {}

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [self::JAWATAN, self::GRED, self::BERSARA];
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
                importsIds: true,
            ),
            self::GRED => new self(
                key: self::GRED,
                label: 'Gred',
                table: 'greds',
                column: 'desc_gred',
                columnLabel: 'Nama gred',
                aliases: ['descgred', 'gred', 'namagred', 'nama', 'desc'],
                deletesPegawai: true,
                sample: 'N41',
                importsIds: true,
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
     * Pegawai deleted (jawatan/gred: FK cascade) or unlinked (bersara: FK nullOnDelete) by a replace.
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
        return match ($this->key) {
            self::JAWATAN => Jawatan::class,
            self::GRED => Gred::class,
            default => Bersara::class,
        };
    }
}
