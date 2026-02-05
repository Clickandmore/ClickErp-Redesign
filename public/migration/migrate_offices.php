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

        switch ($action) {
            case 'export_csv':
                exportOfficesCsv($newDb);
                return;

            case 'analyze':
                echo json_encode(analyzeOffices($oldDb, $newDb), JSON_UNESCAPED_UNICODE);
                break;

            case 'preview':
                $limit = (int)($_POST['limit'] ?? $_GET['limit'] ?? 40);
                echo json_encode(previewOffices($oldDb, $newDb, $limit), JSON_UNESCAPED_UNICODE);
                break;

            case 'migrate':
                $chunkSize = max(50, (int)($_POST['chunk'] ?? $_GET['chunk'] ?? 500));
                echo json_encode(migrateOffices($oldDb, $newDb, $chunkSize), JSON_UNESCAPED_UNICODE);
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

    function analyzeOffices(PDO $oldDb, PDO $newDb): array
    {
        $oldStats = $oldDb->query('SELECT COUNT(*) AS total, SUM(CASE WHEN ban = 1 THEN 1 ELSE 0 END) AS banned FROM offices')->fetch() ?: ['total' => 0, 'banned' => 0];
        $newStats = $newDb->query('SELECT COUNT(*) AS total, SUM(CASE WHEN status = "dis_active" THEN 1 ELSE 0 END) AS inactive FROM recruitment_offices')->fetch() ?: ['total' => 0, 'inactive' => 0];

        $oldIds = $oldDb->query('SELECT id FROM offices ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $newIds = $newDb->query('SELECT id FROM recruitment_offices ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $missingIds = array_values(array_map('intval', array_diff(array_map('intval', $oldIds), array_map('intval', $newIds))));
        $extraIds = array_values(array_map('intval', array_diff(array_map('intval', $newIds), array_map('intval', $oldIds))));

        $mappingData = buildNationalityMapping($oldDb, $newDb);
        $nationalityMap = $mappingData['map'];

        $oldNationalityIds = $oldDb->query('SELECT DISTINCT nationality_id FROM offices WHERE nationality_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $unresolvedNationalityIds = [];
        foreach ($oldNationalityIds as $oldId) {
            $oldId = (int)$oldId;
            if (!isset($nationalityMap[$oldId])) {
                $unresolvedNationalityIds[$oldId] = $mappingData['old'][$oldId]['name'] ?? '';
            }
        }

        $missingNationalitiesForOffices = [];
        if ($missingIds) {
            $placeholders = implode(',', array_fill(0, count($missingIds), '?'));
            $stmt = $oldDb->prepare("SELECT DISTINCT nationality_id FROM offices WHERE id IN ($placeholders)");
            foreach ($missingIds as $index => $id) {
                $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
            }
            $stmt->execute();
            $missingNationalitiesForOffices = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        }

        $translationGapsStmt = $oldDb->query('SELECT DISTINCT t.office_id FROM office_translations t LEFT JOIN offices o ON o.id = t.office_id WHERE o.id IS NULL');
        $translationOrphans = $translationGapsStmt ? $translationGapsStmt->fetchAll(PDO::FETCH_COLUMN) : [];

        return [
            'success' => true,
            'old_stats' => [
                'total' => (int)$oldStats['total'],
                'banned' => (int)$oldStats['banned'],
                'active' => (int)$oldStats['total'] - (int)$oldStats['banned'],
            ],
            'new_stats' => [
                'total' => (int)$newStats['total'],
                'inactive' => (int)$newStats['inactive'],
                'active' => (int)$newStats['total'] - (int)$newStats['inactive'],
            ],
            'missing_office_ids' => $missingIds,
            'extra_office_ids' => $extraIds,
            'nationality_mapping_resolved' => count($nationalityMap),
            'unresolved_nationalities' => array_map(
                fn($id, $name) => ['old_id' => $id, 'name' => $name],
                array_keys($unresolvedNationalityIds),
                $unresolvedNationalityIds
            ),
            'missing_nationality_ids_for_missing_offices' => array_values(array_map('intval', array_filter($missingNationalitiesForOffices))),
            'translation_orphans' => array_map('intval', array_filter($translationOrphans)),
        ];
    }

    function previewOffices(PDO $oldDb, PDO $newDb, int $limit): array
    {
        $limit = max(1, $limit);
        $stmt = $oldDb->prepare('SELECT * FROM offices ORDER BY id LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $offices = $stmt->fetchAll() ?: [];

        $mappingData = buildNationalityMapping($oldDb, $newDb);
        $nationalityMap = $mappingData['map'];
        $translations = fetchTranslations($oldDb, array_column($offices, 'id'));

        $preview = [];
        foreach ($offices as $office) {
            $originalNationalityId = isset($office['nationality_id']) ? (int)$office['nationality_id'] : null;
            $mappedNationalityId = $originalNationalityId !== null ? ($nationalityMap[$originalNationalityId] ?? null) : null;
            $mapped = transformOfficeRow($office, $mappedNationalityId);
            $preview[] = [
                'source' => $office,
                'mapped' => $mapped,
                'nationality_mapping' => [
                    'old_id' => $originalNationalityId,
                    'new_id' => $mappedNationalityId,
                    'old_name' => $originalNationalityId !== null ? ($mappingData['old'][$originalNationalityId]['name'] ?? null) : null,
                ],
                'translations' => $translations[$office['id']] ?? [],
            ];
        }

        return [
            'success' => true,
            'preview_count' => count($preview),
            'data' => $preview,
        ];
    }

    function migrateOffices(PDO $oldDb, PDO $newDb, int $chunkSize): array
    {
        $offices = $oldDb->query('SELECT * FROM offices ORDER BY id')->fetchAll() ?: [];
        if (!$offices) {
            return [
                'success' => true,
                'message' => 'لا توجد بيانات لنقلها من جدول offices.',
                'stats' => ['inserted' => 0, 'updated' => 0, 'skipped' => 0],
            ];
        }

        ensureRecruitmentOfficeColumns($newDb);

        $mappingData = buildNationalityMapping($oldDb, $newDb);
        $nationalityMap = $mappingData['map'];
        $unmappedNationalities = [];
        $translations = fetchTranslations($oldDb, array_column($offices, 'id'));

        $availableColumns = getTableColumns($newDb, 'recruitment_offices');
        $desiredColumns = [
            'id', 'nationality_id', 'status', 'phone_office', 'license_number', 'street',
            'manager_name', 'manager_phone', 'manager_email', 'password',
            'bank_address', 'bank_name', 'bank_account_name', 'bank_swift_code', 'bank_iban',
            'bank_account', 'cost_of_dollar', 'cost_of_other', 'office_country', 'office_city',
            'office_notes', 'created_at', 'updated_at'
        ];
        $columnsToUse = array_values(array_intersect($desiredColumns, $availableColumns));
        if (!in_array('id', $columnsToUse, true)) {
            array_unshift($columnsToUse, 'id');
        }

        $insertSql = buildUpsertQuery('recruitment_offices', $columnsToUse);
        $officeStmt = $newDb->prepare($insertSql);

        $translationColumns = ['id', 'rec_office_id', 'title', 'locale', 'created_at', 'updated_at'];
        $translationSql = buildUpsertQuery('recruitment_office_translations', $translationColumns, ['id']);
        $translationStmt = $newDb->prepare($translationSql);

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $processed = 0;

        $chunkSize = max(1, $chunkSize);

        $newDb->beginTransaction();

        try {
            foreach ($offices as $office) {
                $processed++;
                $originalNationalityId = isset($office['nationality_id']) ? (int)$office['nationality_id'] : null;
                $mappedNationalityId = $originalNationalityId !== null ? ($nationalityMap[$originalNationalityId] ?? null) : null;

                if ($originalNationalityId !== null && $mappedNationalityId === null) {
                    $skipped++;
                    if (count($errors) < 20) {
                        $errors[] = [
                            'error' => 'تعذر مطابقة جنسية المكتب مع القاعدة الجديدة',
                            'row' => [
                                'office_id' => $office['id'],
                                'nationality_id_old' => $originalNationalityId,
                                'nationality_name_old' => $mappingData['old'][$originalNationalityId]['name'] ?? null,
                            ],
                        ];
                    }
                    if ($originalNationalityId !== null) {
                        $unmappedNationalities[$originalNationalityId] = $mappingData['old'][$originalNationalityId]['name'] ?? '';
                    }
                    continue;
                }

                $mapped = transformOfficeRow($office, $mappedNationalityId);

                if ($mapped === null) {
                    $skipped++;
                    if (count($errors) < 20) {
                        $errors[] = ['error' => 'بيانات المكتب غير صالحة', 'row' => $office];
                    }
                    continue;
                }

                if (empty($mapped['nationality_id'])) {
                    $skipped++;
                    if (count($errors) < 20) {
                        $errors[] = ['error' => 'الجنسية غير محددة بعد التحويل', 'row' => $office];
                    }
                    continue;
                }

                $params = [];
                foreach ($columnsToUse as $column) {
                    $params[":" . $column] = $mapped[$column] ?? null;
                }

                $officeStmt->execute($params);
                $affected = $officeStmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                }

                if (!empty($translations[$office['id']])) {
                    foreach ($translations[$office['id']] as $translation) {
                        $translationParams = [];
                        foreach ($translationColumns as $column) {
                            $translationParams[":" . $column] = $translation[$column] ?? null;
                        }
                        $translationStmt->execute($translationParams);
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

        $profilesSummary = migrateOfficeFiles($oldDb, $newDb);

        return [
            'success' => true,
            'message' => 'اكتمل نقل بيانات المكاتب الخارجية.',
            'stats' => [
                'total_source_rows' => count($offices),
                'processed' => $processed,
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
            ],
            'errors' => $errors,
            'unmapped_nationalities' => array_map(
                fn($id, $name) => ['old_id' => (int)$id, 'name' => $name],
                array_keys($unmappedNationalities),
                $unmappedNationalities
            ),
            'nationality_mapping_count' => count($nationalityMap),
            'profiles_migration' => $profilesSummary,
        ];
    }

    function exportOfficesCsv(PDO $newDb): void
    {
        $filename = 'recruitment_offices_export_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            throw new RuntimeException('تعذر فتح مخرجات CSV.');
        }

        fputcsv($output, [
            'id', 'nationality_id', 'status', 'phone_office', 'license_number', 'street',
            'manager_name', 'manager_phone', 'manager_email', 'bank_address', 'bank_name',
            'bank_account_name', 'bank_swift_code', 'bank_iban', 'bank_account',
            'cost_of_dollar', 'cost_of_other', 'created_at', 'updated_at'
        ]);

        $query = $newDb->query('SELECT id, nationality_id, status, phone_office, license_number, street,
            manager_name, manager_phone, manager_email, bank_address, bank_name, bank_account_name,
            bank_swift_code, bank_iban, bank_account, cost_of_dollar, cost_of_other, created_at, updated_at
            FROM recruitment_offices ORDER BY id');

        foreach ($query as $row) {
            fputcsv($output, [
                $row['id'],
                $row['nationality_id'],
                $row['status'],
                $row['phone_office'],
                $row['license_number'],
                $row['street'],
                $row['manager_name'],
                $row['manager_phone'],
                $row['manager_email'],
                $row['bank_address'],
                $row['bank_name'],
                $row['bank_account_name'],
                $row['bank_swift_code'],
                $row['bank_iban'],
                $row['bank_account'],
                $row['cost_of_dollar'],
                $row['cost_of_other'],
                $row['created_at'],
                $row['updated_at'],
            ]);
        }

        fclose($output);
        exit;
    }

    function transformOfficeRow(array $office, ?int $nationalityId = null): ?array
    {
        $id = (int)($office['id'] ?? 0);
        if ($id <= 0) {
            return null;
        }
        if ($nationalityId !== null) {
            $nationalityId = (int)$nationalityId;
            if ($nationalityId <= 0) {
                $nationalityId = null;
            }
        }

        $status = ((int)($office['ban'] ?? 0) === 1) ? 'dis_active' : 'active';
        $phone = trimString($office['office_phone'] ?? null);
        $license = trimString($office['office_commercial_id'] ?? null);

        $country = trimString($office['office_country'] ?? null);
        $city = trimString($office['office_city'] ?? null);
        $street = trimString($office['office_street'] ?? null);

        $bankAccountName = trimString($office['office_bank_account_name'] ?? null);
        $bankSwift = trimString($office['office_bank_code'] ?? null);
        $notes = trimString($office['office_notes'] ?? null);

        $createdAt = normalizeTimestamp($office['created_at'] ?? null);
        $updatedAt = normalizeTimestamp($office['updated_at'] ?? null) ?: $createdAt;

        return [
            'id' => $id,
            'nationality_id' => $nationalityId,
            'status' => $status,
            'phone_office' => $phone,
            'license_number' => $license,
            'street' => $street,
            'office_country' => $country,
            'office_city' => $city,
            'manager_name' => null,
            'manager_phone' => null,
            'manager_email' => null,
            'password' => null,
            'bank_address' => null,
            'bank_name' => null,
            'bank_account_name' => $bankAccountName,
            'bank_swift_code' => $bankSwift,
            'bank_iban' => null,
            'bank_account' => null,
            'cost_of_dollar' => null,
            'cost_of_other' => null,
            'office_notes' => $notes,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    function fetchTranslations(PDO $oldDb, array $officeIds): array
    {
        $officeIds = array_values(array_filter(array_map('intval', $officeIds)));
        if (!$officeIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($officeIds), '?'));
        $stmt = $oldDb->prepare("SELECT id, office_id, name, lang, created_at, updated_at FROM office_translations WHERE office_id IN ($placeholders)");
        foreach ($officeIds as $index => $officeId) {
            $stmt->bindValue($index + 1, $officeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['office_id']][] = transformTranslationRow($row);
        }

        return $result;
    }

    function transformTranslationRow(array $row): array
    {
        $locale = strtolower(trim((string)($row['lang'] ?? '')));
        if ($locale === '') {
            $locale = 'ar';
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'rec_office_id' => (int)($row['office_id'] ?? 0),
            'title' => trimString($row['name'] ?? null),
            'locale' => $locale,
            'created_at' => normalizeTimestamp($row['created_at'] ?? null),
            'updated_at' => normalizeTimestamp($row['updated_at'] ?? null),
        ];
    }

    function buildNationalityMapping(PDO $oldDb, PDO $newDb): array
    {
        $oldRows = $oldDb->query('SELECT id, name, code, key_code FROM nationalities')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $oldData = [];
        foreach ($oldRows as $row) {
            $oldData[(int)$row['id']] = $row;
        }

        $stmt = $newDb->query('SELECT n.id, n.phone_code, t.title, t.sub_title FROM nationalities n LEFT JOIN nationalities_translations t ON t.nationality_id = n.id');
        $codeIndex = [];
        $nameIndex = [];
        $nameKeys = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $newId = (int)$row['id'];
            $code = strtoupper(trim((string)($row['phone_code'] ?? '')));
            if ($code !== '') {
                $codeIndex[$code][] = $newId;
            }

            foreach (['title', 'sub_title'] as $column) {
                $value = $row[$column] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                $normalized = normalizeName($value);
                if ($normalized === '') {
                    continue;
                }
                if (!isset($nameIndex[$normalized])) {
                    $nameIndex[$normalized] = [];
                    $nameKeys[] = $normalized;
                }
                $nameIndex[$normalized][$newId] = true;
            }
        }

        $mapping = [];
        foreach ($oldData as $oldId => $oldRow) {
            $matchNewId = null;

            $codesToTry = [];
            if (!empty($oldRow['code'])) {
                $codesToTry[] = strtoupper(trim((string)$oldRow['code']));
            }
            if (!empty($oldRow['key_code'])) {
                $codesToTry[] = strtoupper(trim((string)$oldRow['key_code']));
            }

            foreach ($codesToTry as $code) {
                if ($code === '') {
                    continue;
                }
                if (isset($codeIndex[$code])) {
                    $matchNewId = $codeIndex[$code][0];
                    break;
                }
            }

            if ($matchNewId === null) {
                $normalizedOldName = normalizeName($oldRow['name'] ?? '');
                if ($normalizedOldName !== '' && isset($nameIndex[$normalizedOldName])) {
                    $matchNewId = array_key_first($nameIndex[$normalizedOldName]);
                } elseif ($normalizedOldName !== '') {
                    $bestScore = 0.0;
                    $bestId = null;
                    foreach ($nameKeys as $normalizedNew) {
                        similar_text($normalizedOldName, $normalizedNew, $percent);
                        if ($percent > $bestScore) {
                            $bestScore = $percent;
                            $bestId = array_key_first($nameIndex[$normalizedNew]);
                        }
                    }
                    if ($bestScore >= 70) {
                        $matchNewId = $bestId;
                    }
                }
            }

            if ($matchNewId !== null) {
                $mapping[$oldId] = $matchNewId;
            }
        }

        return [
            'map' => $mapping,
            'old' => $oldData,
        ];
    }

    function normalizeName(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = mb_strtolower($value, 'UTF-8');
        $value = strtr($value, [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ة' => 'ا',
            'ى' => 'ي',
            'ؤ' => 'و',
            'ئ' => 'ي',
            'ﻻ' => 'لا',
        ]);
        $value = preg_replace('/\s+/u', '', $value);
        $value = str_replace(['-', '_', '–', '—', ',', '.', '!', '?', '؟', '،', '؛', '\'', '"', '(', ')', '[', ']', '{', '}', 'ـ'], '', $value);
        return $value;
    }

    function getTableColumns(PDO $pdo, string $table): array
    {
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table");
        $stmt->bindValue(':table', $table, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    function ensureRecruitmentOfficeColumns(PDO $pdo): void
    {
        $columns = array_map('strtolower', getTableColumns($pdo, 'recruitment_offices'));

        $needsCountry = !in_array('office_country', $columns, true);
        $needsCity = !in_array('office_city', $columns, true);
        $needsNotes = !in_array('office_notes', $columns, true);

        if (!$needsCountry && !$needsCity && !$needsNotes) {
            return;
        }

        $clauses = [];
        if ($needsCountry) {
            $clauses[] = "ADD COLUMN `office_country` varchar(255) NULL AFTER `street`";
        }

        if ($needsCity) {
            $afterColumn = (!$needsCountry && in_array('office_country', $columns, true)) ? 'office_country' : ($needsCountry ? 'office_country' : 'street');
            $clauses[] = "ADD COLUMN `office_city` varchar(255) NULL AFTER `{$afterColumn}`";
        }

        if ($needsNotes) {
            $clauses[] = "ADD COLUMN `office_notes` text NULL AFTER `bank_account`";
        }

        $sql = 'ALTER TABLE `recruitment_offices` ' . implode(', ', $clauses);
        $pdo->exec($sql);
    }

    function migrateOfficeFiles(PDO $oldDb, PDO $newDb): array
    {
        $rows = $oldDb->query('SELECT * FROM office_profiles ORDER BY id')->fetchAll() ?: [];
        if (!$rows) {
            return [
                'total_source_rows' => 0,
                'inserted' => 0,
                'updated' => 0,
                'skills_links' => 0,
            ];
        }

        $insertSql = buildUpsertQuery('office_files', [
            'id',
            'recruitment_office_id',
            'office_file_name',
            'job_title_id',
            'religion_id',
            'marital_status',
            'contract_duration',
            'passport_validity_period',
            'salary',
            'recruitment_price',
            'languages',
            'passport_issuance_location',
            'education',
            'created_at',
            'updated_at',
        ]);
        $fileStmt = $newDb->prepare($insertSql);

        $deleteSkillStmt = $newDb->prepare('DELETE FROM office_file_skills WHERE office_file_id = :id');
        $insertSkillStmt = $newDb->prepare('INSERT INTO office_file_skills (office_file_id, skill_id, created_at, updated_at) VALUES (:file_id, :skill_id, NOW(), NOW())');

        $inserted = 0;
        $updated = 0;
        $skillLinks = 0;

        foreach ($rows as $row) {
            $mapped = transformOfficeProfileRow($row);

            if (empty($mapped['file'][':recruitment_office_id']) || empty($mapped['file'][':job_title_id']) || empty($mapped['file'][':religion_id'])) {
                continue;
            }

            $fileStmt->execute($mapped['file']);
            $affected = $fileStmt->rowCount();
            if ($affected === 1) {
                $inserted++;
            } elseif ($affected === 2) {
                $updated++;
            }

            $deleteSkillStmt->execute([':id' => $row['id']]);
            foreach ($mapped['skills'] as $skillId) {
                $insertSkillStmt->execute([
                    ':file_id' => $row['id'],
                    ':skill_id' => $skillId,
                ]);
                $skillLinks++;
            }
        }

        return [
            'total_source_rows' => count($rows),
            'inserted' => $inserted,
            'updated' => $updated,
            'skills_links' => $skillLinks,
        ];
    }

    function transformOfficeProfileRow(array $row): array
    {
        $id = (int)($row['id'] ?? 0);
        $officeId = (int)($row['office_id'] ?? 0);
        $jobId = (int)($row['job_id'] ?? 0);
        $religionId = (int)($row['religion_id'] ?? 0);

        $profileName = trimString($row['profile_name'] ?? null) ?? 'Office File';
        $maritalStatus = normalizeStatuses($row['social_statuses'] ?? null);
        $contractDuration = trimString($row['duration_contract'] ?? '') ?? '0';
        $passportValidity = trimString($row['passport_validity_period'] ?? '') ?? '0';
        $salary = trimString((string)($row['salary'] ?? '0')) ?? '0';
        $recruitmentPrice = '0';

        $languages = normalizeJsonField($row['languages'] ?? null);
        $education = normalizeEducationField($row['learn'] ?? null);
        $passportPlace = normalizePassportPlace($row['passport_place'] ?? null);

        $createdAt = normalizeTimestamp($row['created_at'] ?? null);
        $updatedAt = normalizeTimestamp($row['updated_at'] ?? null) ?: $createdAt;

        $skills = normalizeSkillsField($row['skills'] ?? null);

        return [
            'file' => [
                ':id' => $id,
                ':recruitment_office_id' => $officeId ?: null,
                ':office_file_name' => $profileName,
                ':job_title_id' => $jobId ?: null,
                ':religion_id' => $religionId ?: null,
                ':marital_status' => $maritalStatus,
                ':contract_duration' => $contractDuration,
                ':passport_validity_period' => $passportValidity,
                ':salary' => $salary,
                ':recruitment_price' => $recruitmentPrice,
                ':languages' => $languages,
                ':passport_issuance_location' => $passportPlace,
                ':education' => $education,
                ':created_at' => $createdAt,
                ':updated_at' => $updatedAt,
            ],
            'skills' => $skills,
        ];
    }

    function normalizeStatuses(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'unknown';
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $result = implode(',', array_filter(array_map('trim', $decoded), fn($item) => $item !== ''));
            return $result !== '' ? $result : 'unknown';
        }

        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : 'unknown';
    }

    function normalizeJsonField($value): string
    {
        if ($value === null) {
            return json_encode([]);
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return json_encode([]);
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded);
            }

            $parts = array_filter(array_map('trim', explode(',', $value)), fn($item) => $item !== '');
            return json_encode(array_values($parts));
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return json_encode([$value]);
    }

    function normalizeEducationField($value): string
    {
        if ($value === null || trim((string)$value) === '') {
            return json_encode([]);
        }

        $value = str_replace(['[', ']'], '', (string)$value);
        $parts = array_filter(array_map('trim', explode(',', $value)), fn($item) => $item !== '');
        return json_encode(array_values($parts));
    }

    function normalizePassportPlace($value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return json_encode([]);
        }

        return json_encode([$value]);
    }

    function normalizeSkillsField($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string)$value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map('intval', $decoded), fn($item) => $item > 0));
        }

        return [];
    }

    function buildUpsertQuery(string $table, array $columns, array $excludeUpdate = []): string
    {
        $columns = array_values(array_unique($columns));
        $quotedColumns = array_map(fn($col) => "`$col`", $columns);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $updateColumns = array_filter($columns, fn($col) => !in_array($col, $excludeUpdate, true) && $col !== 'id');
        $updateAssignments = array_map(fn($col) => "`$col` = VALUES(`$col`)", $updateColumns);

        $sql = sprintf(
            "INSERT INTO `%s` (%s) VALUES (%s)",
            $table,
            implode(', ', $quotedColumns),
            implode(', ', $placeholders)
        );

        if ($updateAssignments) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateAssignments);
        }

        return $sql;
    }

    function normalizeTimestamp(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        return $value;
    }

    function trimString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }
