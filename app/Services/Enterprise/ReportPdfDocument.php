<?php

namespace App\Services\Enterprise;

/** Dependency-free, text-only PDF for bounded aggregate statements on shared hosting. */
class ReportPdfDocument
{
    public function render(string $title, array $summary, array $headings, iterable $rows): string
    {
        $lines = [];
        $widths = [38, 13, 13, 13];
        $format = fn (array $cells) => implode(' ', array_map(
            fn ($cell, $index) => str_pad(substr($this->latin((string) $cell), 0, $widths[$index] ?? 13), $widths[$index] ?? 13),
            array_values($cells), array_keys(array_values($cells))
        ));
        $lines[] = $format($headings);
        $lines[] = str_repeat('-', 80);
        foreach ($rows as $row) {
            $lines[] = $format($row);
        }
        if (count($lines) === 2) {
            $lines[] = 'No records match the selected filters.';
        }
        $pages = array_chunk(array_slice($lines, 2), 42);
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Courier /Encoding /WinAnsiEncoding >>',
        ];
        $pageIds = [];
        foreach ($pages as $pageIndex => $body) {
            $pageId = count($objects) + 1;
            $contentId = $pageId + 1;
            $pageIds[] = $pageId.' 0 R';
            $stream = "0.05 0.35 0.22 rg\nBT /F1 17 Tf 48 790 Td (".$this->escape($title).") Tj ET\n";
            $stream .= "0 0 0 rg\nBT /F1 9 Tf 48 768 Td (".$this->escape('Generated '.gmdate('Y-m-d H:i').' UTC').") Tj ET\n";
            $y = 747;
            foreach (array_slice($summary, 0, 4) as $line) {
                $stream .= "BT /F1 9 Tf 48 {$y} Td (".$this->escape(mb_strimwidth((string) $line, 0, 100, '...')).") Tj ET\n";
                $y -= 15;
            }
            $y -= 15;
            foreach (array_merge(array_slice($lines, 0, 2), $body) as $line) {
                $stream .= "BT /F2 8 Tf 48 {$y} Td (".$this->escape($line, true).") Tj ET\n";
                $y -= 13;
            }
            $stream .= "BT /F1 8 Tf 48 38 Td (".$this->escape('Page '.($pageIndex + 1).' of '.count($pages).' | Aggregate campaign records').") Tj ET\n";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentId} 0 R >>";
            $objects[$contentId] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream."endstream";
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageIds).'] /Count '.count($pages).' >>';
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($number = 1; $number <= count($objects); $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }

    private function latin(string $text): string
    {
        return preg_replace('/[\x00-\x1f\x7f]/', ' ', iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: '');
    }

    private function escape(string $text, bool $encoded = false): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded ? $text : $this->latin($text));
    }
}
