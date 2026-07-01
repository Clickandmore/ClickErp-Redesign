<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\عملاء\\clients_dedup.xlsx';

if (!is_file($path)) {
    fwrite(STDERR, "File not found: {$path}" . PHP_EOL);
    exit(1);
}

$spreadsheet = IOFactory::load($path);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestDataRow();
$highestColumn = $sheet->getHighestDataColumn();
$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

echo "File: {$path}" . PHP_EOL;
echo "Sheet: " . $sheet->getTitle() . PHP_EOL;
echo "Rows: {$highestRow}" . PHP_EOL;
echo "Columns: {$highestColumn} ({$highestColumnIndex})" . PHP_EOL;
echo PHP_EOL . "Headers:" . PHP_EOL;

$headers = [];
for ($col = 1; $col <= $highestColumnIndex; $col++) {
    $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '1';
    $value = trim((string) $sheet->getCell($cell)->getFormattedValue());
    $headers[$col] = $value;
    echo $col . ': ' . $value . PHP_EOL;
}

echo PHP_EOL . "Sample rows:" . PHP_EOL;
$maxRow = min($highestRow, 6);
for ($row = 2; $row <= $maxRow; $row++) {
    $parts = [];
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $row;
        $value = trim((string) $sheet->getCell($cell)->getFormattedValue());
        if ($value !== '') {
            $parts[] = ($headers[$col] ?: "COL{$col}") . '=' . $value;
        }
    }
    echo $row . ': ' . implode(' | ', $parts) . PHP_EOL;
}
