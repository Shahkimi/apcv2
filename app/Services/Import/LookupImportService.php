<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Support\IdCounter;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Replaces a whole lookup table (Jawatan / Bersara) from CSV/XLSX.
 * Ids come from the optional id column (datasets with importsIds), otherwise 1..N in file order.
 */
final class LookupImportService
{
    private const MAX_LENGTH = 255;

    public function __construct(private readonly SheetReader $reader) {}

    /**
     * @return array{relative_path: string, headers: list<string>, row_count: int}
     */
    public function storeUpload(UploadedFile $file, LookupDataset $dataset): array
    {
        $relativePath = $file->store($dataset->storageDirectory(), 'local');
        $meta = $this->reader->meta(Storage::disk('local')->path($relativePath));

        return [
            'relative_path' => $relativePath,
            'headers' => $meta['headers'],
            'row_count' => $meta['row_count'],
        ];
    }

    /**
     * @param  list<string>  $headers
     * @return array{errors: list<string>, preview: list<array{id: int, name: string}>, rows: list<array{id: int, name: string}>, impact: array<string, mixed>}
     */
    public function preview(string $relativePath, array $headers, string $header, LookupDataset $dataset, int $previewLimit = 100, ?string $idHeader = null): array
    {
        $dataRows = $this->reader->dataRows(Storage::disk('local')->path($relativePath));
        $indexMap = $this->reader->headerIndexMap($headers);
        $index = $indexMap[$header] ?? null;
        $idIndex = $idHeader !== null ? ($indexMap[$idHeader] ?? null) : null;

        $errors = [];
        $rows = [];
        $seen = [];
        $seenIds = [];

        if ($index === null) {
            $errors[] = "Lajur '{$header}' tidak wujud dalam fail.";
        } elseif ($idHeader !== null && $idIndex === null) {
            $errors[] = "Lajur '{$idHeader}' tidak wujud dalam fail.";
        } else {
            foreach ($dataRows as $i => $dataRow) {
                $line = $i + 2;
                $name = trim((string) ($dataRow[$index] ?? ''));

                if ($name === '') {
                    $errors[] = "Baris {$line}: '{$dataset->column}' diperlukan.";

                    continue;
                }
                if (mb_strlen($name) > self::MAX_LENGTH) {
                    $errors[] = "Baris {$line}: '{$dataset->column}' melebihi ".self::MAX_LENGTH.' aksara.';

                    continue;
                }

                $key = mb_strtolower($name);
                if (isset($seen[$key])) {
                    $errors[] = "Baris {$line}: '{$name}' pendua (pertama pada baris {$seen[$key]}).";

                    continue;
                }
                $seen[$key] = $line;

                if ($idIndex === null) {
                    $rows[] = ['id' => count($rows) + 1, 'name' => $name];

                    continue;
                }

                $rawId = trim((string) ($dataRow[$idIndex] ?? ''));
                $id = $this->parseId($rawId);
                if ($rawId === '') {
                    $errors[] = "Baris {$line}: '".LookupDataset::ID_FIELD."' diperlukan.";

                    continue;
                }
                if ($id === null) {
                    $errors[] = "Baris {$line}: id '{$rawId}' mesti integer positif.";

                    continue;
                }
                if (isset($seenIds[$id])) {
                    $errors[] = "Baris {$line}: id '{$rawId}' pendua (pertama pada baris {$seenIds[$id]}).";

                    continue;
                }
                $seenIds[$id] = $line;

                $rows[] = ['id' => $id, 'name' => $name];
            }

            if ($rows === [] && $errors === []) {
                $errors[] = 'Fail tidak mengandungi baris data.';
            }
        }

        return [
            'errors' => $errors,
            'preview' => array_slice($rows, 0, $previewLimit),
            'rows' => $rows,
            'impact' => $this->impact($dataset),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function impact(LookupDataset $dataset): array
    {
        return [
            'dataset' => $dataset->key,
            'label' => $dataset->label,
            'existing' => $dataset->existingCount(),
            'pegawai' => $dataset->pegawaiImpact(),
            'deletes_pegawai' => $dataset->deletesPegawai,
        ];
    }

    /**
     * Validates, then replaces the table inside one transaction.
     *
     * @param  list<string>  $headers
     * @return array{ok: true, imported: int, replaced: int, pegawai: int, deletes_pegawai: bool}|array{ok: false, errors: list<string>, preview: list<array{id: int, name: string}>, impact: array<string, mixed>}
     */
    public function commitImport(string $relativePath, array $headers, string $header, LookupDataset $dataset, ?string $idHeader = null): array
    {
        $result = $this->preview($relativePath, $headers, $header, $dataset, idHeader: $idHeader);
        if ($result['errors'] !== []) {
            return ['ok' => false, 'errors' => $result['errors'], 'preview' => $result['preview'], 'impact' => $result['impact']];
        }

        $impact = $result['impact'];
        $rows = $result['rows'];
        $now = CarbonImmutable::now();

        DB::transaction(function () use ($dataset, $rows, $now): void {
            // FKs do the rest: jawatan -> pegawai cascade, bersara -> pegawai.bersara_id set null.
            DB::table($dataset->table)->delete();

            if ($dataset->deletesPegawai) {
                // senarai_announced_officers.pegawai_id has no FK; clear it with the pegawai it points to.
                DB::table('senarai_announced_officers')->delete();
                DB::table('senarai_progress')->update(['current_index' => 0, 'announced_officers' => null]);
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table($dataset->table)->insert(array_map(
                    static fn (array $row): array => [
                        'id' => $row['id'],
                        $dataset->column => $row['name'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $chunk
                ));
            }
        });

        // Next id after the import is max(id) + 1 (N + 1 when ids were assigned 1..N).
        IdCounter::realign($dataset->table, $rows === [] ? 0 : max(array_column($rows, 'id')));

        return [
            'ok' => true,
            'imported' => count($rows),
            'replaced' => (int) $impact['existing'],
            'pegawai' => (int) $impact['pegawai'],
            'deletes_pegawai' => (bool) $impact['deletes_pegawai'],
        ];
    }

    /**
     * Positive integer id from a sheet cell (XLSX whole numbers already arrive as digit strings).
     */
    private function parseId(string $raw): ?int
    {
        if ($raw === '' || ! ctype_digit($raw) || strlen(ltrim($raw, '0')) > 18) {
            return null;
        }
        $id = (int) $raw;

        return $id >= 1 ? $id : null;
    }

    public function deleteStoredFile(?string $relativePath): void
    {
        if ($relativePath !== null && $relativePath !== '' && Storage::disk('local')->exists($relativePath)) {
            Storage::disk('local')->delete($relativePath);
        }
    }
}
