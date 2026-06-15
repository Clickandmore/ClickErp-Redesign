<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$options = [
    'file' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\عملاء\\clients_dedup.xlsx',
    'target' => trim((string) ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp')),
    'source' => 'alrafedain_live',
    'commit' => false,
    'prepare' => false,
];

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--commit') {
        $options['commit'] = true;
    } elseif ($arg === '--prepare-db') {
        $options['prepare'] = true;
    } elseif (str_starts_with($arg, '--file=')) {
        $options['file'] = substr($arg, 7);
    } elseif (str_starts_with($arg, '--target=')) {
        $options['target'] = trim(substr($arg, 9));
    } elseif (str_starts_with($arg, '--source=')) {
        $options['source'] = trim(substr($arg, 9));
    }
}

if ($options['target'] === '') {
    fail('Target database is empty.');
}

if (!is_file($options['file'])) {
    fail('Excel file not found: ' . $options['file']);
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

$server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

if ($options['prepare']) {
    prepareTargetDatabase($server, $options['source'], $options['target']);
}

if (!databaseExists($server, $options['target'])) {
    fail("Target database does not exist: {$options['target']}. Run with --prepare-db first.");
}

$pdo = new PDO("mysql:host={$host};port={$port};dbname={$options['target']};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$usersColumns = columnSet($pdo, 'users');
if (!$usersColumns) {
    fail("Target database {$options['target']} does not contain users table.");
}

$lookups = buildLookups($pdo);
$rows = readClientRows($options['file']);
$stats = [
    'total_excel_rows' => count($rows),
    'inserted' => 0,
    'updated' => 0,
    'skipped' => 0,
    'matched_by_id' => 0,
    'matched_by_phone' => 0,
    'matched_by_national_num' => 0,
    'phone_conflict_used_phone2' => 0,
    'duplicate_phone_conflicts_skipped' => 0,
    'account_id_set' => 0,
    'account_id_missing' => 0,
    'client_category_defaulted' => 0,
    'nationality_id_set' => 0,
    'nationality_unmatched' => 0,
];
$skips = [];
$now = date('Y-m-d H:i:s');

$pdo->beginTransaction();

try {
    foreach ($rows as $rowNumber => $row) {
        $payload = buildPayload($row, $usersColumns, $lookups, $stats, $now);

        if (($payload['name'] ?? '') === '') {
            $stats['skipped']++;
            $skips[] = "row {$rowNumber}: missing name";
            continue;
        }

        if (($payload['phone'] ?? '') === '') {
            $stats['skipped']++;
            $skips[] = "row {$rowNumber}: missing phone";
            continue;
        }

        $match = findExistingUser($pdo, $payload, $row['ID'] ?? null);
        if ($match && $match['by'] === 'phone' && isPhoneConflict($match, $payload)) {
            $originalPhone = $payload['phone'];
            $alternatePhone = $payload['phone2'] ?? null;

            if ($alternatePhone && $alternatePhone !== $originalPhone && !phoneExists($pdo, $alternatePhone)) {
                $payload['phone'] = $alternatePhone;
                $payload['phone2'] = $originalPhone;
                $stats['phone_conflict_used_phone2']++;
                $match = findExistingUser($pdo, $payload, $row['ID'] ?? null);
            } else {
                $stats['skipped']++;
                $stats['duplicate_phone_conflicts_skipped']++;
                $skips[] = "row {$rowNumber}: duplicate phone {$originalPhone} conflicts with user {$match['id']}";
                continue;
            }
        }

        if ($match) {
            $stats['updated']++;
            $stats['matched_by_' . $match['by']]++;
            updateUser($pdo, $usersColumns, (int) $match['id'], $payload);
        } else {
            $stats['inserted']++;
            insertUser($pdo, $usersColumns, $payload);
        }
    }

    if ($options['commit']) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

echo 'Mode: ' . ($options['commit'] ? 'COMMIT' : 'DRY-RUN') . PHP_EOL;
echo 'Target database: ' . $options['target'] . PHP_EOL;
echo 'Excel rows: ' . $stats['total_excel_rows'] . PHP_EOL;
echo 'Inserted: ' . $stats['inserted'] . PHP_EOL;
echo 'Updated: ' . $stats['updated'] . PHP_EOL;
echo 'Skipped: ' . $stats['skipped'] . PHP_EOL;
echo 'Matched by id: ' . $stats['matched_by_id'] . PHP_EOL;
echo 'Matched by phone: ' . $stats['matched_by_phone'] . PHP_EOL;
echo 'Matched by national_num: ' . $stats['matched_by_national_num'] . PHP_EOL;
echo 'Phone conflicts resolved with phone2: ' . $stats['phone_conflict_used_phone2'] . PHP_EOL;
echo 'Duplicate phone conflicts skipped: ' . $stats['duplicate_phone_conflicts_skipped'] . PHP_EOL;
echo 'Account IDs set: ' . $stats['account_id_set'] . PHP_EOL;
echo 'Account IDs missing in fn_accounts: ' . $stats['account_id_missing'] . PHP_EOL;
echo 'Default client category set: ' . $stats['client_category_defaulted'] . PHP_EOL;
echo 'Nationality IDs set: ' . $stats['nationality_id_set'] . PHP_EOL;
echo 'Nationalities unmatched: ' . $stats['nationality_unmatched'] . PHP_EOL;

if ($skips) {
    echo PHP_EOL . 'First skipped rows:' . PHP_EOL;
    foreach (array_slice($skips, 0, 50) as $skip) {
        echo '- ' . $skip . PHP_EOL;
    }
}

function prepareTargetDatabase(PDO $server, string $source, string $target): void
{
    if (!databaseExists($server, $source)) {
        fail("Source database does not exist: {$source}");
    }

    if (databaseExists($server, $target)) {
        echo "Target database already exists: {$target}" . PHP_EOL;
        return;
    }

    echo "Creating database {$target} from {$source}" . PHP_EOL;
    $server->exec('CREATE DATABASE ' . ident($target) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $tables = $server
        ->query("SHOW FULL TABLES FROM " . ident($source) . " WHERE Table_type = 'BASE TABLE'")
        ->fetchAll(PDO::FETCH_NUM);

    $server->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach ($tables as $tableRow) {
            $table = $tableRow[0];
            $createRow = $server->query('SHOW CREATE TABLE ' . ident($source) . '.' . ident($table))->fetch(PDO::FETCH_ASSOC);
            $createSql = $createRow['Create Table'];
            $createSql = preg_replace('/^CREATE TABLE `([^`]+)`/u', 'CREATE TABLE ' . ident($target) . '.`$1`', $createSql, 1);
            $server->exec($createSql);
        }

        foreach ($tables as $tableRow) {
            $table = $tableRow[0];
            $server->exec('INSERT INTO ' . ident($target) . '.' . ident($table) . ' SELECT * FROM ' . ident($source) . '.' . ident($table));
        }
    } finally {
        $server->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    echo 'Copied tables: ' . count($tables) . PHP_EOL;
}

function databaseExists(PDO $pdo, string $database): bool
{
    $stmt = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
    $stmt->execute([$database]);

    return (bool) $stmt->fetchColumn();
}

function columnSet(PDO $pdo, string $table): array
{
    try {
        $columns = $pdo->query('SHOW COLUMNS FROM ' . ident($table))->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        return [];
    }

    $set = [];
    foreach ($columns as $column) {
        $set[$column['Field']] = true;
    }

    return $set;
}

function buildLookups(PDO $pdo): array
{
    $lookups = [
        'accounts' => [],
        'categories' => [],
        'default_category_id' => null,
        'nationalities' => [],
        'default_created_by' => null,
        'default_branch_id' => null,
    ];

    if (tableExists($pdo, 'fn_accounts')) {
        foreach ($pdo->query('SELECT id FROM fn_accounts') as $row) {
            $lookups['accounts'][(string) $row['id']] = true;
        }
    }

    if (tableExists($pdo, 'fn_client_categories')) {
        foreach ($pdo->query('SELECT id, name FROM fn_client_categories') as $row) {
            $lookups['categories'][normalizeKey($row['name'])] = (int) $row['id'];
            if ($lookups['default_category_id'] === null) {
                $lookups['default_category_id'] = (int) $row['id'];
            }
        }
    }

    if (tableExists($pdo, 'nationalities_translations')) {
        foreach ($pdo->query('SELECT nationality_id, title, sub_title FROM nationalities_translations') as $row) {
            foreach ([$row['title'] ?? null, $row['sub_title'] ?? null] as $name) {
                $key = normalizeKey($name);
                if ($key !== '') {
                    $lookups['nationalities'][$key] = (int) $row['nationality_id'];
                }
            }
        }
    }

    if (tableExists($pdo, 'admins')) {
        $lookups['default_created_by'] = $pdo->query('SELECT id FROM admins ORDER BY id LIMIT 1')->fetchColumn() ?: null;
    }

    if (tableExists($pdo, 'branches')) {
        $stmt = $pdo->query('SELECT id FROM branches WHERE is_main = 1 ORDER BY id LIMIT 1');
        $lookups['default_branch_id'] = $stmt->fetchColumn() ?: null;
        if (!$lookups['default_branch_id']) {
            $lookups['default_branch_id'] = $pdo->query('SELECT id FROM branches ORDER BY id LIMIT 1')->fetchColumn() ?: null;
        }
    }

    return $lookups;
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);

    return (bool) $stmt->fetchColumn();
}

function readClientRows(string $file): array
{
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
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
            $header = $headers[$col] ?: 'COL' . $col;
            $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);
            $value = trim((string) $cell->getFormattedValue());
            $data[$header] = $value;
            if ($value !== '') {
                $hasValue = true;
            }
        }

        if ($hasValue) {
            $data['_row'] = $row;
            $data['_birthdate'] = parseExcelDate($sheet, $row, findHeaderColumn($headers, 'تاريخ الميلاد'));
            $rows[$row] = $data;
        }
    }

    return $rows;
}

