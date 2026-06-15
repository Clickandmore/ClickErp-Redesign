<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$file = 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\عملاء\\clients_dedup.xlsx';
$database = trim((string) ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp'));
$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . $database . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestDataRow();
$highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
$headers = [];

for ($col = 1; $col <= $highestColumnIndex; $col++) {
    $headers[$col] = trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($col) . '1')->getFormattedValue());
}

$columns = array_flip($headers);
$missing = [];

for ($row = 2; $row <= $highestRow; $row++) {
    $id = digitsOnly(value($sheet, $columns, 'ID', $row));
    if ($id === '') {
        continue;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);
    if (!$stmt->fetchColumn()) {
        $missing[] = [
            'row' => $row,
            'id' => $id,
            'name' => value($sheet, $columns, 'اسم العميل', $row),
            'phone' => normalizePhone(value($sheet, $columns, 'رقم الجوال', $row)),
            'national_num' => value($sheet, $columns, 'رقم الهوية/الإقامة', $row),
        ];
    }
}

echo 'Database: ' . $database . PHP_EOL;
echo 'Users count: ' . $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() . PHP_EOL;
echo 'Excel data rows: ' . ($highestRow - 1) . PHP_EOL;
echo 'Missing Excel IDs in users: ' . count($missing) . PHP_EOL;
echo 'Duplicate phones in database: ' . $pdo->query('SELECT COUNT(*) FROM (SELECT phone FROM users GROUP BY phone HAVING COUNT(*) > 1) x')->fetchColumn() . PHP_EOL;
echo PHP_EOL . 'First users:' . PHP_EOL;

foreach ($pdo->query('SELECT id, name, phone, phone2, national_num, status, client_category_id FROM users ORDER BY id LIMIT 5') as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

if ($missing) {
    echo PHP_EOL . 'Missing rows:' . PHP_EOL;
    foreach ($missing as $row) {
        echo '- row ' . $row['row']
            . ' ID=' . $row['id']
            . ' phone=' . $row['phone']
            . ' name=' . $row['name']
            . ' national=' . $row['national_num']
            . PHP_EOL;
    }
}

function value($sheet, array $columns, string $header, int $row): string
{
    if (!isset($columns[$header])) {
        return '';
    }

    return trim((string) $sheet->getCell(Coordinate::stringFromColumnIndex($columns[$header]) . $row)->getFormattedValue());
}

function digitsOnly(string $value): string
{
    return preg_replace('/\D+/u', '', translateDigits($value));
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

function translateDigits(string $value): string
{
    return strtr($value, [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ]);
}
