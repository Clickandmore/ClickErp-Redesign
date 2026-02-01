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
    'host'      => $_POST['db_host'] ?? $_GET['db_host'] ?? $MIGRATION_NEW_DB_HOST,
    'port'      => (int)($_POST['db_port'] ?? $_GET['db_port'] ?? $MIGRATION_NEW_DB_PORT),
    'user'      => $_POST['db_user'] ?? $_GET['db_user'] ?? $MIGRATION_NEW_DB_USER,
    'pass'      => $_POST['db_pass'] ?? $_GET['db_pass'] ?? $MIGRATION_NEW_DB_PASS,
    'old_db'    => $_POST['old_db'] ?? $_GET['old_db'] ?? $MIGRATION_OLD_DB_NAME,
    'new_db'    => $_POST['new_db'] ?? $_GET['new_db'] ?? $MIGRATION_NEW_DB_NAME,
];

try {
    $oldDb = connectDatabase($config['old_db'], $config);
    $newDb = connectDatabase($config['new_db'], $config);

    $countryMeta = loadCountryMeta($newDb);
    if (empty($countryMeta['by_id'])) {
        throw new RuntimeException('Ø¬Ø¯ÙˆÙ„ countries ÙÙŠ Ø§Ù„Ù‚Ø§Ø¹Ø¯Ø© Ø§Ù„Ø¬Ø¯ÙŠØ¯Ø© ÙØ§Ø±Øº Ø£Ùˆ ØºÙŠØ± Ù…ØªØ§Ø­.');
    }

    $referenceCities = loadReferenceCities($countryMeta);
    $capitalHints = loadCapitalHints();

    switch ($action) {
        case 'export_csv':
            exportCitiesCsv($newDb, $countryMeta);
            return;

        case 'missing':
            $limit = (int)($_POST['limit'] ?? $_GET['limit'] ?? 100);
            echo json_encode(
                reportMissingCities($oldDb, $countryMeta, $limit),
                JSON_UNESCAPED_UNICODE
            );
            break;

        case 'missing_ids':
            $ids = $_POST['ids'] ?? $_GET['ids'] ?? '';
            echo json_encode(
                reportMissingByIds($oldDb, $countryMeta, $ids),
                JSON_UNESCAPED_UNICODE
            );
            break;

        case 'analyze':
            echo json_encode(analyzeCities($oldDb, $newDb, $countryMeta), JSON_UNESCAPED_UNICODE);
            break;

        case 'preview':
            $limit = (int)($_POST['limit'] ?? $_GET['limit'] ?? 40);
            echo json_encode(
                previewMappedCities($oldDb, $countryMeta, $referenceCities, $capitalHints, $limit),
                JSON_UNESCAPED_UNICODE
            );
            break;

        case 'migrate':
            $chunkSize = max(100, (int)($_POST['chunk'] ?? $_GET['chunk'] ?? 1000));
            echo json_encode(
                migrateCities($oldDb, $newDb, $countryMeta, $referenceCities, $capitalHints, $chunkSize),
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

function loadCountryMeta(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, code, name_ar, name_en FROM countries');
    $byId = [];
    $idByCode = [];

    foreach ($stmt as $row) {
        $id = (int)$row['id'];
        $code = strtoupper(trim((string)$row['code']));
        $byId[$id] = [
            'id' => $id,
            'code' => $code,
            'name_ar' => $row['name_ar'],
            'name_en' => $row['name_en'],
        ];
        if ($code !== '') {
            $idByCode[$code] = $id;
        }
    }

    return ['by_id' => $byId, 'id_by_code' => $idByCode];
}

function loadReferenceCities(array $countryMeta): array
{
    $path = dirname(__DIR__, 2) . '/cities_data.php';
    if (!file_exists($path)) {
        return [];
    }

    $raw = include $path;
    if (!is_array($raw)) {
        return [];
    }

    $map = [];
    $idByCode = $countryMeta['id_by_code'] ?? [];

    foreach ($raw as $record) {
        if (!is_array($record) || empty($record['country_code'])) {
            continue;
        }

        $code = strtoupper((string)$record['country_code']);
        $countryId = $idByCode[$code] ?? null;
        if (!$countryId) {
            continue;
        }

        $nameKey = $record['name_en'] ?? $record['name_ar'] ?? '';
        $slug = slugify($nameKey);
        if ($slug === '') {
            continue;
        }

        $key = $countryId . '::' . $slug;
        $map[$key] = $record + ['country_id' => $countryId];
    }

    return $map;
}

function loadCapitalHints(): array
{
    // Lowercase entries to simplify matching.
    return [
        17  => ['manama', 'المنامة'],
        64  => ['cairo', 'القاهرة'],
        69  => ['addis abeba', 'addis ababa', 'أديس أبابا'],
        111 => ['amman', 'عمان'],
        113 => ['nairobi', 'نيروبي'],
        117 => ['kuwait city', 'مدينة الكويت'],
        153 => ['kathmandu', 'كاتماندو'],
        173 => ['manila', 'مانيلا'],
        178 => ['doha', 'الدوحة'],
        191 => ['riyadh', 'الرياض'],
        227 => ['kampala', 'كمبالا'],
        229 => ['abu dhabi', 'أبوظبي', 'ابوظبي'],
        299 => ['dhaka', 'دكا'],
    ];
}

function analyzeCities(PDO $oldDb, PDO $newDb, array $countryMeta): array
{
    $oldTotal = (int)$oldDb->query('SELECT COUNT(*) FROM cities')->fetchColumn();
    $oldActive = (int)$oldDb->query('SELECT COUNT(*) FROM cities WHERE deleted_at IS NULL')->fetchColumn();
    $newTotal = (int)$newDb->query('SELECT COUNT(*) FROM cities')->fetchColumn();
    $newActive = (int)$newDb->query('SELECT COUNT(*) FROM cities WHERE is_active = 1')->fetchColumn();

    $missingCountries = [];
    $countrySet = array_keys($countryMeta['by_id']);

    $stmt = $oldDb->query('SELECT DISTINCT country_id FROM cities');
    foreach ($stmt as $row) {
        $countryId = (int)$row['country_id'];
        if (!in_array($countryId, $countrySet, true)) {
            $missingCountries[] = $countryId;
        }
    }

    $duplicates = $oldDb->query(
        'SELECT country_id, name, COUNT(*) AS total
         FROM cities
         GROUP BY country_id, name
         HAVING total > 1
         ORDER BY total DESC, country_id
         LIMIT 20'
    )->fetchAll();

    return [
        'success' => true,
        'summary' => [
            'old_total' => $oldTotal,
            'old_active' => $oldActive,
            'new_total' => $newTotal,
            'new_active' => $newActive,
        ],
        'missing_countries_in_new_db' => $missingCountries,
        'duplicate_names_sample' => $duplicates,
    ];
}

function previewMappedCities(
    PDO $oldDb,
    array $countryMeta,
    array $referenceCities,
    array $capitalHints,
    int $limit
): array {
    $sql = 'SELECT c.id, c.country_id, c.name, c.created_at, c.updated_at, c.deleted_at
            FROM cities c
            ORDER BY c.country_id, c.id
            LIMIT :limit';
    $stmt = $oldDb->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $sortCounters = [];
    $preview = [];

    foreach ($stmt as $row) {
        $countryId = (int)$row['country_id'];
        if (!isset($countryMeta['by_id'][$countryId])) {
            continue;
        }

        $mapped = transformCityRow(
            $row,
            $countryMeta['by_id'][$countryId],
            $sortCounters,
            $referenceCities,
            $capitalHints
        );

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

function migrateCities(
    PDO $oldDb,
    PDO $newDb,
    array $countryMeta,
    array $referenceCities,
    array $capitalHints,
    int $chunkSize
): array {
    $totalRows = (int)$oldDb->query('SELECT COUNT(*) FROM cities')->fetchColumn();
    if ($totalRows === 0) {
        return [
            'success' => true,
            'message' => 'لا توجد بيانات لنقلها من جدول المدن في القاعدة القديمة.',
            'stats' => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
        ];
    }

    $sql = 'SELECT c.id, c.country_id, c.name, c.created_at, c.updated_at, c.deleted_at
            FROM cities c
            ORDER BY c.country_id, c.id';

    $stmt = $oldDb->prepare($sql);
    $stmt->execute();

    $insertSql = 'INSERT INTO cities (
            id, name_ar, name_en, code, country_id, is_capital,
            latitude, longitude, is_active, sort_order, created_at, updated_at
        ) VALUES (
            :id, :name_ar, :name_en, :code, :country_id, :is_capital,
            :latitude, :longitude, :is_active, :sort_order, :created_at, :updated_at
        )
        ON DUPLICATE KEY UPDATE
            name_ar = VALUES(name_ar),
            name_en = VALUES(name_en),
            code = VALUES(code),
            country_id = VALUES(country_id),
            is_capital = VALUES(is_capital),
            latitude = VALUES(latitude),
            longitude = VALUES(longitude),
            is_active = VALUES(is_active),
            sort_order = VALUES(sort_order),
            updated_at = VALUES(updated_at)';

    $insertStmt = $newDb->prepare($insertSql);

    $sortCounters = [];
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $skippedExamples = [];
    $errors = [];
    $processed = 0;

    $newDb->beginTransaction();

    try {
        foreach ($stmt as $row) {
            $processed++;
            $countryId = (int)$row['country_id'];

            if (!isset($countryMeta['by_id'][$countryId])) {
                $skipped++;
                if (count($skippedExamples) < 20) {
                    $skippedExamples[] = ['reason' => 'missing_country', 'row' => $row];
                }
                continue;
            }

            $mapped = transformCityRow(
                $row,
                $countryMeta['by_id'][$countryId],
                $sortCounters,
                $referenceCities,
                $capitalHints
            );

            if ($mapped === null) {
                $skipped++;
                if (count($skippedExamples) < 20) {
                    $skippedExamples[] = ['reason' => 'invalid_name', 'row' => $row];
                }
                continue;
            }

            try {
                $insertStmt->execute([
                    ':id' => $mapped['id'],
                    ':name_ar' => $mapped['name_ar'],
                    ':name_en' => $mapped['name_en'],
                    ':code' => $mapped['code'],
                    ':country_id' => $mapped['country_id'],
                    ':is_capital' => $mapped['is_capital'],
                    ':latitude' => $mapped['latitude'],
                    ':longitude' => $mapped['longitude'],
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
        'message' => 'اكتمل نقل بيانات المدن.',
        'stats' => [
            'total_source_rows' => $totalRows,
            'processed' => $processed,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
        ],
        'skipped_samples' => $skippedExamples,
        'errors' => $errors,
    ];
}

function exportCitiesCsv(PDO $newDb, array $countryMeta): void
{
    $filename = 'cities_export_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    if ($output === false) {
        throw new RuntimeException('تعذر فتح مخرجات CSV.');
    }

    fputcsv($output, [
        'id',
        'country_id',
        'country_name_ar',
        'country_name_en',
        'name_ar',
        'name_en',
        'code',
        'is_capital',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
        'created_at',
        'updated_at',
    ]);

    $query = $newDb->query(
        'SELECT id, country_id, name_ar, name_en, code, is_capital, latitude, longitude,
                is_active, sort_order, created_at, updated_at
         FROM cities
         ORDER BY country_id, sort_order, id'
    );

    foreach ($query as $row) {
        $country = $countryMeta['by_id'][$row['country_id']] ?? null;
        fputcsv($output, [
            $row['id'],
            $row['country_id'],
            $country['name_ar'] ?? '',
            $country['name_en'] ?? '',
            $row['name_ar'],
            $row['name_en'],
            $row['code'],
            $row['is_capital'],
            $row['latitude'],
            $row['longitude'],
            $row['is_active'],
            $row['sort_order'],
            $row['created_at'],
            $row['updated_at'],
        ]);
    }

    fclose($output);
    exit;
}

function reportMissingCities(PDO $oldDb, array $countryMeta, int $limit): array
{
    $limit = max(1, $limit);
    $validCountryIds = array_map('intval', array_keys($countryMeta['by_id'] ?? []));

    if (empty($validCountryIds)) {
        return [
            'success' => true,
            'count' => 0,
            'rows' => [],
            'message' => 'لا توجد دول معرفة في القاعدة الجديدة.'
        ];
    }

    $placeholders = implode(',', array_fill(0, count($validCountryIds), '?'));
    $sql = "SELECT c.id, c.country_id, c.name, co.name AS country_name
            FROM cities c
            LEFT JOIN countries co ON co.id = c.country_id
            WHERE c.country_id NOT IN ($placeholders)
            ORDER BY c.country_id, c.id
            LIMIT ?";

    $stmt = $oldDb->prepare($sql);
    foreach ($validCountryIds as $index => $cid) {
        $stmt->bindValue($index + 1, $cid, PDO::PARAM_INT);
    }
    $stmt->bindValue(count($validCountryIds) + 1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll();

    return [
        'success' => true,
        'count' => count($rows),
        'rows' => $rows,
        'limit' => $limit,
    ];
}

function reportMissingByIds(PDO $oldDb, array $countryMeta, string $ids): array
{
    $idsList = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $ids) ?: [])));
    if (empty($idsList)) {
        return [
            'success' => false,
            'message' => 'لم يتم تمرير معرفات صالحة.'
        ];
    }

    $placeholders = implode(',', array_fill(0, count($idsList), '?'));
    $sql = "SELECT c.id, c.country_id, c.name, co.name AS country_name
            FROM cities c
            LEFT JOIN countries co ON co.id = c.country_id
            WHERE c.id IN ($placeholders)
            ORDER BY c.id";

    $stmt = $oldDb->prepare($sql);
    foreach ($idsList as $index => $id) {
        $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    return [
        'success' => true,
        'count' => count($rows),
        'rows' => $rows,
        'requested_ids' => $idsList,
    ];
}

function transformCityRow(
    array $row,
    array $country,
    array &$sortCounters,
    array $referenceCities,
    array $capitalHints
): ?array {
    $id = (int)$row['id'];
    $countryId = (int)$row['country_id'];

    $rawName = normalizeName((string)$row['name']);
    if ($rawName === '') {
        return null;
    }

    $hasArabic = containsArabic($rawName);

    $nameAr = $hasArabic ? $rawName : $rawName;
    $nameEn = $hasArabic ? transliterateName($rawName) : $rawName;

    if ($nameEn === '') {
        $nameEn = transliterateName($rawName);
    }
    if ($nameEn === '') {
        $nameEn = $rawName;
    }

    $refKey = $countryId . '::' . slugify($nameEn);
    $reference = $referenceCities[$refKey] ?? null;

    if ($reference) {
        if (!empty($reference['name_ar'])) {
            $nameAr = $reference['name_ar'];
        }
        if (!empty($reference['name_en'])) {
            $nameEn = $reference['name_en'];
        }
    }

    $sortCounters[$countryId] = $sortCounters[$countryId] ?? 0;
    if ($reference && !empty($reference['sort_order'])) {
        $sortCounters[$countryId] = max($sortCounters[$countryId], (int)$reference['sort_order']);
        $sortOrder = (int)$reference['sort_order'];
    } else {
        $sortOrder = ++$sortCounters[$countryId];
    }

    $isCapital = 0;
    if ($reference && isset($reference['is_capital'])) {
        $isCapital = (int)$reference['is_capital'];
    } else {
        $normalizedAr = mb_strtolower($nameAr, 'UTF-8');
        $normalizedEn = strtolower($nameEn);
        $hints = $capitalHints[$countryId] ?? [];
        foreach ($hints as $hint) {
            $hintLower = mb_strtolower($hint, 'UTF-8');
            if ($normalizedAr === $hintLower || $normalizedEn === $hintLower) {
                $isCapital = 1;
                break;
            }
        }
        if ($isCapital === 0 && $sortOrder === 1) {
            $isCapital = 1;
        }
    }

    $code = generateCityCode($country['code'] ?? '', $id);
    $isActive = empty($row['deleted_at']) ? 1 : 0;

    $latitude = $reference['latitude'] ?? null;
    $longitude = $reference['longitude'] ?? null;

    $createdAt = normalizeTimestamp($row['created_at'] ?? null);
    $updatedAt = normalizeTimestamp($row['updated_at'] ?? null) ?: $createdAt;

    return [
        'id' => $id,
        'name_ar' => $nameAr,
        'name_en' => $nameEn,
        'code' => $code,
        'country_id' => $countryId,
        'is_capital' => $isCapital,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'is_active' => $isActive,
        'sort_order' => $sortOrder,
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
    ];
}

function normalizeName(string $value): string
{
    $value = str_replace("''", "'", $value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    return trim($value);
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

function containsArabic(string $value): bool
{
    return (bool)preg_match('/\p{Arabic}/u', $value);
}

function transliterateName(string $value): string
{
    $latin = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($latin === false || $latin === '') {
        return '';
    }

    $latin = preg_replace('/[^A-Za-z0-9\s-]/', '', $latin) ?? $latin;
    $latin = preg_replace('/\s+/', ' ', $latin) ?? $latin;

    return trim($latin);
}

function slugify(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = mb_strtolower($value, 'UTF-8');
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($ascii === false || $ascii === '') {
        $ascii = $value;
    }

    $ascii = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? $ascii;
    $ascii = trim($ascii, '-');

    return $ascii;
}

function generateCityCode(string $countryCode, int $cityId): string
{
    $prefix = $countryCode !== '' ? strtoupper($countryCode) : 'C' . $cityId;
    return $prefix . '-' . str_pad((string)$cityId, 4, '0', STR_PAD_LEFT);
}
