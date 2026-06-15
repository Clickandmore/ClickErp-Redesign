<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$files = [
    'housemaid' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\سير ذاتية\\عاملة منزلية\\عاملة_منزلية_محدث.xlsx',
    'driver' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\سير ذاتية\\سائق خاص\\سائق_خاص_محدث.xlsx',
    'other_jobs' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\سير ذاتية\\باقي المهن\\باقي_المهن_محدث.xlsx',
];

foreach ($files as $key => $path) {
    echo PHP_EOL . "==== {$key} ====" . PHP_EOL;
    echo "File: {$path}" . PHP_EOL;

    if (!is_file($path)) {
        echo "Missing file" . PHP_EOL;
        continue;
    }

    $reader = IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $reader->setReadEmptyCells(false);
    $spreadsheet = $reader->load($path);

    foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        echo "Sheet: {$sheet->getTitle()} rows={$highestRow} columns={$highestColumn} ({$highestColumnIndex})" . PHP_EOL;

        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $header = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . '1')->getFormattedValue());
            $headers[$col] = $header;
            echo "{$col}: {$header}" . PHP_EOL;
        }

        echo "Sample rows:" . PHP_EOL;
        for ($row = 2; $row <= min($highestRow, 6); $row++) {
            $parts = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getFormattedValue());
                if ($value !== '') {
                    $parts[] = ($headers[$col] ?: "COL{$col}") . '=' . $value;
                }
            }
            echo "{$row}: " . implode(' | ', $parts) . PHP_EOL;
        }
    }
}
