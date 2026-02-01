<?php
function readEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line == '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) != 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($value != '' && ($value[0] == '"' || $value[0] == "'")) {
            $value = trim($value, "\"'");
        }
        $vars[$key] = $value;
    }

    return $vars;
}

$env = readEnvFile(__DIR__ . '/../../.env');

$MIGRATION_OLD_DB_HOST = $env['MIGRATION_OLD_DB_HOST'] ?? $env['OLD_DB_HOST'] ?? $env['DB_OLD_HOST'] ?? '127.0.0.1';
$MIGRATION_OLD_DB_PORT = $env['MIGRATION_OLD_DB_PORT'] ?? $env['OLD_DB_PORT'] ?? $env['DB_OLD_PORT'] ?? '8889';
$MIGRATION_OLD_DB_NAME = $env['MIGRATION_OLD_DB_NAME'] ?? $env['OLD_DB_NAME'] ?? $env['OLD_DB_DATABASE'] ?? $env['DB_OLD_DATABASE'] ?? 'platinum_old';
$MIGRATION_OLD_DB_USER = $env['MIGRATION_OLD_DB_USER'] ?? $env['OLD_DB_USER'] ?? $env['DB_OLD_USERNAME'] ?? 'root';
$MIGRATION_OLD_DB_PASS = $env['MIGRATION_OLD_DB_PASS'] ?? $env['OLD_DB_PASS'] ?? $env['DB_OLD_PASSWORD'] ?? 'root';

$MIGRATION_NEW_DB_HOST = $env['MIGRATION_NEW_DB_HOST'] ?? $env['DB_HOST'] ?? '127.0.0.1';
$MIGRATION_NEW_DB_PORT = $env['MIGRATION_NEW_DB_PORT'] ?? $env['DB_PORT'] ?? '8889';
$MIGRATION_NEW_DB_NAME = $env['MIGRATION_NEW_DB_NAME'] ?? $env['DB_DATABASE'] ?? 'platinum_new';
$MIGRATION_NEW_DB_USER = $env['MIGRATION_NEW_DB_USER'] ?? $env['DB_USERNAME'] ?? 'root';
$MIGRATION_NEW_DB_PASS = $env['MIGRATION_NEW_DB_PASS'] ?? $env['DB_PASSWORD'] ?? 'root';

error_reporting(E_ALL);
ini_set('display_errors', '1');

$action = $_POST['action'] ?? $_GET['action'] ?? 'analyze';

if ($action !== 'export_csv') {
    header('Content-Type: application/json; charset=utf-8');
}

$config = [
    'host'   => $_POST['db_host'] ?? $_GET['db_host'] ?? $MIGRATION_NEW_DB_HOST,
    'port'   => (int)($_POST['db_port'] ?? $_GET['db_port'] ?? $MIGRATION_NEW_DB_PORT),
    'user'   => $_POST['db_user'] ?? $_GET['db_user'] ?? $MIGRATION_NEW_DB_USER,
    'pass'   => $_POST['db_pass'] ?? $_GET['db_pass'] ?? $MIGRATION_NEW_DB_PASS,
    'old_db' => $_POST['old_db'] ?? $_GET['old_db'] ?? $MIGRATION_OLD_DB_NAME,
    'new_db' => $_POST['new_db'] ?? $_GET['new_db'] ?? $MIGRATION_NEW_DB_NAME,
];

try {
    $oldDb = connectDatabase($config['old_db'], $config);
    $newDb = connectDatabase($config['new_db'], $config);

    $reference = loadCountryReference();

    switch ($action) {
        case 'export_csv':
            exportCountriesCsv($newDb);
            return;

        case 'analyze':
            echo json_encode(analyzeCountries($oldDb, $newDb, $reference), JSON_UNESCAPED_UNICODE);
            break;

        case 'preview':
            $limit = (int)($_POST['limit'] ?? $_GET['limit'] ?? 40);
            echo json_encode(
                previewCountries($oldDb, $reference, $limit),
                JSON_UNESCAPED_UNICODE
            );
            break;

        case 'migrate':
            $chunkSize = max(50, (int)($_POST['chunk'] ?? $_GET['chunk'] ?? 500));
            echo json_encode(
                migrateCountries($oldDb, $newDb, $reference, $chunkSize),
                JSON_UNESCAPED_UNICODE
            );
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'إجراء غير معروف، استخدم analyze أو preview أو migrate'
            ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}

function connectDatabase(string $dbName, array $config): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $dbName
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ];

    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    $pdo->exec('SET NAMES utf8mb4');

    return $pdo;
}

