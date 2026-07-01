<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$file = 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\العقود\\العقود_معبأ_محدث.xlsx';
$database = trim((string) ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp'));

$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$reader->setReadEmptyCells(false);
$spreadsheet = $reader->load($file);

echo 'File: ' . $file . PHP_EOL;
echo 'Sheets: ' . implode(', ', array_map(fn ($sheet) => $sheet->getTitle(), $spreadsheet->getAllSheets())) . PHP_EOL;

foreach ($spreadsheet->getAllSheets() as $sheet) {
    $highestRow = $sheet->getHighestDataRow();
    $highestColumn = $sheet->getHighestDataColumn();
    $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

    echo PHP_EOL . 'Sheet: ' . $sheet->getTitle() . PHP_EOL;
    echo 'Rows: ' . $highestRow . ' Cols: ' . $highestColumn . PHP_EOL;

    $headers = [];
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $headers[$col] = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . '1')->getFormattedValue());
    }

    echo 'Headers:' . PHP_EOL;
    foreach ($headers as $col => $header) {
        if ($header !== '') {
            echo Coordinate::stringFromColumnIndex($col) . ': ' . $header . PHP_EOL;
        }
    }

    echo 'Sample rows:' . PHP_EOL;
    $printed = 0;
    for ($row = 2; $row <= $highestRow && $printed < 8; $row++) {
        $values = [];
        $hasValue = false;
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $header = $headers[$col] ?: Coordinate::stringFromColumnIndex($col);
            $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getFormattedValue());
            if ($value !== '') {
                $hasValue = true;
            }
            $values[] = $header . '=' . $value;
        }

        if ($hasValue) {
            echo $row . ': ' . implode(' | ', $values) . PHP_EOL;
            $printed++;
        }
    }
}

$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . $database . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

echo PHP_EOL . 'Database: ' . $database . PHP_EOL;
foreach (['users', 'maids', 'orders', 'order_details', 'contracts', 'contract_infos'] as $table) {
    echo $table . ': ' . $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() . PHP_EOL;
}

foreach (['orders', 'order_details', 'contracts', 'contract_infos', 'maids', 'users'] as $table) {
    echo PHP_EOL . 'Columns ' . $table . ':' . PHP_EOL;
    foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`") as $column) {
        echo $column['Field'] . ' ' . $column['Type'] . ' Null=' . $column['Null'] . ' Default=' . ($column['Default'] ?? 'NULL') . PHP_EOL;
    }
}
