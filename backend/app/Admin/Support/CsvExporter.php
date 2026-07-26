<?php

declare(strict_types=1);

namespace App\Admin\Support;

/**
 * CSV exporter for admin data tables.
 *
 * Produces RFC 4180 output with a header row. Values are stringified and any
 * value beginning with a formula trigger is prefixed with a single quote to
 * neutralise CSV/formula injection in spreadsheet apps.
 */
final class CsvExporter
{
    /**
     * @param array<int, string>                 $headers Column headers.
     * @param array<int, array<int|string, mixed>> $rows   Row data (aligned to headers or assoc).
     * @param array<int, string>                 $keys    Optional assoc keys to pull per row, in order.
     */
    public function toCsv(array $headers, array $rows, array $keys = []): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }

        fputcsv($handle, array_map([$this, 'sanitize'], $headers), ',', '"', '');

        foreach ($rows as $row) {
            $line = $keys === [] ? array_values($row) : array_map(static fn(string $k): mixed => $row[$k] ?? '', $keys);
            fputcsv($handle, array_map([$this, 'sanitize'], $line), ',', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }

    /**
     * Stringify a cell and defuse formula-injection prefixes.
     */
    private function sanitize(mixed $value): string
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        $string = is_scalar($value) || $value === null ? (string) $value : (string) json_encode($value);

        if ($string !== '' && in_array($string[0], ['=', '+', '-', '@'], true)) {
            $string = "'" . $string;
        }

        return $string;
    }
}