function buildPayload(array $row, array $columns, array $lookups, array &$stats, string $now): array
{
    static $importPasswordHash = null;

    if ($importPasswordHash === null) {
        $importPasswordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
    }

    $name = text($row['اسم العميل'] ?? '');
    [$firstName, $lastName] = splitName($name);
    $status = normalizeKey($row['الحالة'] ?? '') === normalizeKey('نشط') ? 1 : 0;
    $nationality = text($row['الجنسية'] ?? '');
    $payload = [
        'id' => numericId($row['ID'] ?? null),
        'name' => $name,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone_code' => '+966',
        'phone' => normalizePhone($row['رقم الجوال'] ?? ''),
        'phone2' => normalizePhone($row['رقم جوال إضافي'] ?? ''),
        'national_num' => digitsOnly($row['رقم الهوية/الإقامة'] ?? ''),
        'email' => nullableText($row['البريد الإلكتروني'] ?? ''),
        'password' => $importPasswordHash,
        'status' => $status,
        'is_blocked' => $status ? 0 : 1,
        'register_from' => 'excel_import',
        'client_type' => 'person',
        'birthdate' => $row['_birthdate'] ?? null,
        'marital_status' => maritalStatus($row['الحالة الاجتماعية'] ?? ''),
        'city' => nullableText($row['المدينة'] ?? ''),
        'address' => nullableText($row['العنوان'] ?? ''),
        'country' => $nationality ?: null,
        'rooms_num' => nullableDigits($row['عدد الغرف'] ?? ''),
        'family_num' => nullableDigits($row['عدد أفراد الأسرة'] ?? ''),
        'children' => nullableDigits($row['عدد الأطفال'] ?? ''),
        'job' => nullableText($row['الوظيفة'] ?? ''),
        'profession' => nullableText($row['الوظيفة'] ?? ''),
        'created_at' => $now,
        'updated_at' => $now,
    ];

    if (!empty($columns['account_id'])) {
        $accountId = numericId($row['Account ID'] ?? null);
        if ($accountId !== null && isset($lookups['accounts'][(string) $accountId])) {
            $payload['account_id'] = $accountId;
            $stats['account_id_set']++;
        } elseif ($accountId !== null) {
            $stats['account_id_missing']++;
        }
    }

    if (!empty($columns['client_category_id'])) {
        $categoryKey = normalizeKey($row['تصنيف العميل'] ?? '');
        if ($categoryKey !== '' && isset($lookups['categories'][$categoryKey])) {
            $payload['client_category_id'] = $lookups['categories'][$categoryKey];
        } elseif ($lookups['default_category_id']) {
            $payload['client_category_id'] = $lookups['default_category_id'];
            $stats['client_category_defaulted']++;
        }
    }

    if (!empty($columns['nationality_id']) && $nationality !== '') {
        $nationalityId = $lookups['nationalities'][normalizeKey($nationality)] ?? null;
        if (!$nationalityId) {
            $nationalityId = $lookups['nationalities'][normalizeKey(normalizeSaudiNationality($nationality))] ?? null;
        }

        if ($nationalityId) {
            $payload['nationality_id'] = $nationalityId;
            $stats['nationality_id_set']++;
        } else {
            $stats['nationality_unmatched']++;
        }
    }

    if (!empty($columns['created_by']) && $lookups['default_created_by']) {
        $payload['created_by'] = (int) $lookups['default_created_by'];
    }

    if (!empty($columns['branch_id']) && $lookups['default_branch_id']) {
        $payload['branch_id'] = (int) $lookups['default_branch_id'];
    }

    return array_intersect_key($payload, $columns);
}

