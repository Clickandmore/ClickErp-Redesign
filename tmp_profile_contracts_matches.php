<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$file = 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\العقود\\العقود_معبأ_محدث.xlsx';
$database = trim((string) ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp'));
$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . $database . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$rows = readRows($file);
$stats = [
    'rows' => 0,
    'with_contract_number' => 0,
    'duplicate_contract_numbers' => 0,
    'user_found' => 0,
    'user_missing' => 0,
    'maid_found' => 0,
    'maid_missing' => 0,
    'both_found' => 0,
    'existing_contract_ids' => 0,
];
$seenContracts = [];
$statuses = [];
$missingUsers = [];
$missingMaids = [];
$existingContracts = [];

foreach ($rows as $rowNumber => $row) {
    $stats['rows']++;
    $contractId = numericId(value($row, 'رقم العقد'));
    $phone = normalizePhone(value($row, 'جوال العميل'));
    $national = digitsOnly(value($row, 'رقم هوية العميل'));
    $passport = normalizePassport(value($row, 'رقم جواز العاملة'));
    $status = value($row, 'حالة العقد');
    if ($status !== '') {
        $statuses[$status] = ($statuses[$status] ?? 0) + 1;
    }

    if ($contractId !== null) {
        $stats['with_contract_number']++;
        if (isset($seenContracts[$contractId])) {
            $stats['duplicate_contract_numbers']++;
        }
        $seenContracts[$contractId] = true;

        $stmt = $pdo->prepare('SELECT id, order_id, user_id, maid_id, status FROM contracts WHERE id = ? LIMIT 1');
        $stmt->execute([$contractId]);
        $existing = $stmt->fetch();
        if ($existing) {
            $stats['existing_contract_ids']++;
            $existingContracts[] = ['row' => $rowNumber] + $existing;
        }
    }

    $user = findUser($pdo, $phone, $national);
    $maid = findMaid($pdo, $passport);
    if ($user) {
        $stats['user_found']++;
    } else {
        $stats['user_missing']++;
        $missingUsers[] = compact('rowNumber', 'phone', 'national') + ['name' => value($row, 'اسم العميل')];
    }

    if ($maid) {
        $stats['maid_found']++;
    } else {
        $stats['maid_missing']++;
        $missingMaids[] = compact('rowNumber', 'passport') + ['nationality' => value($row, 'الجنسية')];
    }

    if ($user && $maid) {
        $stats['both_found']++;
    }
}

echo 'Database: ' . $database . PHP_EOL;
foreach ($stats as $key => $value) {
    echo $key . ': ' . $value . PHP_EOL;
}

echo PHP_EOL . 'Statuses:' . PHP_EOL;
arsort($statuses);
foreach ($statuses as $status => $count) {
    echo $status . ': ' . $count . PHP_EOL;
}

echo PHP_EOL . 'Existing contract IDs:' . PHP_EOL;
foreach (array_slice($existingContracts, 0, 20) as $contract) {
    echo json_encode($contract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

echo PHP_EOL . 'Missing users:' . PHP_EOL;
foreach (array_slice($missingUsers, 0, 30) as $missing) {
    echo json_encode($missing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

echo PHP_EOL . 'Missing maids:' . PHP_EOL;
foreach (array_slice($missingMaids, 0, 30) as $missing) {
    echo json_encode($missing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
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
            $header = $headers[$col] ?: Coordinate::stringFromColumnIndex($col);
            $value = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getFormattedValue());
            $data[$header] = $value;
            $hasValue = $hasValue || $value !== '';
        }
        if ($hasValue) {
            $rows[$row] = $data;
        }
    }

    return $rows;
}

function findUser(PDO $pdo, string $phone, string $national): ?array
{
    if ($phone !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ? OR phone2 = ? LIMIT 1');
        $stmt->execute([$phone, $phone]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }

    if ($national !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE national_num = ? LIMIT 1');
        $stmt->execute([$national]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
    }

    return null;
}

function findMaid(PDO $pdo, string $passport): ?array
{
    if ($passport === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id FROM maids WHERE passport_num = ? LIMIT 1');
    $stmt->execute([$passport]);
    $row = $stmt->fetch();
    if ($row) {
        return $row;
    }

    $cleanPassport = cleanPassport($passport);
    if ($cleanPassport !== '' && $cleanPassport !== $passport) {
        $stmt = $pdo->prepare("SELECT id FROM maids WHERE UPPER(REPLACE(REPLACE(REPLACE(REPLACE(passport_num, '.', ''), ':', ''), ' ', ''), '-', '')) = ? LIMIT 1");
        $stmt->execute([$cleanPassport]);
        $row = $stmt->fetch();
    }

    return $row ?: null;
}

function value(array $row, string $key): string
{
    return trim((string) ($row[$key] ?? ''));
}

function numericId(string $value): ?int
{
    $digits = digitsOnly($value);
    return $digits === '' ? null : (int) $digits;
}

function normalizePhone(string $value): string
{
    $digits = digitsOnly($value);
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

function normalizePassport(string $value): string
{
    return strtoupper(preg_replace('/\s+/u', '', translateDigits($value)));
}

function cleanPassport(string $value): string
{
    return preg_replace('/[^A-Z0-9]/u', '', normalizePassport($value));
}

function digitsOnly(string $value): string
{
    return preg_replace('/\D+/u', '', translateDigits($value));
}

function translateDigits(string $value): string
{
    return strtr($value, [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ]);
}
