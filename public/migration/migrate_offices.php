<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

$action = $_POST['action'] ?? $_GET['action'] ?? 'analyze';

if ($action !== 'export_csv') {
    header('Content-Type: application/json; charset=utf-8');
}

$config = [
    'host'   => $_POST['db_host'] ?? $_GET['db_host'] ?? '127.0.0.1',
    'port'   => (int)($_POST['db_port'] ?? $_GET['db_port'] ?? 8889),
    'user'   => $_POST['db_user'] ?? $_GET['db_user'] ?? 'root',
    'pass'   => $_POST['db_pass'] ?? $_GET['db_pass'] ?? 'root',
    'old_db' => $_POST['old_db'] ?? $_GET['old_db'] ?? 'platinum',
    'new_db' => $_POST['new_db'] ?? $_GET['new_db'] ?? 'platinum_new',
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
                echo json_encode(previewOffices($oldDb, $limit), JSON_UNESCAPED_UNICODE);
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

    $oldNationalityIds = $oldDb->query('SELECT DISTINCT nationality_id FROM offices WHERE nationality_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $newNationalityIds = $newDb->query('SELECT id FROM nationalities')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $nationalityGaps = array_values(array_map('intval', array_diff(array_map('intval', $oldNationalityIds), array_map('intval', $newNationalityIds))));

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
        'nationality_gaps_in_target' => $nationalityGaps,
        'missing_nationality_ids_for_missing_offices' => array_values(array_map('intval', array_filter($missingNationalitiesForOffices))),
            'translation_orphans' => array_map('intval', array_filter($translationOrphans)),
        ];
    }

    function previewOffices(PDO $oldDb, int $limit): array
    {
        $limit = max(1, $limit);
        $stmt = $oldDb->prepare('SELECT * FROM offices ORDER BY id LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $offices = $stmt->fetchAll() ?: [];

        $translations = fetchTranslations($oldDb, array_column($offices, 'id'));

        $preview = [];
        foreach ($offices as $office) {
            $mapped = transformOfficeRow($office);
            $preview[] = [
                'source' => $office,
                'mapped' => $mapped,
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

        $translations = fetchTranslations($oldDb, array_column($offices, 'id'));

        $availableColumns = getTableColumns($newDb, 'recruitment_offices');
        $desiredColumns = [
            'id', 'nationality_id', 'status', 'phone_office', 'license_number', 'street',
            'manager_name', 'manager_phone', 'manager_email', 'password',
            'bank_address', 'bank_name', 'bank_account_name', 'bank_swift_code', 'bank_iban',
            'bank_account', 'cost_of_dollar', 'cost_of_other', 'created_at', 'updated_at'
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
                $mapped = transformOfficeRow($office);

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
                        $errors[] = ['error' => 'المعرف مرتبط بجنسية غير موجودة في القاعدة الجديدة', 'row' => $office];
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

    function transformOfficeRow(array $office): ?array
    {
        $id = (int)($office['id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

    $nationalityId = isset($office['nationality_id']) ? (int)$office['nationality_id'] : null;
    if ($nationalityId !== null && $nationalityId <= 0) {
        $nationalityId = null;
    }
        $status = ((int)($office['ban'] ?? 0) === 1) ? 'dis_active' : 'active';
        $phone = trimString($office['office_phone'] ?? null);
        $license = trimString($office['office_commercial_id'] ?? null);
        $street = trimString($office['office_street'] ?? null);
        $bankAccountName = trimString($office['office_bank_account_name'] ?? null);
        $bankSwift = trimString($office['office_bank_code'] ?? null);

        $createdAt = normalizeTimestamp($office['created_at'] ?? null);
        $updatedAt = normalizeTimestamp($office['updated_at'] ?? null) ?: $createdAt;

        return [
            'id' => $id,
            'nationality_id' => $nationalityId,
            'status' => $status,
            'phone_office' => $phone,
            'license_number' => $license,
            'street' => $street,
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

    function getTableColumns(PDO $pdo, string $table): array
    {
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table");
        $stmt->bindValue(':table', $table, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
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
