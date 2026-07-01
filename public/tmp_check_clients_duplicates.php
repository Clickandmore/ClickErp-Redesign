<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\عملاء\\clients_dedup.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestDataRow();
$highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
$headers = [];

for ($col = 1; $col <= $highestColumnIndex; $col++) {
    $headers[$col] = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . '1')->getFormattedValue());
}

$columns = array_flip($headers);
$phones = [];

for ($row = 2; $row <= $highestRow; $row++) {
    $phone = normalizePhone((string) $sheet->getCell(Coordinate::stringFromColumnIndex($columns['رقم الجوال']) . $row)->getFormattedValue());
    if ($phone === '') {
        continue;
    }

    $phones[$phone][] = [
        'row' => $row,
        'id' => value($sheet, $columns, 'ID', $row),
        'name' => value($sheet, $columns, 'اسم العميل', $row),
        'national_num' => value($sheet, $columns, 'رقم الهوية/الإقامة', $row),
    ];
}

$duplicates = array_filter($phones, static fn ($items) => count($items) > 1);
echo 'Duplicate phones: ' . count($duplicates) . PHP_EOL;

foreach (array_slice($duplicates, 0, 40, true) as $phone => $items) {
    echo PHP_EOL . $phone . PHP_EOL;
    foreach ($items as $item) {
        echo '- row ' . $item['row'] . ' ID=' . $item['id'] . ' name=' . $item['name'] . ' national=' . $item['national_num'] . PHP_EOL;
    }
}

function value($sheet, array $columns, string $header, int $row): string
{
    if (!isset($columns[$header])) {
        return '';
    }

    return trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($columns[$header]) . $row)->getFormattedValue());
}

function normalizePhone(string $value): string
{
    $digits = preg_replace('/\D+/u', '', strtr($value, [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ]));

    if (str_starts_with($digits, '00966')) {
        $digits = substr($digits, 2);
    }

    if (str_starts_with($digits, '966') && strlen($digits) === 12) {
        return '0' . substr($digits, 3);
    }

    if (str_starts_with($digits, '5') && strlen($digits) === 9) {
        return '0' . $digits;
    }

    return $digits;
}
