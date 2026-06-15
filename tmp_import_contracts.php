<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$commit = in_array('--commit', $argv, true);
$file = 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\العقود\\العقود_معبأ_محدث.xlsx';
$database = trim((string) ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp'));
$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . $database . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$rows = readRows($file);
$adminId = preferredAdminId($pdo);
$nationalities = translatableLookup($pdo, 'nationalities_translations', 'nationality_id', ['title', 'sub_title']);
$stats = [
    'excel_rows' => count($rows),
    'contracts_inserted' => 0,
    'contracts_updated' => 0,
    'contract_infos_inserted' => 0,
    'contract_infos_updated' => 0,
    'orders_inserted' => 0,
    'orders_updated_completed' => 0,
    'order_details_inserted' => 0,
    'maids_marked_reserved' => 0,
    'contracts_without_order' => 0,
    'missing_user' => 0,
    'missing_maid' => 0,
    'skipped' => 0,
];
$skips = [];
$withoutOrder = [];

$pdo->beginTransaction();

try {
    foreach ($rows as $rowNumber => $row) {
        $contractId = numericId(value($row, 'رقم العقد'));
        if ($contractId === null) {
            $stats['skipped']++;
            $skips[] = "row {$rowNumber}: missing contract number";
            continue;
        }

        $phone = normalizePhone(value($row, 'جوال العميل'));
        $national = digitsOnly(value($row, 'رقم هوية العميل'));
        $passport = normalizePassport(value($row, 'رقم جواز العاملة'));
        $cleanPassport = cleanPassport($passport);
        $user = findUser($pdo, $phone, $national);
        $maid = findMaid($pdo, $passport);
        $status = normalizeContractStatus(value($row, 'حالة العقد'));
        $contractDate = parseDate(value($row, 'تاريخ العقد'));
        $amount = decimalValue(value($row, 'قيمة العقد'));
        $nationalityId = lookupId($nationalities, value($row, 'الجنسية'));

        if (!$user) {
            $stats['missing_user']++;
        }
        if (!$maid) {
            $stats['missing_maid']++;
        }

        $orderId = null;
        if ($user && $maid) {
            $order = ensureCompletedOrder($pdo, (int) $user['id'], (int) $maid['id'], $adminId, $contractId, $contractDate, $stats);
            $orderId = (int) $order['id'];
        } else {
            $stats['contracts_without_order']++;
            $withoutOrder[] = [
                'row' => $rowNumber,
                'contract_id' => $contractId,
                'user' => $user ? $user['id'] : null,
                'maid' => $maid ? $maid['id'] : null,
                'passport' => $passport,
                'client' => value($row, 'اسم العميل'),
            ];
        }

        $contractPayload = [
            'id' => $contractId,
            'status' => $status,
            'warranty_status' => 'not_applicable',
            'user_id' => $user['id'] ?? null,
            'maid_id' => $maid['id'] ?? null,
            'order_id' => $orderId,
            'marketer_id' => $adminId,
            'type' => 'maid',
            'contract_date' => $contractDate,
            'contract_cost' => $amount,
            'completed_at' => $status === 'completed' ? datetimeFromDate($contractDate) : null,
            'updated_at' => nowString(),
        ];

        if (rowExists($pdo, 'contracts', 'id', $contractId)) {
            updateRow($pdo, 'contracts', 'id', $contractId, withoutNulls($contractPayload, ['completed_at']));
            $stats['contracts_updated']++;
        } else {
            $contractPayload['created_at'] = datetimeFromDate($contractDate) ?: nowString();
            insertRow($pdo, 'contracts', $contractPayload);
            $stats['contracts_inserted']++;
        }

        $infoPayload = [
            'contract_id' => $contractId,
            'contract_date' => $contractDate,
            'contract_value' => $amount,
            'manual_maid_passport_num' => $maid ? null : ($cleanPassport ?: $passport ?: null),
            'manual_maid_nationality_id' => $maid ? null : $nationalityId,
            'updated_at' => nowString(),
        ];
        if (rowExists($pdo, 'contract_infos', 'contract_id', $contractId)) {
            updateRow($pdo, 'contract_infos', 'contract_id', $contractId, withoutNulls($infoPayload, ['manual_maid_passport_num', 'manual_maid_nationality_id']));
            $stats['contract_infos_updated']++;
        } else {
            $infoPayload['created_at'] = datetimeFromDate($contractDate) ?: nowString();
            insertRow($pdo, 'contract_infos', $infoPayload);
            $stats['contract_infos_inserted']++;
        }

        if ($maid) {
            $updated = $pdo->prepare("UPDATE maids SET status = 'reserved', available = 0, updated_at = ? WHERE id = ? AND (status <> 'reserved' OR available <> 0)");
            $updated->execute([nowString(), (int) $maid['id']]);
            $stats['maids_marked_reserved'] += $updated->rowCount();
        }
    }

    if ($commit) {
        $pdo->commit();
        resetAutoIncrement($pdo, 'orders');
        resetAutoIncrement($pdo, 'order_details');
        resetAutoIncrement($pdo, 'contracts');
        resetAutoIncrement($pdo, 'contract_infos');
    } else {
        $pdo->rollBack();
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo 'Mode: ' . ($commit ? 'COMMIT' : 'DRY-RUN') . PHP_EOL;
echo 'Database: ' . $database . PHP_EOL;
foreach ($stats as $key => $value) {
    echo $key . ': ' . $value . PHP_EOL;
}

if ($withoutOrder) {
    echo PHP_EOL . 'Contracts without completed order:' . PHP_EOL;
    foreach (array_slice($withoutOrder, 0, 50) as $entry) {
        echo json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}

if ($skips) {
    echo PHP_EOL . 'Skipped rows:' . PHP_EOL;
    foreach ($skips as $skip) {
        echo '- ' . $skip . PHP_EOL;
    }
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

function ensureCompletedOrder(PDO $pdo, int $userId, int $maidId, int $adminId, int $contractId, ?string $contractDate, array &$stats): array
{
    $stmt = $pdo->prepare('SELECT order_id FROM contracts WHERE id = ? AND order_id IS NOT NULL LIMIT 1');
    $stmt->execute([$contractId]);
    $existingOrderId = $stmt->fetchColumn();
    if ($existingOrderId) {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $existingOrderId]);
        $order = $stmt->fetch();
        if ($order) {
            updateCompletedOrder($pdo, (int) $order['id'], $userId, $maidId, $adminId, $stats);
            ensureOrderDetail($pdo, (int) $order['id'], $adminId, $contractDate, $stats);
            return ['id' => (int) $order['id']];
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? AND maid_id = ? AND job_type_id = 1 AND deleted_at IS NULL LIMIT 1");
    $stmt->execute([$userId, $maidId]);
    $order = $stmt->fetch();
    if ($order) {
        updateCompletedOrder($pdo, (int) $order['id'], $userId, $maidId, $adminId, $stats);
        ensureOrderDetail($pdo, (int) $order['id'], $adminId, $contractDate, $stats);
        return ['id' => (int) $order['id']];
    }

    $createdAt = datetimeFromDate($contractDate) ?: nowString();
    $payload = [
        'maid_id' => $maidId,
        'user_id' => $userId,
        'payment_method' => 'cash',
        'payment_type' => null,
        'order_code' => uniqueOrderCode($pdo, $contractId),
        'job_type_id' => 1,
        'status' => 'completed',
        'customer_id' => $adminId,
        'branch_id' => null,
        'status_changed_at' => $createdAt,
        'deadline_at' => null,
        'expiration_time' => null,
        'created_at' => $createdAt,
        'updated_at' => nowString(),
    ];
    insertRow($pdo, 'orders', $payload);
    $orderId = (int) $pdo->lastInsertId();
    $stats['orders_inserted']++;
    ensureOrderDetail($pdo, $orderId, $adminId, $contractDate, $stats);

    return ['id' => $orderId];
}

function updateCompletedOrder(PDO $pdo, int $orderId, int $userId, int $maidId, int $adminId, array &$stats): void
{
    $stmt = $pdo->prepare("UPDATE orders SET user_id = ?, maid_id = ?, job_type_id = 1, status = 'completed', customer_id = COALESCE(customer_id, ?), deadline_at = NULL, expiration_time = NULL, status_changed_at = COALESCE(status_changed_at, ?), updated_at = ? WHERE id = ?");
    $stmt->execute([$userId, $maidId, $adminId, nowString(), nowString(), $orderId]);
    $stats['orders_updated_completed']++;
}

function ensureOrderDetail(PDO $pdo, int $orderId, int $adminId, ?string $contractDate, array &$stats): void
{
    if (rowExists($pdo, 'order_details', 'order_id', $orderId)) {
        $stmt = $pdo->prepare('UPDATE order_details SET admin_id = COALESCE(admin_id, ?), updated_at = ? WHERE order_id = ?');
        $stmt->execute([$adminId, nowString(), $orderId]);
        return;
    }

    insertRow($pdo, 'order_details', [
        'order_id' => $orderId,
        'admin_id' => $adminId,
        'deliver_maid' => '0',
        'created_at' => datetimeFromDate($contractDate) ?: nowString(),
        'updated_at' => nowString(),
    ]);
    $stats['order_details_inserted']++;
}

function findUser(PDO $pdo, string $phone, string $national): ?array
{
    if ($phone !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ? OR phone2 = ? LIMIT 1');
        $stmt->execute([$phone, $phone]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    if ($national !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE national_num = ? LIMIT 1');
        $stmt->execute([$national]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
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
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return $row;
    }

    $cleanPassport = cleanPassport($passport);
    if ($cleanPassport !== '' && $cleanPassport !== $passport) {
        $stmt = $pdo->prepare("SELECT id FROM maids WHERE UPPER(REPLACE(REPLACE(REPLACE(REPLACE(passport_num, '.', ''), ':', ''), ' ', ''), '-', '')) = ? LIMIT 1");
        $stmt->execute([$cleanPassport]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return $row ?: null;
}

function preferredAdminId(PDO $pdo): int
{
    $id = $pdo->query('SELECT id FROM admins WHERE id = 72 LIMIT 1')->fetchColumn();
    if ($id) {
        return (int) $id;
    }

    return (int) $pdo->query('SELECT id FROM admins ORDER BY id LIMIT 1')->fetchColumn();
}

function normalizeContractStatus(string $status): string
{
    $status = trim($status);
    return match ($status) {
        'مكتمل', 'عقد مكتمل', 'completed' => 'completed',
        'ساري', 'عقد ساري', 'sari' => 'sari',
        default => 'completed',
    };
}

function uniqueOrderCode(PDO $pdo, int $contractId): string
{
    $base = (string) (90000000 + $contractId);
    $code = $base;
    $suffix = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM orders WHERE order_code = ? LIMIT 1');
        $stmt->execute([$code]);
        if (!$stmt->fetchColumn()) {
            return $code;
        }
        $code = $base . $suffix;
        $suffix++;
    }
}

function rowExists(PDO $pdo, string $table, string $key, int $id): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM `{$table}` WHERE `{$key}` = ? LIMIT 1");
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

function insertRow(PDO $pdo, string $table, array $payload): void
{
    $payload = array_filter($payload, fn ($value) => $value !== null);
    $columns = array_keys($payload);
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($payload));
}

function updateRow(PDO $pdo, string $table, string $key, int $id, array $payload): void
{
    unset($payload[$key], $payload['id']);
    $payload = array_filter($payload, fn ($value) => $value !== null);
    if (!$payload) {
        return;
    }

    $columns = array_keys($payload);
    $sql = 'UPDATE `' . $table . '` SET `' . implode('` = ?, `', $columns) . '` = ? WHERE `' . $key . '` = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([...array_values($payload), $id]);
}

function withoutNulls(array $payload, array $keepNullKeys = []): array
{
    foreach ($payload as $key => $value) {
        if ($value === null && !in_array($key, $keepNullKeys, true)) {
            unset($payload[$key]);
        }
    }

    return $payload;
}

function resetAutoIncrement(PDO $pdo, string $table): void
{
    $max = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) FROM `{$table}`")->fetchColumn();
    $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = " . ($max + 1));
}

function translatableLookup(PDO $pdo, string $table, string $idColumn, array $textColumns): array
{
    $lookup = [];
    foreach ($pdo->query("SELECT * FROM `{$table}`") as $row) {
        foreach ($textColumns as $column) {
            $value = normalizeArabicKey((string) ($row[$column] ?? ''));
            if ($value !== '') {
                $lookup[$value] = (int) $row[$idColumn];
            }
        }
    }

    return $lookup;
}

function lookupId(array $lookup, string $value): ?int
{
    $key = normalizeArabicKey($value);
    return $key === '' ? null : ($lookup[$key] ?? null);
}

function normalizeArabicKey(string $value): string
{
    $value = trim(translateDigits($value));
    $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
    $value = str_replace('ى', 'ي', $value);
    $value = str_replace('ة', 'ه', $value);
    $value = preg_replace('/\s+/u', ' ', $value);

    return mb_strtolower($value);
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

function decimalValue(string $value): ?float
{
    $value = str_replace(',', '', translateDigits($value));
    if (!is_numeric($value)) {
        return null;
    }

    return round((float) $value, 2);
}

function parseDate(string $value): ?string
{
    $value = trim(translateDigits($value));
    if ($value === '') {
        return null;
    }

    try {
        return (new DateTime($value))->format('Y-m-d');
    } catch (Throwable) {
        return null;
    }
}

function datetimeFromDate(?string $date): ?string
{
    return $date ? $date . ' 00:00:00' : null;
}

function nowString(): string
{
    return date('Y-m-d H:i:s');
}
