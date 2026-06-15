<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

$commit = in_array('--commit', $argv, true);
$database = trim((string) ($_ENV['DB_DATABASE'] ?? 'alrafedain_erp'));

$files = [
    'housemaid' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\سير ذاتية\\عاملة منزلية\\عاملة_منزلية_محدث.xlsx',
    'driver' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\سير ذاتية\\سائق خاص\\سائق_خاص_محدث.xlsx',
    'other_jobs' => 'E:\\Software Engineering\\Work\\data\\الرافدين\\data after modification\\سير ذاتية\\باقي المهن\\باقي_المهن_محدث.xlsx',
];

$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($_ENV['DB_PORT'] ?? '3306') . ';dbname=' . $database . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$lookups = buildLookups($pdo);
$rows = [];
foreach ($files as $source => $path) {
    foreach (readRows($path) as $rowNumber => $row) {
        $rows[] = ['source' => $source, 'row_number' => $rowNumber, 'row' => $row];
    }
}

$stats = [
    'excel_rows' => count($rows),
    'inserted' => 0,
    'updated' => 0,
    'id_migrated' => 0,
    'skipped' => 0,
    'duplicate_passports_skipped' => 0,
    'missing_passport_skipped' => 0,
    'missing_id_inserted_without_id' => 0,
    'relations_job_type' => 0,
    'relations_job_title' => 0,
    'translations' => 0,
];

$seenPassports = [];
$skips = [];
$unmatched = [
    'nationality' => [],
    'religion' => [],
    'office' => [],
    'job_type' => [],
    'job_title' => [],
];

$pdo->beginTransaction();

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($rows as $entry) {
        $row = $entry['row'];
        $passport = normalizePassport(value($row, 'رقم الجواز *'));
        $sourceId = numericId(value($row, 'ID'));
        $location = $entry['source'] . ':' . $entry['row_number'];

        if ($passport === '') {
            $stats['skipped']++;
            $stats['missing_passport_skipped']++;
            $skips[] = "{$location}: missing passport";
            continue;
        }

        if (isset($seenPassports[$passport])) {
            $stats['skipped']++;
            $stats['duplicate_passports_skipped']++;
            $skips[] = "{$location}: duplicate passport {$passport}, first seen {$seenPassports[$passport]}";
            continue;
        }
        $seenPassports[$passport] = $location;

        $payload = buildMaidPayload($row, $lookups, $unmatched);
        $payload['passport_num'] = $passport;
        if ($sourceId !== null) {
            $payload['id'] = $sourceId;
            $payload['old_id'] = $sourceId;
        } else {
            $stats['missing_id_inserted_without_id']++;
        }

        $existing = findMaidByPassport($pdo, $passport);
        $targetId = $sourceId;

        if ($existing) {
            $existingId = (int) $existing['id'];
            if ($sourceId !== null && $existingId !== $sourceId && !maidIdExists($pdo, $sourceId)) {
                migrateMaidId($pdo, $existingId, $sourceId);
                $targetId = $sourceId;
                $stats['id_migrated']++;
            } else {
                $targetId = $existingId;
                unset($payload['id']);
            }

            updateRow($pdo, 'maids', 'id', $targetId, $payload);
            $stats['updated']++;
        } else {
            if ($sourceId !== null && maidIdExists($pdo, $sourceId)) {
                unset($payload['id']);
                $targetId = null;
            }

            insertRow($pdo, 'maids', $payload);
            $targetId = $targetId ?: (int) $pdo->lastInsertId();
            $stats['inserted']++;
        }

        syncMaidTranslation($pdo, $targetId, value($row, 'الحالة الاجتماعية'), $stats);
        syncMaidRelations($pdo, $targetId, $payload, $stats);
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    if ($commit) {
        $pdo->commit();
        $maxId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM maids')->fetchColumn();
        $pdo->exec('ALTER TABLE maids AUTO_INCREMENT = ' . ($maxId + 1));
    } else {
        $pdo->rollBack();
    }
} catch (Throwable $e) {
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Throwable) {
        //
    }
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo 'Mode: ' . ($commit ? 'COMMIT' : 'DRY-RUN') . PHP_EOL;
echo 'Database: ' . $database . PHP_EOL;
foreach ($stats as $key => $value) {
    echo str_replace('_', ' ', ucfirst($key)) . ': ' . $value . PHP_EOL;
}

