<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Gred;
use App\Models\Jawatan;
use App\Models\Pegawai;
use App\Models\Ptj;
use App\Models\SesiMajlis;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use RuntimeException;

/**
 * Import Pegawai from CSV/TXT (fgetcsv) or Excel .xlsx (PhpSpreadsheet).
 * OFFLINE NOTE: `phpoffice/phpspreadsheet` must be installed via Composer before offline use.
 */
final class DatabaseImportService
{
    public const SESSION_KEY = 'admin_pegawai_sheet_import';

    public const POLICY_ZERO = 'zero';

    public const POLICY_NULL = 'null';

    /** @var list<string> */
    public const PEGAWAI_FILLABLE = [
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
        's_kehadiran',
    ];

    /** @var list<string> */
    public const REQUIRED_MAPPED_FIELDS = [
        'nama',
        'no_kp',
        'ptj_id',
        'jawatan_id',
        'gred_id',
    ];

    /** @var list<string> */
    public const OPTIONAL_POLICY_FIELDS = [
        'sesi_majlis_id',
        'rsvp',
        'no_kerusi',
        'no_sijil',
        'no_meja',
        'no_panggilan_lewat',
        'is_attend',
        'is_late',
        's_kehadiran',
    ];

    public function storeUpload(UploadedFile $file): array
    {
        $relativePath = $file->store('imports/pegawai', 'local');
        $fullPath = Storage::disk('local')->path($relativePath);
        $meta = $this->parseFileMeta($fullPath);

        return [
            'relative_path' => $relativePath,
            'headers' => $meta['headers'],
            'row_count' => $meta['row_count'],
        ];
    }

    /**
     * @return array{headers: list<string>, row_count: int}
     */
    private function parseFileMeta(string $fullPath): array
    {
        return match ($this->importFileExtension($fullPath)) {
            'xlsx' => $this->parseXlsxFileMeta($fullPath),
            'csv', 'txt' => $this->parseCsvFileMeta($fullPath),
            default => throw new InvalidArgumentException('Format fail tidak disokong. Gunakan .csv, .txt atau .xlsx.'),
        };
    }

    private function importFileExtension(string $fullPath): string
    {
        return strtolower((string) pathinfo($fullPath, PATHINFO_EXTENSION));
    }

    /**
     * @return array{headers: list<string>, row_count: int}
     */
    private function parseCsvFileMeta(string $fullPath): array
    {
        $handle = fopen($fullPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Cannot read import file.');
        }

        try {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $headerRow = fgetcsv($handle);
            if ($headerRow === false) {
                throw new InvalidArgumentException('Fail tidak mengandungi header.');
            }

            $headers = array_map(
                static fn (mixed $h): string => is_string($h) ? trim($h) : '',
                $headerRow
            );

            $rowCount = 0;
            while (($row = fgetcsv($handle)) !== false) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }
                $rowCount++;
            }