function loadCountryReference(): array
{
    $path = dirname(__DIR__, 2) . '/countries_data.php';
    if (!file_exists($path)) {
        return [];
    }

    $raw = include $path;
    if (!is_array($raw)) {
        return [];
    }

    $reference = [];
    foreach ($raw as $record) {
        if (!is_array($record) || empty($record['code'])) {
            continue;
        }
        $code = strtoupper((string)$record['code']);
        $reference[$code] = $record;
    }

    return $reference;
}

function analyzeCountries(PDO $oldDb, PDO $newDb, array $reference): array
{
    $oldStats = $oldDb->query('SELECT COUNT(*) AS total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS active_count FROM countries')->fetch();
    $newStats = $newDb->query('SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count FROM countries')->fetch();

    $oldCodes = $oldDb->query('SELECT code FROM countries')->fetchAll(PDO::FETCH_COLUMN);
    $newCodes = $newDb->query('SELECT code FROM countries')->fetchAll(PDO::FETCH_COLUMN);

    $oldCodes = array_map('strtoupper', array_filter((array)$oldCodes));
    $newCodes = array_map('strtoupper', array_filter((array)$newCodes));

    $missingInNew = array_values(array_diff($oldCodes, $newCodes));
    $missingInOld = array_values(array_diff($newCodes, $oldCodes));

    $duplicateCodes = $oldDb->query(
        "SELECT code, COUNT(*) AS total FROM countries WHERE code IS NOT NULL AND code != '' GROUP BY code HAVING total > 1 ORDER BY total DESC"
    )->fetchAll();

    $referenceCoverage = [
        'total_reference' => count($reference),
        'matched' => 0,
        'missing_codes' => [],
    ];

    foreach ($oldCodes as $code) {
        if (isset($reference[$code])) {
            $referenceCoverage['matched']++;
        } else {
            $referenceCoverage['missing_codes'][] = $code;
        }
    }

    return [
        'success' => true,
        'old_stats' => $oldStats,
        'new_stats' => $newStats,
        'missing_in_new' => $missingInNew,
        'missing_in_old' => $missingInOld,
        'duplicate_codes' => $duplicateCodes,
        'reference_coverage' => $referenceCoverage,
    ];
}

function previewCountries(PDO $oldDb, array $reference, int $limit): array
{
    $limit = max(1, $limit);
    $stmt = $oldDb->prepare('SELECT id, code, name, status, created_at, updated_at FROM countries ORDER BY id LIMIT :limit');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $preview = [];
    $sortCounter = 0;

    foreach ($stmt as $row) {
        $referenceRow = null;
        $code = strtoupper((string)$row['code']);
        if ($code && isset($reference[$code])) {
            $referenceRow = $reference[$code];
        }

        $mapped = transformCountryRow($row, $referenceRow, $sortCounter);
        $preview[] = [
            'source' => $row,
            'mapped' => $mapped,
        ];
    }

    return [
        'success' => true,
        'preview_count' => count($preview),
        'data' => $preview,
    ];
}

function migrateCountries(PDO $oldDb, PDO $newDb, array $reference, int $chunkSize): array
{
    $totalRows = (int)$oldDb->query('SELECT COUNT(*) FROM countries')->fetchColumn();
    if ($totalRows === 0) {
        return [
            'success' => true,
            'message' => 'لا توجد بيانات لنقلها من جدول البلدان في القاعدة القديمة.',
            'stats' => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        ];
    }

    $stmt = $oldDb->prepare('SELECT id, code, name, status, created_at, updated_at FROM countries ORDER BY id');
    $stmt->execute();

    $insertSql = 'INSERT INTO countries (
            id, name_ar, name_en, code, phone_code, currency, currency_symbol,
            is_active, sort_order, created_at, updated_at
        ) VALUES (
            :id, :name_ar, :name_en, :code, :phone_code, :currency, :currency_symbol,
            :is_active, :sort_order, :created_at, :updated_at
        )
        ON DUPLICATE KEY UPDATE
            name_ar = VALUES(name_ar),
            name_en = VALUES(name_en),
            code = VALUES(code),
            phone_code = VALUES(phone_code),
            currency = VALUES(currency),
            currency_symbol = VALUES(currency_symbol),
            is_active = VALUES(is_active),
            sort_order = VALUES(sort_order),
            updated_at = VALUES(updated_at)';

    $insertStmt = $newDb->prepare($insertSql);

    $sortCounter = 0;
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $errors = [];
    $processed = 0;

    $newDb->beginTransaction();

    try {
        foreach ($stmt as $row) {
            $processed++;
            $code = strtoupper((string)$row['code']);
            $referenceRow = $code && isset($reference[$code]) ? $reference[$code] : null;

            $mapped = transformCountryRow($row, $referenceRow, $sortCounter);
            if ($mapped === null) {
                $skipped++;
                if (count($errors) < 20) {
                    $errors[] = ['error' => 'بيانات غير صالحة', 'row' => $row];
                }
                continue;
            }

            try {
                $insertStmt->execute([
                    ':id' => $mapped['id'],
                    ':name_ar' => $mapped['name_ar'],
                    ':name_en' => $mapped['name_en'],
                    ':code' => $mapped['code'],
                    ':phone_code' => $mapped['phone_code'],
                    ':currency' => $mapped['currency'],
                    ':currency_symbol' => $mapped['currency_symbol'],
                    ':is_active' => $mapped['is_active'],
                    ':sort_order' => $mapped['sort_order'],
                    ':created_at' => $mapped['created_at'],
                    ':updated_at' => $mapped['updated_at'],
                ]);

                $affected = $insertStmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                }
            } catch (Throwable $e) {
                $skipped++;
                if (count($errors) < 20) {
                    $errors[] = [
                        'error' => $e->getMessage(),
                        'row' => $row,
                    ];
                }
            }

            if ($processed % $chunkSize === 0) {
                $newDb->commit();
                $newDb->beginTransaction();
            }
        }

        $newDb->commit();
    } catch (Throwable $e) {
        $newDb->rollBack();
        throw $e;
    }

    return [
        'success' => true,
        'message' => 'اكتمل نقل بيانات البلدان.',
        'stats' => [
            'total_source_rows' => $totalRows,
            'processed' => $processed,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
        ],
        'errors' => $errors,
    ];
}