foreach ($unmatched as $key => $values) {
    $values = array_keys($values);
    sort($values);
    echo 'Unmatched ' . $key . ': ' . count($values) . PHP_EOL;
    foreach (array_slice($values, 0, 30) as $value) {
        echo '- ' . $value . PHP_EOL;
    }
}

if ($skips) {
    echo 'Skipped rows:' . PHP_EOL;
    foreach (array_slice($skips, 0, 50) as $skip) {
        echo '- ' . $skip . PHP_EOL;
    }
}

function readRows(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException("Missing file: {$path}");
    }

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
            $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row);
            $header = $headers[$col] ?: "COL{$col}";
            $value = trim((string) $cell->getFormattedValue());
            $data[$header] = $value;
            $data['_raw'][$header] = $cell->getValue();
            $hasValue = $hasValue || $value !== '';
        }

        if ($hasValue) {
            $rows[$row] = $data;
        }
    }

    return $rows;
}

function buildLookups(PDO $pdo): array
{
    return [
        'nationalities' => translatableLookup($pdo, 'nationalities_translations', 'nationality_id', ['title', 'sub_title']),
        'religions' => translatableLookup($pdo, 'religions_translations', 'religion_id', ['title']),
        'job_types' => translatableLookup($pdo, 'job_types_translations', 'job_type_id', ['title']),
        'job_titles' => translatableLookup($pdo, 'job_titles_translations', 'job_title_id', ['title']),
        'offices' => translatableLookup($pdo, 'recruitment_office_translations', 'rec_office_id', ['title']),
        'admin_id' => (int) ($pdo->query('SELECT id FROM admins ORDER BY id LIMIT 1')->fetchColumn() ?: 0),
    ];
}

function translatableLookup(PDO $pdo, string $table, string $idColumn, array $textColumns): array
{
    $lookup = [];
    foreach ($pdo->query("SELECT * FROM `{$table}`") as $row) {
        foreach ($textColumns as $column) {
            $key = normalizeKey($row[$column] ?? '');
            if ($key !== '') {
                $lookup[$key] = (int) $row[$idColumn];
            }
        }
    }

    return $lookup;
}

function buildMaidPayload(array $row, array $lookups, array &$unmatched): array
{
    $now = date('Y-m-d H:i:s');
    $status = normalizeStatus(value($row, 'الحالة'));
    $nationalityId = lookupId($lookups['nationalities'], value($row, 'الجنسية *'), 'nationality', $unmatched);
    $religionId = lookupReligion($lookups['religions'], value($row, 'الديانة'), $unmatched);
    $officeId = lookupId($lookups['offices'], value($row, 'المكتب الخارجي'), 'office', $unmatched, false);
    $jobTypeId = lookupId($lookups['job_types'], value($row, 'نوع السيرة *'), 'job_type', $unmatched);
    $jobTitleIds = lookupListIds($lookups['job_titles'], value($row, 'المهنة *'), 'job_title', $unmatched);
    $createdAt = parseDateTime(rawValue($row, 'أنشئ بتاريخ')) ?: $now;

    return array_filter([
        'name' => value($row, 'الاسم بالإنجليزية *') ?: 'EDIT_ME_NAME',
        'gender' => normalizeGender(value($row, 'الجنس')),
        'nationality_id' => $nationalityId,
        'religion_id' => $religionId ?: ($lookups['religions'][normalizeKey('غير محدد')] ?? null),
        'office_id' => $officeId,
        'salary' => decimal(value($row, 'الراتب')),
        'price' => decimal(value($row, 'التكلفة')),
        'birth_date' => parseDate(rawValue($row, 'تاريخ الميلاد')),
        'age' => decimal(value($row, 'العمر')),
        'height' => nullableText(value($row, 'الطول')),
        'weight' => nullableText(value($row, 'الوزن')),
        'id_number' => nullableText(value($row, 'رقم الهوية')),
        'id_expiry_date' => parseDate(rawValue($row, 'تاريخ انتهاء الهوية')),
        'childrens_count' => nullableText(value($row, 'عدد الأطفال')),
        'duration' => nullableText(value($row, 'مدة العقد')),
        'experience_status' => normalizeExperience(value($row, 'حالة الخبرة *')),
        'experience_years' => normalizeExperience(value($row, 'حالة الخبرة *')) === 'has_experience'
            ? (string) (decimal(value($row, 'سنوات الخبرة')) ?? 0)
            : '0',
        'skills' => json_encode(splitList(value($row, 'المهارات')), JSON_UNESCAPED_UNICODE),
        'languages' => json_encode(normalizeLanguages(value($row, 'اللغات')), JSON_UNESCAPED_UNICODE),
        'percentage' => json_encode(languagePercentages(value($row, 'نسب اللغات')), JSON_UNESCAPED_UNICODE),
        'additional_notes' => nullableText(value($row, 'ملاحظات إضافية')),
        'arrival_date' => parseDate(rawValue($row, 'تاريخ الوصول')),
        'available_at' => $status === 'available' ? date('Y-m-d') : null,
        'status' => $status,
        'available' => $status === 'available' ? 1 : 0,
        'request_type' => normalizeRequestType(value($row, 'نوع الطلب')),
        'is_show' => yesNo(value($row, 'إظهار في الموقع'), true) ? 1 : 0,
        'is_pinned' => 0,
        'video_link' => nullableText(value($row, 'رابط فيديو')),
        'added_by' => $lookups['admin_id'] ?: null,
        'created_at' => $createdAt,
        'updated_at' => $now,
        '_job_type_id' => $jobTypeId,
        '_job_title_ids' => $jobTitleIds,
    ], fn ($value) => $value !== null && $value !== '');
}

