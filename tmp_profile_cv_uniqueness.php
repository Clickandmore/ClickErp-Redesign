<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$files = [
    'housemaid' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\سير ذاتية\\عاملة منزلية\\عاملة_منزلية_محدث.xlsx',
    'driver' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\سير ذاتية\\سائق خاص\\سائق_خاص_محدث.xlsx',
    'other_jobs' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\سير ذاتية\\باقي المهن\\باقي_المهن_محدث.xlsx',
];

$database = trim((string) ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp'));
$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . $database . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$allPassports = [];
$allIds = [];
$summary = [];

foreach ($files as $key => $path) {
    $rows = readRows($path);
    $summary[$key] = count($rows);

    foreach ($rows as $rowNumber => $row) {
        $passport = normalizePassport($row['رقم الجواز *'] ?? '');
        $id = digitsOnly($row['ID'] ?? '');
        if ($passport !== '') {
            $allPassports[$passport][] = "{$key}:{$rowNumber}";
        }
        if ($id !== '') {
            $allIds[$id][] = "{$key}:{$rowNumber}";
        }
    }
}

echo 'Database maids: ' . $pdo->query('SELECT COUNT(*) FROM maids')->fetchColumn() . PHP_EOL;
echo 'Existing maids sample:' . PHP_EOL;
foreach ($pdo->query('SELECT id,name,passport_num,nationality_id,religion_id,status,request_type,old_id FROM maids ORDER BY id LIMIT 20') as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

echo PHP_EOL . 'Excel rows:' . PHP_EOL;
foreach ($summary as $key => $count) {
    echo "{$key}: {$count}" . PHP_EOL;
}

$duplicatePassports = array_filter($allPassports, fn ($items) => count($items) > 1);
$duplicateIds = array_filter($allIds, fn ($items) => count($items) > 1);

echo 'Unique passports: ' . count($allPassports) . PHP_EOL;
echo 'Duplicate passports: ' . count($duplicatePassports) . PHP_EOL;
foreach (array_slice($duplicatePassports, 0, 30, true) as $passport => $locations) {
    echo "- duplicate passport {$passport}: " . implode(', ', $locations) . PHP_EOL;
}
echo 'Unique IDs: ' . count($allIds) . PHP_EOL;
echo 'Duplicate IDs: ' . count($duplicateIds) . PHP_EOL;

$existingPassportConflicts = [];
foreach (array_keys($allPassports) as $passport) {
    $stmt = $pdo->prepare('SELECT id,name FROM maids WHERE passport_num = ? LIMIT 1');
    $stmt->execute([$passport]);
    if ($found = $stmt->fetch()) {
        $existingPassportConflicts[$passport] = $found;
    }
}

echo 'Existing passport conflicts: ' . count($existingPassportConflicts) . PHP_EOL;
foreach (array_slice($existingPassportConflicts, 0, 30, true) as $passport => $found) {
    echo "- {$passport}: user id {$found['id']} {$found['name']} excel=" . implode(', ', $allPassports[$passport] ?? []) . PHP_EOL;
}

$existingIdConflicts = [];
foreach (array_keys($allIds) as $id) {
    $stmt = $pdo->prepare('SELECT id,name,passport_num FROM maids WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);
    if ($found = $stmt->fetch()) {
        $existingIdConflicts[$id] = $found;
    }
}

echo 'Existing ID conflicts: ' . count($existingIdConflicts) . PHP_EOL;
foreach (array_slice($existingIdConflicts, 0, 30, true) as $id => $found) {
    echo "- {$id}: {$found['name']} passport {$found['passport_num']}" . PHP_EOL;
}

function readRows(string $path): array
{
    $reader = IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $reader->setReadEmptyCells(false);
    $spreadsheet = $reader->load($path);
    $sheet = $spreadsheet->getSheetByName('البيانات') ?: $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestDataRow();
    $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
    $headers = [];
    for ($col = 1; $col <= $highestColumnIndex; $col++) {
        $headers[$col] = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . '1')->getFormattedValue());
    }

    $rows = [];
    for ($row = 2; $row <= $highestRow; $row++) {
        $data = [];
        $hasValue = false;
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getFormattedValue());
            $data[$headers[$col] ?: "COL{$col}"] = $value;
            $hasValue = $hasValue || $value !== '';
        }
        if ($hasValue) {
            $rows[$row] = $data;
        }
    }

    return $rows;
}

function normalizePassport(string $value): string
{
    return mb_strtoupper(trim($value), 'UTF-8');
}

function digitsOnly(string $value): string
{
    return preg_replace('/\D+/u', '', strtr($value, [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]));
}
