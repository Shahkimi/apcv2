<?php

declare(strict_types=1);

namespace App\Services\Import;

use DateTimeInterface;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Reads uploaded CSV/TXT (fgetcsv) or Excel .xlsx (PhpSpreadsheet) import files.
 */
final class SheetReader
{
    /**
     * @return array{headers: list<string>, row_count: int}
     */
    public function meta(string $fullPath): array
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
                $headers[] = $this->cellToImportString($sheet->getCell($coord));
            }

            $rowCount = 0;
            $maxRow = $sheet->getHighestRow();
            for ($r = 2; $r <= $maxRow; $r++) {
                $row = [];
                for ($c = 1; $c <= $maxColIdx; $c++) {
                    $coord = Coordinate::stringFromColumnIndex($c).$r;
                    $row[] = $this->cellToImportString($sheet->getCell($coord));
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

    private function cellToImportString(Cell $cell): string
    {
        $value = $cell->getValue();

        if ($value === null) {
            return '';
        }

        if ($value instanceof RichText) {
            return trim($value->getPlainText());
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ((is_int($value) || is_float($value)) && ExcelDate::isDateTime($cell)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
        }

        if (is_float($value) && floor($value) === $value && abs($value) < 1e15) {
            return (string) (int) $value;
        }

        return trim((string) $value);
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
    public function dataRows(string $fullPath): array
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
                    $row[] = $this->cellToImportString($sheet->getCell($coord));
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
    public function headerIndexMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $i => $name) {
            if ($name !== '' && ! array_key_exists($name, $map)) {
                $map[$name] = $i;
            }
        }

        return $map;
    }
}
