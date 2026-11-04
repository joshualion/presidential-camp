<?php

use App\Services\Enterprise\ReportPdfDocument;

test('aggregate PDF paginates safely and emits valid cross-reference offsets', function () {
    $rows = array_fill(0, 90, ['Calabar (Central) \\ Office', '1,000', '800', '750']);
    $pdf = (new ReportPdfDocument)->render('Campaign workforce report', ['Synthetic statement'], ['Location', 'Members', 'Active', 'Voters'], $rows);
    expect($pdf)->toStartWith('%PDF-1.4')->toContain('/Count 3', 'Page 3 of 3', 'Calabar \\(Central\\) \\\\ Office');
    preg_match('/startxref\n(\d+)\n%%EOF/', $pdf, $start);
    expect(substr($pdf, (int) $start[1], 4))->toBe('xref');
    preg_match('/xref\n0 (\d+)\n(.*?)trailer/s', $pdf, $xref);
    $entries = explode("\n", trim($xref[2]));
    for ($index = 1; $index < (int) $xref[1]; $index++) {
        $offset = (int) substr($entries[$index], 0, 10);
        expect(substr($pdf, $offset, strlen($index.' 0 obj')))->toBe($index.' 0 obj');
    }
});