function findExistingUser(PDO $pdo, array $payload, mixed $sourceId): ?array
{
    $id = numericId($sourceId);
    if ($id !== null) {
        $stmt = $pdo->prepare('SELECT id, national_num FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $found = $stmt->fetch();
        if ($found) {
            return ['id' => (int) $found['id'], 'national_num' => $found['national_num'] ?? null, 'by' => 'id'];
        }
    }

    if (($payload['phone'] ?? '') !== '') {
        $stmt = $pdo->prepare('SELECT id, national_num FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$payload['phone']]);
        $found = $stmt->fetch();
        if ($found) {
            return ['id' => (int) $found['id'], 'national_num' => $found['national_num'] ?? null, 'by' => 'phone'];
        }
    }

    if (($payload['national_num'] ?? '') !== '') {
        $stmt = $pdo->prepare('SELECT id, national_num FROM users WHERE national_num = ? LIMIT 1');
        $stmt->execute([$payload['national_num']]);
        $found = $stmt->fetch();
        if ($found) {
            return ['id' => (int) $found['id'], 'national_num' => $found['national_num'] ?? null, 'by' => 'national_num'];
        }
    }

    return null;
}

function isPhoneConflict(array $match, array $payload): bool
{
    $existingNationalNum = digitsOnly($match['national_num'] ?? '');
    $incomingNationalNum = digitsOnly($payload['national_num'] ?? '');

    return $existingNationalNum !== ''
        && $incomingNationalNum !== ''
        && $existingNationalNum !== $incomingNationalNum;
}

function phoneExists(PDO $pdo, string $phone): bool
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
    $stmt->execute([$phone]);

    return (bool) $stmt->fetchColumn();
}

function phoneExistsForOtherUser(PDO $pdo, string $phone, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = ? AND id <> ? LIMIT 1');
    $stmt->execute([$phone, $userId]);

    return (bool) $stmt->fetchColumn();
}

function insertUser(PDO $pdo, array $columns, array $payload): void
{
    $payload = array_filter($payload, static fn ($value, $key) => isset($columns[$key]), ARRAY_FILTER_USE_BOTH);
    $fields = array_keys($payload);
    $sql = 'INSERT INTO users (' . implode(', ', array_map('ident', $fields)) . ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')';
    $pdo->prepare($sql)->execute(array_values($payload));
}

function updateUser(PDO $pdo, array $columns, int $id, array $payload): void
{
    unset($payload['id'], $payload['password'], $payload['created_at']);
    if (isset($payload['phone']) && phoneExistsForOtherUser($pdo, $payload['phone'], $id)) {
        unset($payload['phone']);
    }

    $payload = array_filter($payload, static fn ($value, $key) => isset($columns[$key]), ARRAY_FILTER_USE_BOTH);
    $fields = array_keys($payload);
    if (!$fields) {
        return;
    }

    $assignments = array_map(static fn ($field) => ident($field) . ' = ?', $fields);
    $values = array_values($payload);
    $values[] = $id;
    $sql = 'UPDATE users SET ' . implode(', ', $assignments) . ' WHERE id = ?';
    $pdo->prepare($sql)->execute($values);
}

function parseExcelDate($sheet, int $row, ?int $col): ?string
{
    if (!$col) {
        return null;
    }

    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);
    $raw = $cell->getValue();
    if (is_numeric($raw) && ExcelDate::isDateTime($cell)) {
        return ExcelDate::excelToDateTimeObject($raw)->format('Y-m-d');
    }

    $value = trim((string) $cell->getFormattedValue());
    if ($value === '') {
        return null;
    }

    $value = translateDigits($value);
    foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y'] as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d');
        }
    }

    $timestamp = strtotime($value);

    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