            return ['headers' => $headers, 'row_count' => $rowCount];
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array{headers: list<string>, row_count: int}
     */
    private function parseXlsxFileMeta(string $fullPath): array
    {
        $spreadsheet = IOFactory::load($fullPath);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $maxColIdx = Coordinate::columnIndexFromString($sheet->getHighestColumn());
            if ($maxColIdx < 1 || $sheet->getHighestRow() < 1) {
                throw new InvalidArgumentException('Fail tidak mengandungi header.');
            }

            $headers = [];
            for ($c = 1; $c <= $maxColIdx; $c++) {
                $coord = Coordinate::stringFromColumnIndex($c).'1';
                $headers[] = $this->cellToImportString($sheet->getCell($coord)->getValue());
            }

            $rowCount = 0;
            $maxRow = $sheet->getHighestRow();
            for ($r = 2; $r <= $maxRow; $r++) {
                $row = [];
                for ($c = 1; $c <= $maxColIdx; $c++) {
                    $coord = Coordinate::stringFromColumnIndex($c).$r;
                    $row[] = $this->cellToImportString($sheet->getCell($coord)->getValue());
                }
                if (! $this->rowIsEmpty($row)) {
                    $rowCount++;
                }
            }

            return ['headers' => $headers, 'row_count' => $rowCount];
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    private function cellToImportString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof RichText) {
            return trim($value->getPlainText());
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_float($value) && floor($value) === $value && abs($value) < 1e15) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, string>  $mapping  model field => CSV header or ''
     * @param  array<string, string>  $emptyPolicy  model field => zero|null
     * @return array{errors: list<string>, preview: list<array<string, mixed>>, rows: list<array<string, mixed>>}
     */
    public function preview(
        string $relativePath,
        array $headers,
        array $mapping,
        array $emptyPolicy,
        int $previewLimit = 100,
    ): array {
        $fullPath = Storage::disk('local')->path($relativePath);
        $dataRows = $this->readDataRows($fullPath);
        $headerIndex = $this->headerIndexMap($headers);

        $built = [];
        $errors = [];

        foreach ($dataRows as $idx => $dataRow) {
            $csvLine = $idx + 2;
            try {
                $built[] = $this->buildPegawaiRow($dataRow, $headerIndex, $mapping, $emptyPolicy, $csvLine);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        $errors = array_merge($errors, $this->duplicateNoKpErrors($built));
        $errors = array_merge($errors, $this->validateRowsAgainstDatabase($built));

        $preview = [];
        foreach (array_slice($built, 0, $previewLimit) as $row) {
            unset($row['_csv_line']);
            $preview[] = $row;
        }

        return [
            'errors' => $errors,
            'preview' => $preview,
            'rows' => $built,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows  from preview()['rows'] with _csv_line
     */
    public function importRows(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        $now = CarbonImmutable::now();
        $chunks = array_chunk($rows, 500);
        $total = 0;

        DB::transaction(function () use ($chunks, $now, &$total): void {
            foreach ($chunks as $chunk) {
                $payload = [];
                foreach ($chunk as $row) {
                    unset($row['_csv_line']);
                    $row['created_at'] = $now;
                    $row['updated_at'] = $now;
                    $payload[] = $row;
                }
                DB::table('pegawais')->insert($payload);
                $total += count($payload);
            }
        });

        return $total;
    }

    /**
     * @param  list<string>  $headers
     * @param  array<string, string>  $mapping
     * @param  array<string, string>  $emptyPolicy
     * @return array{ok: true, imported: int}|array{ok: false, errors: list<string>, preview: list<array<string, mixed>>, rows: list<array<string, mixed>>}
     */
    public function commitImport(
        string $relativePath,
        array $headers,
        array $mapping,
        array $emptyPolicy,
    ): array {
        $result = $this->preview($relativePath, $headers, $mapping, $emptyPolicy);
        if ($result['errors'] !== []) {
            return ['ok' => false, ...$result];
        }

        $imported = $this->importRows($result['rows']);

        return ['ok' => true, 'imported' => $imported];
    }

    public function deleteStoredFile(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        if (Storage::disk('local')->exists($relativePath)) {
            Storage::disk('local')->delete($relativePath);
        }
    }

    /**
     * @param  list<array<int, string|null>>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<array<int, string|null>>
     */
    private function readDataRows(string $fullPath): array
    {
        return match ($this->importFileExtension($fullPath)) {
            'xlsx' => $this->readXlsxDataRows($fullPath),
            'csv', 'txt' => $this->readCsvDataRows($fullPath),
            default => throw new InvalidArgumentException('Format fail tidak disokong. Gunakan .csv, .txt atau .xlsx.'),
        };
    }

    /**
     * @return list<array<int, string|null>>
     */
    private function readCsvDataRows(string $fullPath): array
    {
        $handle = fopen($fullPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Cannot read import file.');
        }

        $rows = [];
        try {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            if (fgetcsv($handle) === false) {
                return [];
            }

            while (($row = fgetcsv($handle)) !== false) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * @return list<array<int, string|null>>
     */
    private function readXlsxDataRows(string $fullPath): array
    {
        $spreadsheet = IOFactory::load($fullPath);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $maxColIdx = Coordinate::columnIndexFromString($sheet->getHighestColumn());
            $maxRow = $sheet->getHighestRow();
            if ($maxRow < 2 || $maxColIdx < 1) {
                return [];
            }

            $rows = [];
            for ($r = 2; $r <= $maxRow; $r++) {
                $row = [];
                for ($c = 1; $c <= $maxColIdx; $c++) {
                    $coord = Coordinate::stringFromColumnIndex($c).$r;
                    $row[] = $this->cellToImportString($sheet->getCell($coord)->getValue());
                }
                if (! $this->rowIsEmpty($row)) {
                    $rows[] = $row;
                }
            }

            return $rows;
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, int>
     */
    private function headerIndexMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $i => $name) {
            if ($name !== '' && ! array_key_exists($name, $map)) {
                $map[$name] = $i;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, string|null>  $dataRow
     * @param  array<string, int>  $headerIndex
     * @param  array<string, string>  $mapping
     * @param  array<string, string>  $emptyPolicy
     * @return array<string, mixed>
     */
    private function buildPegawaiRow(
        array $dataRow,
        array $headerIndex,
        array $mapping,
        array $emptyPolicy,
        int $csvLine,
    ): array {
        $out = ['_csv_line' => $csvLine];

        foreach (self::PEGAWAI_FILLABLE as $field) {
            $csvHeader = $mapping[$field] ?? '';
            $raw = '';
            if ($csvHeader !== '' && isset($headerIndex[$csvHeader])) {
                $idx = $headerIndex[$csvHeader];
                $cell = $dataRow[$idx] ?? null;
                $raw = is_string($cell) ? trim($cell) : trim((string) $cell);
            }

            $isEmpty = $csvHeader === '' || $raw === '';
            $policy = $emptyPolicy[$field] ?? self::POLICY_ZERO;

            if (in_array($field, self::REQUIRED_MAPPED_FIELDS, true)) {
                if ($csvHeader === '') {
                    throw new InvalidArgumentException("Baris {$csvLine}: medan '{$field}' mesti dipetakan ke lajur sumber.");
                }
                if ($raw === '') {
                    throw new InvalidArgumentException("Baris {$csvLine}: '{$field}' diperlukan.");
                }
                $out[$field] = $this->castRequiredField($field, $raw, $csvLine);
            } else {
                $out[$field] = $this->castOptionalField($field, $raw, $isEmpty, $policy, $csvLine);
            }
        }

        return $out;
    }

    private function castRequiredField(string $field, string $raw, int $csvLine): mixed
    {
        return match ($field) {
            'nama', 'no_kp' => $raw,
            'ptj_id', 'jawatan_id', 'gred_id' => $this->parseRequiredId($raw, $field, $csvLine),
            default => throw new InvalidArgumentException("Baris {$csvLine}: medan tidak dijangka '{$field}'."),
        };
    }

    private function castOptionalField(
        string $field,
        string $raw,
        bool $isEmpty,
        string $policy,
        int $csvLine,
    ): mixed {
        if (! $isEmpty) {
            return match ($field) {
                'sesi_majlis_id' => $this->parseOptionalFkId($raw, 'sesi_majlis_id', $csvLine),
                'rsvp', 'is_attend', 'is_late' => $this->parseBoolean($raw, $csvLine, $field),
                'no_kerusi', 'no_sijil', 'no_meja', 'no_panggilan_lewat' => $this->parseOptionalUInt($raw, $field, $csvLine),
                's_kehadiran' => $this->parseSKehadiran($raw, $csvLine),
                default => null,
            };
        }

        return match ($field) {
            // Nullable FK: never store 0 (tiada baris id=0); kosong sentiasa NULL.
            'sesi_majlis_id' => null,
            // NOT NULL boolean defaults — pilihan 0/null pada UI tidak ubah storan (kedua-dua false).
            'rsvp', 'is_attend', 'is_late' => false,
            'no_kerusi', 'no_sijil', 'no_meja', 'no_panggilan_lewat' => $policy === self::POLICY_NULL ? null : 0,
            's_kehadiran' => 0,
            default => null,
        };
    }

    private function parseRequiredId(string $raw, string $field, int $csvLine): int
    {
        if (! ctype_digit($raw)) {
            throw new InvalidArgumentException("Baris {$csvLine}: '{$field}' mesti integer positif.");
        }

        $id = (int) $raw;
        if ($id < 1) {
            throw new InvalidArgumentException("Baris {$csvLine}: '{$field}' mesti lebih besar daripada 0.");
        }

        return $id;
    }

    private function parseOptionalFkId(string $raw, string $field, int $csvLine): ?int
    {
        if ($raw === '') {
            return null;
        }
        if (! ctype_digit($raw)) {
            throw new InvalidArgumentException("Baris {$csvLine}: '{$field}' mesti integer atau kosong.");
        }

        $id = (int) $raw;

        return $id === 0 ? null : $id;
    }

    private function parseOptionalUInt(string $raw, string $field, int $csvLine): ?int
    {
        if (! ctype_digit($raw)) {
            throw new InvalidArgumentException("Baris {$csvLine}: '{$field}' mesti nombor tidak negatif.");
        }

        return (int) $raw;
    }

    private function parseBoolean(string $raw, int $csvLine, string $field): bool
    {
        $l = strtolower($raw);
        if (in_array($l, ['1', 'true', 'yes', 'ya', 'y'], true)) {
            return true;
        }
        if (in_array($l, ['0', 'false', 'no', 'tidak', 'n', ''], true)) {
            return false;
        }

        throw new InvalidArgumentException("Baris {$csvLine}: '{$field}' nilai boolean tidak sah (gunakan 0/1).");
    }

    private function parseSKehadiran(string $raw, int $csvLine): int
    {
        if (! ctype_digit($raw)) {
            throw new InvalidArgumentException("Baris {$csvLine}: 's_kehadiran' mesti 0 atau 1.");
        }

        $v = (int) $raw;
        if ($v !== Pegawai::S_KEHADIRAN_PAGI && $v !== Pegawai::S_KEHADIRAN_PETANG) {
            throw new InvalidArgumentException("Baris {$csvLine}: 's_kehadiran' mesti 0 (pagi) atau 1 (petang).");
        }

        return $v;
    }

    /**
     * @param  list<array<string, mixed>>  $built
     * @return list<string>
     */
    private function duplicateNoKpErrors(array $built): array
    {
        $seen = [];
        $errors = [];
        foreach ($built as $row) {
            $kp = $row['no_kp'] ?? '';
            if (! is_string($kp) || $kp === '') {
                continue;
            }
            if (isset($seen[$kp])) {
                $line = $row['_csv_line'] ?? '?';
                $errors[] = "Baris {$line}: no_kp '{$kp}' pendua (pertama pada baris {$seen[$kp]}).";
            } else {
                $seen[$kp] = $row['_csv_line'] ?? '?';
            }
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $built
     * @return list<string>
     */
    private function validateRowsAgainstDatabase(array $built): array
    {
        $errors = [];

        $ptjIds = collect($built)->pluck('ptj_id')->unique()->filter()->all();
        $jawatanIds = collect($built)->pluck('jawatan_id')->unique()->filter()->all();
        $gredIds = collect($built)->pluck('gred_id')->unique()->filter()->all();
        $sesiIds = collect($built)->pluck('sesi_majlis_id')->unique()->filter()->all();

        $existingPtj = Ptj::query()->whereIn('id', $ptjIds)->pluck('id')->all();
        $existingJawatan = Jawatan::query()->whereIn('id', $jawatanIds)->pluck('id')->all();
        $existingGred = Gred::query()->whereIn('id', $gredIds)->pluck('id')->all();
        $existingSesi = $sesiIds === []
            ? []
            : SesiMajlis::query()->whereIn('id', $sesiIds)->pluck('id')->all();

        $existingPtj = array_flip($existingPtj);
        $existingJawatan = array_flip($existingJawatan);
        $existingGred = array_flip($existingGred);
        $existingSesi = array_flip($existingSesi);

        $kps = collect($built)->pluck('no_kp')->unique()->filter()->all();
        $existingKp = Pegawai::query()->whereIn('no_kp', $kps)->pluck('no_kp')->all();
        $existingKp = array_flip($existingKp);

        foreach ($built as $row) {
            $line = $row['_csv_line'] ?? '?';
            $ptjId = $row['ptj_id'] ?? null;
            if (is_int($ptjId) && ! isset($existingPtj[$ptjId])) {
                $errors[] = "Baris {$line}: ptj_id '{$ptjId}' tidak wujud.";
            }
            $jId = $row['jawatan_id'] ?? null;
            if (is_int($jId) && ! isset($existingJawatan[$jId])) {
                $errors[] = "Baris {$line}: jawatan_id '{$jId}' tidak wujud.";
            }
            $gId = $row['gred_id'] ?? null;
            if (is_int($gId) && ! isset($existingGred[$gId])) {
                $errors[] = "Baris {$line}: gred_id '{$gId}' tidak wujud.";
            }
            $sId = $row['sesi_majlis_id'] ?? null;
            if ($sId !== null && is_int($sId) && ! isset($existingSesi[$sId])) {
                $errors[] = "Baris {$line}: sesi_majlis_id '{$sId}' tidak wujud.";
            }
            $kp = $row['no_kp'] ?? '';
            if (is_string($kp) && $kp !== '' && isset($existingKp[$kp])) {
                $errors[] = "Baris {$line}: no_kp '{$kp}' sudah wujud dalam pangkalan data.";
            }
        }

        return $errors;
    }
}