function lookupReligion(array $lookup, string $value, array &$unmatched): ?int
{
    $normalized = normalizeKey($value);
    $aliases = [
        normalizeKey('مسلم') => normalizeKey('مسلم'),
        normalizeKey('مسلمة') => normalizeKey('مسلمة'),
        normalizeKey('مسيحي') => normalizeKey('مسيحية'),
        normalizeKey('مسيحية') => normalizeKey('مسيحية'),
        normalizeKey('غير محدد') => normalizeKey('غير محدد'),
    ];
    $key = $aliases[$normalized] ?? $normalized;
    if ($key === '') {
        return $lookup[normalizeKey('غير محدد')] ?? null;
    }
    if (isset($lookup[$key])) {
        return $lookup[$key];
    }
    $unmatched['religion'][$value] = true;

    return $lookup[normalizeKey('غير محدد')] ?? null;
}

function lookupId(array $lookup, string $value, string $type, array &$unmatched, bool $required = true): ?int
{
    $key = normalizeKey($value);
    if ($type === 'job_title' && $key === normalizeKey('سائقة خاصة')) {
        $key = normalizeKey('سائق خاص');
    }

    if ($key === '') {
        if ($required) {
            $unmatched[$type]['(empty)'] = true;
        }
        return null;
    }
    if (isset($lookup[$key])) {
        return $lookup[$key];
    }
    $unmatched[$type][$value] = true;

    return null;
}