function findHeaderColumn(array $headers, string $name): ?int
{
    foreach ($headers as $col => $header) {
        if (normalizeKey($header) === normalizeKey($name)) {
            return (int) $col;
        }
    }

    return null;
}

function splitName(string $name): array
{
    $parts = preg_split('/\s+/u', trim($name), 2);

    return [$parts[0] ?? null, $parts[1] ?? null];
}

function maritalStatus(mixed $value): ?string
{
    $key = normalizeKey($value);
    return match ($key) {
        normalizeKey('متزوج'), normalizeKey('متزوجة') => 'married',
        normalizeKey('اعزب'), normalizeKey('أعزب'), normalizeKey('عزباء') => 'single',
        normalizeKey('مطلق'), normalizeKey('مطلقة') => 'divorced',
        normalizeKey('ارمل'), normalizeKey('أرمل'), normalizeKey('ارملة'), normalizeKey('أرملة') => 'widowed',
        default => null,
    };
}

function normalizeSaudiNationality(string $value): string
{
    $key = normalizeKey($value);
    if (in_array($key, [normalizeKey('سعودي'), normalizeKey('سعودية')], true)) {
        return 'السعودية';
    }

    return $value;
}

function text(mixed $value): string
{
    return trim(preg_replace('/\s+/u', ' ', (string) $value));
}

function nullableText(mixed $value): ?string
{
    $value = text($value);

    return $value === '' ? null : $value;
}

function numericId(mixed $value): ?int
{
    $digits = digitsOnly($value);

    return $digits === '' ? null : (int) $digits;
}

function nullableDigits(mixed $value): ?string
{
    $digits = digitsOnly($value);

    return $digits === '' ? null : $digits;
}

function digitsOnly(mixed $value): string
{
    return preg_replace('/\D+/u', '', translateDigits((string) $value));
}

function normalizePhone(mixed $value): ?string
{
    $digits = digitsOnly($value);
    if ($digits === '') {
        return null;
    }

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

function normalizeKey(mixed $value): string
{
    $value = mb_strtolower(text($value), 'UTF-8');
    $value = strtr($value, [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
        'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي',
        'ـ' => '',
    ]);
    $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value);

    return trim($value);
}

function ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}
