<?php

declare(strict_types=1);

namespace App\Core;

final class Csv
{
    /**
     * CSV download. Cells starting with = + - @ get a leading apostrophe so Excel
     * shows them as text instead of running them as formulas.
     *
     * @param list<string> $header
     * @param iterable<list<mixed>> $rows
     */
    public static function download(string $filename, array $header, iterable $rows): Response
    {
        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₱ correctly
        fputcsv($out, $header, escape: '');
        foreach ($rows as $row) {
            fputcsv($out, array_map(self::cell(...), $row), escape: '');
        }
        rewind($out);

        return Response::download((string) stream_get_contents($out), $filename, 'text/csv; charset=UTF-8');
    }

    private static function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');
        if ($value !== '' && !is_numeric($value) && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