function exportCountriesCsv(PDO $newDb): void
{
    $filename = 'countries_export_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    if ($output === false) {
        throw new RuntimeException('تعذر فتح مخرجات CSV.');
    }

    fputcsv($output, [
        'id',
        'name_ar',
        'name_en',
        'code',
        'phone_code',
        'currency',
        'currency_symbol',
        'is_active',
        'sort_order',
        'created_at',
        'updated_at',
    ]);

    $query = $newDb->query('SELECT id, name_ar, name_en, code, phone_code, currency, currency_symbol,
        is_active, sort_order, created_at, updated_at FROM countries ORDER BY sort_order, id');

    foreach ($query as $row) {
        fputcsv($output, [
            $row['id'],
            $row['name_ar'],
            $row['name_en'],
            $row['code'],
            $row['phone_code'],
            $row['currency'],
            $row['currency_symbol'],
            $row['is_active'],
            $row['sort_order'],
            $row['created_at'],
            $row['updated_at'],
        ]);
    }

    fclose($output);
    exit;
}

function transformCountryRow(array $row, ?array $referenceRow, int &$sortCounter): ?array
{
    $id = (int)$row['id'];
    if ($id <= 0) {
        return null;
    }

    $code = strtoupper(trim((string)($row['code'] ?? '')));
    if ($code === '') {
        return null;
    }

    $nameAr = trim((string)($row['name'] ?? ''));
    if ($nameAr === '') {
        $nameAr = $referenceRow['name_ar'] ?? '';
    }

    $nameEn = $referenceRow['name_en'] ?? transliterateName($nameAr);
    if ($nameEn === '') {
        $nameEn = $nameAr;
    }

    $phoneCode = $referenceRow['phone_code'] ?? '';
    if ($phoneCode !== '' && $phoneCode[0] !== '+' && preg_match('/^\d+$/', $phoneCode)) {
        $phoneCode = '+' . $phoneCode;
    }

    $currency = $referenceRow['currency'] ?? '';
    $currencySymbol = $referenceRow['currency_symbol'] ?? '';

    $isActive = isset($referenceRow['is_active'])
        ? (int)$referenceRow['is_active']
        : ((isset($row['status']) && (int)$row['status'] === 1) ? 1 : 0);

    $sortCounter++;
    $sortOrder = isset($referenceRow['sort_order']) ? (int)$referenceRow['sort_order'] : $sortCounter;

    $createdAt = normalizeTimestamp($row['created_at'] ?? null);
    $updatedAt = normalizeTimestamp($row['updated_at'] ?? null) ?: $createdAt;

    return [
        'id' => $id,
        'name_ar' => $nameAr,
        'name_en' => $nameEn,
        'code' => $code,
        'phone_code' => $phoneCode,
        'currency' => $currency,
        'currency_symbol' => $currencySymbol,
        'is_active' => $isActive,
        'sort_order' => $sortOrder,
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
    ];
}

function transliterateName(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $latin = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($latin === false || $latin === '') {
        return '';
    }

    $latin = preg_replace('/[^A-Za-z0-9\s-]/', '', $latin) ?? '';
    $latin = preg_replace('/\s+/', ' ', $latin) ?? '';

    return trim($latin);
}

function normalizeTimestamp(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return null;
    }

    return $value;
}
