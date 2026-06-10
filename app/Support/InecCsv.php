<?php

namespace App\Support;

use RuntimeException;
use SplFileObject;

class InecCsv
{
    public static function readRows(string $path): iterable
    {
        if (! is_file($path)) {
            throw new RuntimeException("CSV file not found: {$path}");
        }

        $file = fopen($path, 'rb');

        $headers = null;
        $rowNumber = 0;

        while (($row = fgetcsv($file, 0, ',', '"', '')) !== false) {
            $rowNumber++;

            if ($row === [null] || $row === false) {
                continue;
            }

            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);

            if ($headers === null) {
                $headers = array_map(fn ($header) => trim((string) $header), $row);
                continue;
            }

            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), null);
            }

            $mapped = array_combine($headers, array_slice($row, 0, count($headers)));

            if ($mapped !== false) {
                $mapped['__row_number'] = $rowNumber;

                yield $mapped;
            }
        }

        fclose($file);
    }

    public static function writeRows(string $path, array $headers, array $rows): int
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $file = new SplFileObject($path, 'w');
        $file->fputcsv($headers);

        foreach ($rows as $row) {
            $file->fputcsv(array_map(fn ($header) => $row[$header] ?? '', $headers));
        }

        return count($rows);
    }
}
