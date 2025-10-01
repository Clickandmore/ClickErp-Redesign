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
        case 'analyze':
            echo json_encode(analyzeMaids($oldDb, $newDb), JSON_UNESCAPED_UNICODE);
            break;

        case 'migrate':
            $chunkSize = max(50, (int)($_POST['chunk'] ?? $_GET['chunk'] ?? 300));
            echo json_encode(migrateMaids($oldDb, $newDb, $chunkSize), JSON_UNESCAPED_UNICODE);
            break;

        case 'missing_details':
            $mappings = buildMaidsMappings($oldDb, $newDb);
            $missing = collectMissingDetails($oldDb, $newDb, $mappings);
            echo json_encode([
                'success' => true,
                'details' => $missing,
                'message' => 'تم استخراج القيم المفقودة.'
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'إجراء غير معروف، استخدم analyze أو migrate'
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

function analyzeMaids(PDO $oldDb, PDO $newDb): array
{
    $counts = [
        'old_total' => (int)$oldDb->query('SELECT COUNT(*) FROM cvs')->fetchColumn(),
        'new_total' => (int)$newDb->query('SELECT COUNT(*) FROM maids')->fetchColumn(),
    ];

    $statusBreakdown = $oldDb->query('SELECT cv_status, COUNT(*) AS total FROM cvs GROUP BY cv_status ORDER BY cv_status')->fetchAll();

    $nationalityIds = $oldDb->query('SELECT DISTINCT national_id FROM cvs WHERE national_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $availableNationalities = $newDb->query('SELECT id FROM nationalities')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $missingNationalities = array_values(array_diff(array_map('intval', $nationalityIds), array_map('intval', $availableNationalities)));

    $religionIds = $oldDb->query('SELECT DISTINCT region_id FROM cvs WHERE region_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $availableReligions = $newDb->query('SELECT id FROM religions')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $missingReligions = array_values(array_diff(array_map('intval', $religionIds), array_map('intval', $availableReligions)));

    $occupationIds = $oldDb->query('SELECT DISTINCT occuption_id FROM cvs WHERE occuption_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $occupationMap = buildOccupationMapping($oldDb, $newDb);
    $missingOccupations = [];
    foreach ($occupationIds as $occId) {
        $occId = (int)$occId;
        if (!isset($occupationMap['job_titles'][$occId])) {
            $missingOccupations[] = $occId;
        }
    }

    $languageIds = [];
    foreach ($oldDb->query('SELECT lang_id FROM cvs WHERE lang_id IS NOT NULL AND lang_id <> ""') as $row) {
        $ids = normalizeJsonArray($row['lang_id']);
        foreach ($ids as $id) {
            $languageIds[(int)$id] = true;
        }
    }
    $languageMap = buildLanguageMap($oldDb, $newDb);
    $missingLanguages = array_values(array_diff(array_keys($languageIds), array_keys($languageMap)));

    $officeNames = [];
    foreach ($oldDb->query('SELECT DISTINCT office FROM cvs WHERE office IS NOT NULL AND office <> ""') as $row) {
        $officeNames[$row['office']] = true;
    }
    $officeMap = buildOfficeMap($newDb);
    $unmatchedOffices = [];
    foreach (array_keys($officeNames) as $officeName) {
        if (!resolveOfficeId($officeName, $officeMap)) {
            $unmatchedOffices[] = $officeName;
        }
    }

    $sample = $oldDb->query('SELECT id, cvs_name, national_id, region_id, occuption_id, cv_status, created_at FROM cvs ORDER BY id ASC LIMIT 5')->fetchAll();

    return [
        'success' => true,
        'stats' => $counts,
        'status_breakdown' => $statusBreakdown,
        'missing_nationalities' => $missingNationalities,
        'missing_religions' => $missingReligions,
        'unmapped_occupations' => $missingOccupations,
        'unmapped_languages' => $missingLanguages,
        'unmatched_offices' => array_slice($unmatchedOffices, 0, 20),
        'sample' => $sample,
    ];
}

function migrateMaids(PDO $oldDb, PDO $newDb, int $chunkSize): array
{
    $mappings = buildMaidsMappings($oldDb, $newDb);

    $totalSource = (int)$oldDb->query('SELECT COUNT(*) FROM cvs')->fetchColumn();
    if ($totalSource === 0) {
        return [
            'success' => true,
            'message' => 'لا توجد بيانات لنقلها من جدول cvs.',
            'stats' => ['processed' => 0, 'inserted' => 0, 'updated' => 0, 'skipped' => 0],
            'profiles_migration' => [
                'total_source_rows' => 0,
                'inserted' => 0,
                'updated' => 0,
                'skills_links' => 0,
            ],
            'errors' => [],
        ];
    }

    $maidColumns = fetchTableColumns($newDb, 'maids');
    $maidColumnsLower = array_map('strtolower', $maidColumns);

    $allowedColumns = array_intersect(
        [
            'id', 'name', 'nationality_id', 'passport_num', 'price', 'salary', 'religion_id', 'age', 'birth_date',
            'cv_file', 'available', 'is_pinned', 'available_at', 'status', 'is_show', 'created_at', 'updated_at',
            'experience_years', 'experience_status', 'cv_preview', 'office_id', 'image', 'height', 'weight',
            'id_number', 'id_expiry_date', 'skills', 'languages', 'experience_country', 'video_preview',
            'childrens_count', 'education', 'duration', 'st_code', 'int_code'
        ],
        $maidColumns
    );

    $insertSql = buildUpsertQuery('maids', $allowedColumns);
    $maidStmt = $newDb->prepare($insertSql);

    $deleteJobTitleStmt = $newDb->prepare('DELETE FROM maid_job_titles WHERE maid_id = :maid_id');
    $insertJobTitleStmt = $newDb->prepare('INSERT INTO maid_job_titles (maid_id, job_title_id, created_at, updated_at) VALUES (:maid_id, :job_title_id, NOW(), NOW())');

    $deleteJobTypeStmt = $newDb->prepare('DELETE FROM maid_jobs WHERE maid_id = :maid_id');
    $insertJobTypeStmt = $newDb->prepare('INSERT INTO maid_jobs (maid_id, job_type_id, created_at, updated_at) VALUES (:maid_id, :job_type_id, NOW(), NOW())');

    $translationInsertSql = "INSERT INTO maid_translations (maid_id, locale, languages, marital_status, created_at, updated_at)
        VALUES (:maid_id, :locale, :languages, :marital_status, NOW(), NOW())
        ON DUPLICATE KEY UPDATE languages = VALUES(languages), marital_status = VALUES(marital_status), updated_at = VALUES(updated_at)";
    $translationStmt = $newDb->prepare($translationInsertSql);

    $selectCvStmt = $oldDb->query('SELECT * FROM cvs ORDER BY id');
    $processed = 0;
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    $jobTitleLinks = 0;
    $jobTypeLinks = 0;
    $errors = [];
    $warnings = [];
    $defaultNationalityUsed = 0;
    $defaultReligionUsed = 0;

    $newDb->beginTransaction();

    try {
        while ($cv = $selectCvStmt->fetch()) {
            $processed++;
            $result = transformCvRow($cv, $mappings, $allowedColumns);

            if ($result === null) {
                $skipped++;
                if (count($errors) < 25) {
                    $errors[] = ['id' => $cv['id'], 'reason' => 'بيانات اساسية مفقودة (الجنسية أو الديانة)'];
                }
                continue;
            }

            if (!empty($result['errors']) && count($errors) < 25) {
                $errors[] = ['id' => $cv['id'], 'reason' => implode(' | ', $result['errors'])];
            }

            if (!empty($result['warnings'])) {
                foreach ($result['warnings'] as $warning) {
                    $warnings[] = ['id' => $cv['id'], 'message' => $warning];
                }
            }

            if (!empty($result['used_defaults']['nationality'])) {
                $defaultNationalityUsed++;
            }

            if (!empty($result['used_defaults']['religion'])) {
                $defaultReligionUsed++;
            }

            $maidStmt->execute($result['maid']);
            $affected = $maidStmt->rowCount();
            if ($affected === 1) {
                $inserted++;
            } elseif ($affected === 2) {
                $updated++;
            }

            $maidId = $result['maid'][':id'];

            $deleteJobTitleStmt->execute([':maid_id' => $maidId]);
            foreach ($result['job_titles'] as $jobTitleId) {
                $insertJobTitleStmt->execute([
                    ':maid_id' => $maidId,
                    ':job_title_id' => $jobTitleId,
                ]);
                $jobTitleLinks++;
            }

            $deleteJobTypeStmt->execute([':maid_id' => $maidId]);
            foreach ($result['job_types'] as $jobTypeId) {
                $insertJobTypeStmt->execute([
                    ':maid_id' => $maidId,
                    ':job_type_id' => $jobTypeId,
                ]);
                $jobTypeLinks++;
            }

            foreach ($result['translations'] as $translation) {
                $translationStmt->execute([
                    ':maid_id' => $maidId,
                    ':locale' => $translation['locale'],
                    ':languages' => $translation['languages'],
                    ':marital_status' => $translation['marital_status'],
                ]);
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

    $profilesSummary = [
        'total_source_rows' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skills_links' => 0,
        'job_title_links' => $jobTitleLinks,
        'job_type_links' => $jobTypeLinks,
    ];

    return [
        'success' => true,
        'message' => 'تم نقل بيانات السير الذاتية إلى جدول maids.',
        'stats' => [
            'total_source_rows' => $totalSource,
            'processed' => $processed,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
        ],
        'profiles_migration' => $profilesSummary,
        'errors' => $errors,
        'warnings' => array_slice($warnings, 0, 25),
        'defaults_used' => [
            'nationality' => $defaultNationalityUsed,
            'religion' => $defaultReligionUsed,
        ],
    ];
}

function buildMaidsMappings(PDO $oldDb, PDO $newDb): array
{
    return [
        'nationalities' => array_map('intval', $newDb->query('SELECT id FROM nationalities')->fetchAll(PDO::FETCH_COLUMN) ?: []),
        'religions' => array_map('intval', $newDb->query('SELECT id FROM religions')->fetchAll(PDO::FETCH_COLUMN) ?: []),
        'ages' => buildAgesMap($oldDb),
        'experiences' => buildExperienceMap($oldDb),
        'languages' => buildLanguageMap($oldDb, $newDb),
        'skills' => array_map('intval', $newDb->query('SELECT id FROM skills')->fetchAll(PDO::FETCH_COLUMN) ?: []),
        'social_status' => buildSocialStatusMap($oldDb),
        'office_map' => buildOfficeMap($newDb),
        'occupations' => buildOccupationMapping($oldDb, $newDb),
        'default_nationality' => getDefaultValue($newDb, 'nationalities'),
        'default_religion' => getDefaultValue($newDb, 'religions'),
    ];
}

function getDefaultValue(PDO $pdo, string $table): ?int
{
    $stmt = $pdo->query("SELECT id FROM {$table} ORDER BY id ASC LIMIT 1");
    $value = $stmt ? $stmt->fetchColumn() : null;
    return $value ? (int)$value : null;
}

function collectMissingDetails(PDO $oldDb, PDO $newDb, array $mappings): array
{
    $missingNationalities = $oldDb->query('SELECT DISTINCT national_id FROM cvs WHERE national_id IS NOT NULL AND national_id <> 0')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $missingNationalities = array_values(array_diff(array_map('intval', $missingNationalities), $mappings['nationalities']));

    $missingReligions = $oldDb->query('SELECT DISTINCT region_id FROM cvs WHERE region_id IS NOT NULL AND region_id <> 0')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $missingReligions = array_values(array_diff(array_map('intval', $missingReligions), $mappings['religions']));

    $nullNationalityCount = (int)$oldDb->query('SELECT COUNT(*) FROM cvs WHERE national_id IS NULL OR national_id = 0')->fetchColumn();
    $nullNationalitySamples = $oldDb->query('SELECT id FROM cvs WHERE national_id IS NULL OR national_id = 0 LIMIT 20')->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $nullReligionCount = (int)$oldDb->query('SELECT COUNT(*) FROM cvs WHERE region_id IS NULL OR region_id = 0')->fetchColumn();
    $nullReligionSamples = $oldDb->query('SELECT id FROM cvs WHERE region_id IS NULL OR region_id = 0 LIMIT 20')->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $occupationIds = $oldDb->query('SELECT DISTINCT occuption_id FROM cvs WHERE occuption_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $unmappedOccupations = [];
    foreach ($occupationIds as $occId) {
        $occId = (int)$occId;
        if (!isset($mappings['occupations']['job_titles'][$occId])) {
            $unmappedOccupations[] = $occId;
        }
    }

    $languageIds = [];
    foreach ($oldDb->query('SELECT lang_id FROM cvs WHERE lang_id IS NOT NULL AND lang_id <> ""') as $row) {
        foreach (normalizeJsonArray($row['lang_id']) as $id) {
            $languageIds[(int)$id] = true;
        }
    }
    $unmappedLanguages = array_values(array_diff(array_keys($languageIds), array_keys($mappings['languages'])));

    return [
        'missing_nationalities' => $missingNationalities,
        'missing_religions' => $missingReligions,
        'null_nationalities' => [
            'count' => $nullNationalityCount,
            'samples' => $nullNationalitySamples,
        ],
        'null_religions' => [
            'count' => $nullReligionCount,
            'samples' => $nullReligionSamples,
        ],
        'unmapped_occupations' => $unmappedOccupations,
        'unmatched_offices' => [],
        'unmapped_languages' => $unmappedLanguages,
    ];
}

function buildAgesMap(PDO $oldDb): array
{
    $map = [];
    foreach ($oldDb->query('SELECT id, `min`, `max` FROM recruitment_form_ages') as $row) {
        $map[(int)$row['id']] = ($row['min'] + $row['max']) / 2;
    }
    return $map;
}

function buildExperienceMap(PDO $oldDb): array
{
    $map = [];
    foreach ($oldDb->query('SELECT id, name FROM recruitment_form_experience') as $row) {
        $map[(int)$row['id']] = $row['name'];
    }
    return $map;
}

function buildLanguageMap(PDO $oldDb, PDO $newDb): array
{
    $map = [];

    $languageRows = $oldDb->query('SELECT id, name FROM recruitment_form_languages')->fetchAll();
    foreach ($languageRows as $row) {
        $normalized = normalizeLanguageName($row['name']);
        if ($normalized !== '') {
            $map[(int)$row['id']] = $normalized;
        }
    }

    return $map;
}

function buildSocialStatusMap(PDO $oldDb): array
{
    $map = [];
    foreach ($oldDb->query('SELECT id, name FROM recruitment_form_social_status') as $row) {
        $map[(int)$row['id']] = [
            'ar' => trim($row['name']),
            'en' => translateMaritalStatus(trim($row['name'])),
        ];
    }
    return $map;
}

function buildOfficeMap(PDO $newDb): array
{
    $map = [];
    $stmt = $newDb->query('SELECT rec_office_id, title, locale FROM recruitment_office_translations');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $normalized = normalizeKey($row['title']);
        if ($normalized !== '') {
            $map[$normalized] = (int)$row['rec_office_id'];
        }
        $map[(string)$row['rec_office_id']] = (int)$row['rec_office_id'];
    }
    return $map;
}

function buildOccupationMapping(PDO $oldDb, PDO $newDb): array
{
    $occupations = [];
    foreach ($oldDb->query('SELECT id, name FROM recruitment_form_occupation') as $row) {
        $occupations[(int)$row['id']] = trim($row['name']);
    }

    $jobTitles = [];
    $stmt = $newDb->query('SELECT job_title_id, title FROM job_titles_translations');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = normalizeKey($row['title']);
        if ($key !== '') {
            $jobTitles[$key] = (int)$row['job_title_id'];
        }
    }

    $mapping = [
        'job_titles' => [],
        'job_types' => [],
    ];

    foreach ($occupations as $id => $name) {
        $key = normalizeKey($name);
        $jobTitleId = $jobTitles[$key] ?? defaultJobTitleForOccupation($name);
        $jobTypeId = defaultJobTypeForOccupation($name);

        if ($jobTitleId) {
            $mapping['job_titles'][$id] = $jobTitleId;
        }
        if ($jobTypeId) {
            $mapping['job_types'][$id] = $jobTypeId;
        }
    }

    return $mapping;
}

function defaultJobTitleForOccupation(string $name): ?int
{
    $name = normalizeKey($name);

    if ($name === 'عاملةمنزلية' || $name === 'عاملةمنزلية' || $name === 'عاملمنزلى' || $name === 'مربية') {
        return 21;
    }

    if ($name === 'سائقخاص') {
        return 22;
    }

    if ($name === 'طباخمنزلية') {
        return 26;
    }

    return 21; // افتراضي عاملة منزلية
}

function defaultJobTypeForOccupation(string $name): int
{
    $name = normalizeKey($name);

    if ($name === 'سائقخاص') {
        return 2; // ايجار عمالة
    }

    return 1; // استقدام
}

function transformCvRow(array $cv, array $mappings, array $allowedColumns): ?array
{
    $errors = [];
    $warnings = [];

    $nationalityId = (int)($cv['national_id'] ?? 0);
    $usedDefaultNationality = false;
    if ($nationalityId === 0 || !in_array($nationalityId, $mappings['nationalities'], true)) {
        if ($mappings['default_nationality'] !== null) {
            $nationalityId = $mappings['default_nationality'];
            $warnings[] = 'تم استخدام جنسية افتراضية';
            $usedDefaultNationality = true;
        } else {
            return null;
        }
    }

    $religionId = (int)($cv['region_id'] ?? 0);
    $usedDefaultReligion = false;
    if ($religionId === 0 || !in_array($religionId, $mappings['religions'], true)) {
        if ($mappings['default_religion'] !== null) {
            $religionId = $mappings['default_religion'];
            $warnings[] = 'تم استخدام ديانة افتراضية';
            $usedDefaultReligion = true;
        } else {
            return null;
        }
    }

    $maidId = (int)$cv['id'];

    $age = computeAge($cv, $mappings['ages']);
    $experience = computeExperienceData($cv, $mappings['experiences']);
    $statusData = mapCvStatus((int)($cv['cv_status'] ?? 0));

    $languages = normalizeLanguagesField($cv['lang_id'] ?? null, $mappings['languages']);
    $skills = normalizeSkillsField($cv['skills'] ?? null, $mappings['skills']);

    $maritalStatus = mapSocialStatus((int)($cv['social_id'] ?? 0), $mappings['social_status']);

    $officeId = resolveOfficeId($cv['office'] ?? null, $mappings['office_map']);

    $image = extractImage($cv);
    $cvFile = normalizeCvFile($cv);
    $cvPreview = normalizeCvPreview($cv);

    $height = trimString($cv['length'] ?? '') ?: '0';
    $weight = trimString($cv['weight'] ?? '') ?: '0';

    $experienceYears = $experience['years'];
    $experienceStatus = $experience['status'];

    $birthDate = normalizeTimestamp($cv['birth_date'] ?? null, 'date');
    $createdAt = normalizeTimestamp($cv['created_at'] ?? null);
    $updatedAt = normalizeTimestamp($cv['updated_at'] ?? null) ?: $createdAt;

    $maidData = [
        ':id' => $maidId,
        ':name' => trimString($cv['cvs_name'] ?? '') ?: ('CV #' . $maidId),
        ':nationality_id' => $nationalityId,
        ':passport_num' => trimString($cv['passport_id'] ?? '') ?: ('CV_' . $maidId),
        ':price' => (float)($cv['service_price'] ?? 0),
        ':salary' => (float)($cv['salary'] ?? 0),
        ':religion_id' => $religionId,
        ':age' => $age,
        ':birth_date' => $birthDate,
        ':cv_file' => $cvFile,
        ':available' => $statusData['available'],
        ':is_pinned' => (int)($cv['profile_dropdown'] ?? 0) === 1 ? 1 : 0,
        ':available_at' => normalizeTimestamp($cv['arrived_date'] ?? null, 'date') ?: date('Y-m-d'),
        ':status' => $statusData['status'],
        ':is_show' => 1,
        ':created_at' => $createdAt,
        ':updated_at' => $updatedAt,
        ':experience_years' => $experienceYears,
        ':experience_status' => $experienceStatus,
        ':cv_preview' => $cvPreview,
        ':office_id' => $officeId,
        ':image' => $image,
        ':height' => $height,
        ':weight' => $weight,
        ':id_number' => null,
        ':id_expiry_date' => normalizeTimestamp($cv['passport_finish_release_date'] ?? null, 'date'),
        ':skills' => json_encode($skills, JSON_UNESCAPED_UNICODE),
        ':languages' => json_encode($languages, JSON_UNESCAPED_UNICODE),
        ':experience_country' => trimString($cv['experience_country'] ?? null),
        ':video_preview' => trimString($cv['cv_url'] ?? null),
        ':childrens_count' => trimString($cv['child_num'] ?? '') ?: null,
        ':education' => normalizeEducationField($cv['learning'] ?? null),
        ':duration' => trimString($cv['duration_contract'] ?? '') ?: null,
        ':st_code' => null,
        ':int_code' => null,
    ];

    foreach (array_keys($maidData) as $key) {
        $column = ltrim($key, ':');
        if (!in_array($column, $allowedColumns, true)) {
            unset($maidData[$key]);
        }
    }

    $jobTitleId = null;
    $jobTypeId = null;

    $occupationId = (int)($cv['occuption_id'] ?? 0);
    if ($occupationId && isset($mappings['occupations']['job_titles'][$occupationId])) {
        $jobTitleId = $mappings['occupations']['job_titles'][$occupationId];
    } else {
        $jobTitleId = defaultJobTitleForOccupation('عاملة منزلية');
        $errors[] = 'لم يتم العثور على مهنة مطابقة';
    }

    if ($occupationId && isset($mappings['occupations']['job_types'][$occupationId])) {
        $jobTypeId = $mappings['occupations']['job_types'][$occupationId];
    } else {
        $jobTypeId = defaultJobTypeForOccupation('عاملة منزلية');
    }

    $translations = [];
    if ($maritalStatus !== null) {
        $translations[] = [
            'locale' => 'ar',
            'languages' => json_encode($languages, JSON_UNESCAPED_UNICODE),
            'marital_status' => $maritalStatus['ar'],
        ];

        if ($maritalStatus['en'] !== null) {
            $translations[] = [
                'locale' => 'en',
                'languages' => json_encode($languages, JSON_UNESCAPED_UNICODE),
                'marital_status' => $maritalStatus['en'],
            ];
        }
    }

    return [
        'maid' => $maidData,
        'job_titles' => $jobTitleId ? [$jobTitleId] : [],
        'job_types' => $jobTypeId ? [$jobTypeId] : [],
        'translations' => $translations,
        'skills' => $skills,
        'errors' => $errors,
        'warnings' => $warnings,
        'used_defaults' => [
            'nationality' => $usedDefaultNationality,
            'religion' => $usedDefaultReligion,
        ],
    ];
}

function computeAge(array $cv, array $ageMap): float
{
    $birthDate = $cv['birth_date'] ?? null;
    if ($birthDate && $birthDate !== '0000-00-00') {
        $birth = new DateTime($birthDate);
        $today = new DateTime();
        return (float)$today->diff($birth)->y;
    }

    $ageId = (int)($cv['age_id'] ?? 0);
    if ($ageId && isset($ageMap[$ageId])) {
        return (float)$ageMap[$ageId];
    }

    return (float)($cv['age'] ?? 0) ?: 0.0;
}

function computeExperienceData(array $cv, array $experienceMap): array
{
    $experId = (int)($cv['exper_id'] ?? 0);
    $status = 'no_experience';
    if ($experId && isset($experienceMap[$experId])) {
        $status = stripos($experienceMap[$experId], 'قادم') !== false ? 'no_experience' : 'has_experience';
    }

    $from = trim((string)($cv['experience_from'] ?? ''));
    $to = trim((string)($cv['experience_to'] ?? ''));

    if ($from !== '' && $to !== '' && is_numeric($to) && is_numeric($from)) {
        $years = max(0, (float)$to - (float)$from);
    } else {
        $years = trimString($cv['experience_years'] ?? '') ?: trimString($cv['experience_to'] ?? '') ?: '0';
    }

    if ($years !== '0') {
        $status = 'has_experience';
    }

    return [
        'years' => (string)$years,
        'status' => $status,
    ];
}

function normalizeLanguagesField($value, array $languageMap): array
{
    $ids = normalizeJsonArray($value);
    $result = [];
    foreach ($ids as $id) {
        $id = (int)$id;
        if (isset($languageMap[$id])) {
            $result[] = $languageMap[$id];
        }
    }
    return array_values(array_unique($result));
}

function normalizeSkillsField($value, array $availableSkills): array
{
    $ids = normalizeJsonArray($value);
    $available = array_flip($availableSkills);
    $result = [];
    foreach ($ids as $id) {
        $id = (int)$id;
        if (isset($available[$id])) {
            $result[] = $id;
        }
    }
    return array_values(array_unique($result));
}

function mapSocialStatus(int $socialId, array $map): ?array
{
    return $map[$socialId] ?? null;
}

function extractImage(array $cv): string
{
    $personal = trimString($cv['personal_image'] ?? null);
    if ($personal) {
        return $personal;
    }

    $images = normalizeJsonArray($cv['images'] ?? null);
    if (!empty($images)) {
        return $images[0];
    }

    $newImage = trimString($cv['new_image'] ?? null);
    if ($newImage) {
        return $newImage;
    }

    return '';
}

function normalizeCvFile(array $cv): string
{
    $cvFile = trimString($cv['cv_file'] ?? null);
    if ($cvFile) {
        return $cvFile;
    }

    $newImage = trimString($cv['new_image'] ?? null);
    if ($newImage) {
        return $newImage;
    }

    return '';
}

function normalizeCvPreview(array $cv): ?string
{
    $cvUrl = trimString($cv['cv_url'] ?? null);
    if ($cvUrl) {
        return $cvUrl;
    }

    $newImage = trimString($cv['new_image'] ?? null);
    return $newImage ?: null;
}

function resolveOfficeId(?string $officeName, array $officeMap): ?int
{
    if ($officeName === null) {
        return null;
    }

    $normalized = normalizeKey($officeName);
    if ($normalized === '') {
        return null;
    }

    return $officeMap[$normalized] ?? null;
}

function mapCvStatus(int $cvStatus): array
{
    switch ($cvStatus) {
        case 0:
            return ['status' => 'available', 'available' => 1];
        case 1:
            return ['status' => 'reserved', 'available' => 0];
        case 2:
            return ['status' => 'backout', 'available' => 0];
        case 3:
            return ['status' => 'pending', 'available' => 0];
        case 4:
            return ['status' => 'completed', 'available' => 0];
        case 5:
            return ['status' => 'backout', 'available' => 0];
        default:
            return ['status' => 'available', 'available' => 1];
    }
}

function normalizeJsonArray($value): array
{
    if ($value === null) {
        return [];
    }

    if (is_array($value)) {
        return $value;
    }

    $string = trim((string)$value);
    if ($string === '') {
        return [];
    }

    $decoded = json_decode($string, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (is_array($decoded)) {
            return $decoded;
        }
        return [$decoded];
    }

    $parts = array_filter(array_map('trim', explode(',', $string)), fn($item) => $item !== '');
    return $parts;
}

function normalizeEducationField($value): ?string
{
    if ($value === null || trim((string)$value) === '') {
        return null;
    }

    $value = str_replace(['[', ']'], '', (string)$value);
    $parts = array_filter(array_map('trim', explode(',', $value)), fn($item) => $item !== '');
    if (empty($parts)) {
        return null;
    }

    return implode(', ', $parts);
}

function normalizeLanguageName(string $name): string
{
    $normalized = normalizeKey($name);

    $map = [
        'اللغةالعربية' => 'arabic',
        'العربية' => 'arabic',
        'arabic' => 'arabic',
        'اللغةالانجليزية' => 'english',
        'الانجليزية' => 'english',
        'english' => 'english',
        'اللغةالام' => 'mother',
        'اللغةالامهرية' => 'amharic',
        'الهندية' => 'hindi',
        'اللغةالبنجالية' => 'bengali',
        'اللغةالبنجالية	' => 'bengali',
        'amharic' => 'amharic',
        'bengali' => 'bengali',
        'motherlanguage' => 'mother',
    ];

    return $map[$normalized] ?? $normalized;
}

function translateMaritalStatus(string $value): ?string
{
    $value = normalizeKey($value);
    $map = [
        'اعزب' => 'Single',
        'عازب' => 'Single',
        'متزوج' => 'Married',
        'متزوجة' => 'Married',
        'عازبة' => 'Single',
        'مطلقة' => 'Divorced',
        'ارملة' => 'Widowed',
    ];

    return $map[$value] ?? null;
}

function normalizeKey(?string $value): string
{
    if ($value === null) {
        return '';
    }

    $value = trim(mb_strtolower($value));
    $value = str_replace([' ', '-', '_', '\\', '/', 'ـ', '،', ',', '.', '\"', '"', '\'', "'"], '', $value);
    return $value;
}

function normalizeTimestamp(?string $value, string $type = 'datetime'): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);
    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return null;
    }

    if ($type === 'date') {
        return substr($value, 0, 10);
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

function fetchTableColumns(PDO $pdo, string $table): array
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