function lookupListIds(array $lookup, string $value, string $type, array &$unmatched): array
{
    $ids = [];
    foreach (splitList($value) as $item) {
        $id = lookupId($lookup, $item, $type, $unmatched);
        if ($id) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function syncMaidRelations(PDO $pdo, int $maidId, array $payload, array &$stats): void
{
    if (!empty($payload['_job_type_id'])) {
        $jobTypeId = (int) $payload['_job_type_id'];
        if (!relationExists($pdo, 'maid_jobs', 'job_type_id', $maidId, $jobTypeId)) {
            insertRow($pdo, 'maid_jobs', [
                'maid_id' => $maidId,
                'job_type_id' => $jobTypeId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $stats['relations_job_type']++;
        }
    }

    foreach (($payload['_job_title_ids'] ?? []) as $jobTitleId) {
        $jobTitleId = (int) $jobTitleId;
        if (!relationExists($pdo, 'maid_job_titles', 'job_title_id', $maidId, $jobTitleId)) {
            insertRow($pdo, 'maid_job_titles', [
                'maid_id' => $maidId,
                'job_title_id' => $jobTitleId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $stats['relations_job_title']++;
        }
    }
}

function relationExists(PDO $pdo, string $table, string $relationColumn, int $maidId, int $relationId): bool
{
    $stmt = $pdo->prepare("SELECT id FROM `{$table}` WHERE maid_id = ? AND `{$relationColumn}` = ? LIMIT 1");
    $stmt->execute([$maidId, $relationId]);

    return (bool) $stmt->fetchColumn();
}

function syncMaidTranslation(PDO $pdo, int $maidId, string $maritalStatus, array &$stats): void
{
    foreach (['ar', 'en'] as $locale) {
        $stmt = $pdo->prepare('SELECT id FROM maid_translations WHERE maid_id = ? AND locale = ? LIMIT 1');
        $stmt->execute([$maidId, $locale]);
        $id = $stmt->fetchColumn();
        $payload = [
            'maid_id' => $maidId,
            'locale' => $locale,
            'marital_status' => nullableText($maritalStatus),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            updateRow($pdo, 'maid_translations', 'id', (int) $id, $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            insertRow($pdo, 'maid_translations', $payload);
        }
        $stats['translations']++;
    }
}

function findMaidByPassport(PDO $pdo, string $passport): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM maids WHERE passport_num = ? LIMIT 1');
    $stmt->execute([$passport]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function maidIdExists(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('SELECT id FROM maids WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);

    return (bool) $stmt->fetchColumn();
}

function migrateMaidId(PDO $pdo, int $fromId, int $toId): void
{
    foreach (['maid_translations', 'maid_jobs', 'maid_job_titles', 'maid_expertises', 'maid_attachments'] as $table) {
        if (!tableExists($pdo, $table)) {
            continue;
        }
        $pdo->prepare("UPDATE `{$table}` SET maid_id = ? WHERE maid_id = ?")->execute([$toId, $fromId]);
    }
    $pdo->prepare('UPDATE maids SET id = ? WHERE id = ?')->execute([$toId, $fromId]);
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);

    return (bool) $stmt->fetchColumn();
}

function insertRow(PDO $pdo, string $table, array $payload): void
{
    $payload = cleanPayload($payload);
    $fields = array_keys($payload);
    $sql = "INSERT INTO `{$table}` (" . implode(', ', array_map(fn ($field) => "`{$field}`", $fields)) . ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')';
    $pdo->prepare($sql)->execute(array_values($payload));
}

function insertIgnore(PDO $pdo, string $table, array $payload): void
{
    $payload = cleanPayload($payload);
    $fields = array_keys($payload);
    $sql = "INSERT IGNORE INTO `{$table}` (" . implode(', ', array_map(fn ($field) => "`{$field}`", $fields)) . ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')';
    $pdo->prepare($sql)->execute(array_values($payload));
}

function updateRow(PDO $pdo, string $table, string $key, int $id, array $payload): void
{
    $payload = cleanPayload($payload);
    unset($payload[$key]);
    if ($payload === []) {
        return;
    }

    $assignments = implode(', ', array_map(fn ($field) => "`{$field}` = ?", array_keys($payload)));
    $values = array_values($payload);
    $values[] = $id;
    $pdo->prepare("UPDATE `{$table}` SET {$assignments} WHERE `{$key}` = ?")->execute($values);
}

function cleanPayload(array $payload): array
{
    unset($payload['_job_type_id'], $payload['_job_title_ids']);

    return array_filter($payload, fn ($value) => $value !== null, ARRAY_FILTER_USE_BOTH);
}

function value(array $row, string $key): string
{
    return trim((string) ($row[$key] ?? ''));
}

function rawValue(array $row, string $key): mixed
{
    return $row['_raw'][$key] ?? ($row[$key] ?? null);
}

function normalizePassport(string $value): string
{
    return mb_strtoupper(trim($value), 'UTF-8');
}

function numericId(string $value): ?int
{
    $digits = preg_replace('/\D+/u', '', translateDigits($value));

    return $digits === '' ? null : (int) $digits;
}

function decimal(string $value): ?float
{
    $value = str_replace(',', '', translateDigits(trim($value)));

    return is_numeric($value) ? (float) $value : null;
}

function nullableText(string $value): ?string
{
    $value = trim($value);

    return $value === '' ? null : $value;
}

function parseDate(mixed $value): ?string
{
    $dateTime = parseDateTime($value);

    return $dateTime ? substr($dateTime, 0, 10) : null;
}

function parseDateTime(mixed $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_numeric($value)) {
        return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
    }

    $value = translateDigits(trim((string) $value));
    foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt instanceof DateTime) {
            return $dt->format('Y-m-d H:i:s');
        }
    }
    $timestamp = strtotime($value);

    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
}

function normalizeGender(string $value): ?string
{
    $key = normalizeKey($value);
    return match ($key) {
        normalizeKey('ذكر') => 'male',
        normalizeKey('أنثى'), normalizeKey('انثى') => 'female',
        default => null,
    };
}

function normalizeStatus(string $value): string
{
    $key = normalizeKey($value);
    return match ($key) {
        normalizeKey('محجوزة'), normalizeKey('محجوز'), 'reserved' => 'reserved',
        normalizeKey('تم التعاقد'), normalizeKey('متعاقدة'), 'contracted' => 'contracted',
        normalizeKey('داخل الإيواء'), normalizeKey('داخل الايواء') => 'in_accommodation',
        normalizeKey('باك أوت'), normalizeKey('باك اوت'), 'backout' => 'backout',
        normalizeKey('غير متاحة'), normalizeKey('موقوفة'), normalizeKey('موقوف'), 'suspended' => 'suspended',
        normalizeKey('مرحلة'), 'deported' => 'deported',
        default => 'available',
    };
}

function normalizeExperience(string $value): string
{
    $key = normalizeKey($value);
    return in_array($key, [normalizeKey('سبق لها العمل'), normalizeKey('سبق له العمل'), 'has_experience'], true)
        ? 'has_experience'
        : 'no_experience';
}

function normalizeRequestType(string $value): string
{
    $key = normalizeKey($value);
    return match ($key) {
        normalizeKey('معروفة'), normalizeKey('معروف'), 'known' => 'known',
        normalizeKey('معينة'), normalizeKey('محددة'), normalizeKey('محدد'), 'specific' => 'specific',
        default => 'custom_spec',
    };
}

function yesNo(string $value, bool $default): bool
{
    $key = normalizeKey($value);
    if ($key === '') {
        return $default;
    }

    return in_array($key, [normalizeKey('نعم'), 'yes', '1', 'true'], true);
}

function splitList(string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/[|،,]+/u', $value) ?: [])));
}

function normalizeLanguages(string $value): array
{
    $aliases = [
        normalizeKey('العربية') => 'arabic',
        normalizeKey('عربي') => 'arabic',
        normalizeKey('الإنجليزية') => 'english',
        normalizeKey('الانجليزية') => 'english',
        normalizeKey('انجليزي') => 'english',
        normalizeKey('الأمهرية') => 'amharic',
        normalizeKey('الامهرية') => 'amharic',
        normalizeKey('امهرية') => 'amharic',
        normalizeKey('اللغة الأم') => 'mother',
        normalizeKey('اللغة الام') => 'mother',
    ];

    $languages = [];
    foreach (splitList($value) as $item) {
        $key = normalizeKey($item);
        $languages[] = $aliases[$key] ?? strtolower($item);
    }

    return array_values(array_unique(array_filter($languages)));
}

function languagePercentages(string $value): array
{
    $percentages = [];
    foreach (splitList($value) as $item) {
        if (!str_contains($item, ':')) {
            continue;
        }
        [$language, $percent] = array_map('trim', explode(':', $item, 2));
        $normalized = normalizeLanguages($language)[0] ?? strtolower($language);
        if ($normalized !== '' && $percent !== '') {
            $percentages[$normalized] = $percent;
        }
    }

    return $percentages;
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
    $value = mb_strtolower(trim((string) $value), 'UTF-8');
    $value = strtr($value, [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
        'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي',
        'ـ' => '',
    ]);
    $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value);
    $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);

    return trim(preg_replace('/\s+/u', ' ', $value));
}
