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
/**
 * نقل الجداول المرتبطة بالسير الذاتية كجداول منفصلة
 */

// منع جميع الـ warnings والـ output قبل JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

try {
    // تنظيف أي output سابق
    if (ob_get_level() > 0) {
        ob_clean();
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? 'analyze';

    // الاتصال بالقواعد
    $oldDb = new PDO("mysql:host={$MIGRATION_OLD_DB_HOST};port={$MIGRATION_OLD_DB_PORT};dbname={$MIGRATION_OLD_DB_NAME};charset=utf8mb4",
        $MIGRATION_OLD_DB_USER,
        $MIGRATION_OLD_DB_PASS
    );
    $newDb = new PDO("mysql:host={$MIGRATION_NEW_DB_HOST};port={$MIGRATION_NEW_DB_PORT};dbname={$MIGRATION_NEW_DB_NAME};charset=utf8mb4",
        $MIGRATION_NEW_DB_USER,
        $MIGRATION_NEW_DB_PASS
    );

    switch ($action) {
        case 'analyze_related_tables':
            // تحليل الجداول المرتبطة المطلوبة
            $relatedFields = [
                'national_id' => 'nationalities',
                'occuption_id' => 'jobs/professions',
                'skills' => 'skills',
                'social_id' => 'social_status',
                'age_id' => 'ages',
                'region_id' => 'regions',
                'lang_id' => 'languages',
                'exper_id' => 'experiences',
                'country_id' => 'countries',
                'office' => 'offices',
                'airport_id' => 'airports'
            ];

            $analysis = [];

            // فحص البيانات في جدول cvs
            foreach ($relatedFields as $field => $suggestedTable) {
                try {
                    // فحص القيم المستخدمة في القاعدة القديمة
                    $usedValues = $oldDb->query("
                        SELECT DISTINCT $field, COUNT(*) as usage_count
                        FROM cvs
                        WHERE $field IS NOT NULL AND $field != 0 AND $field != ''
                        GROUP BY $field
                        ORDER BY $field
                        LIMIT 20
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    // البحث عن جدول مطابق في القاعدة الجديدة
                    $possibleTables = findPossibleTables($newDb, $suggestedTable);

                    $analysis[$field] = [
                        'suggested_table' => $suggestedTable,
                        'used_values' => $usedValues,
                        'total_usage' => array_sum(array_column($usedValues, 'usage_count')),
                        'unique_values' => count($usedValues),
                        'possible_tables' => $possibleTables,
                        'recommended_action' => count($usedValues) > 0 ? 'ينصح بإنشاء/تحديث الجدول' : 'تجاهل'
                    ];

                } catch (Exception $e) {
                    $analysis[$field] = [
                        'error' => $e->getMessage(),
                        'recommended_action' => 'فحص يدوي مطلوب'
                    ];
                }
            }

            // تنظيف وإرسال JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }

            echo json_encode([
                'success' => true,
                'analysis' => $analysis,
                'message' => 'تحليل الجداول المرتبطة مكتمل'
            ], JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'create_missing_tables':
            // إنشاء الجداول المفقودة
            $results = [];

            $tablesToCreate = [
                'ages' => [
                    'sql' => "CREATE TABLE IF NOT EXISTS ages (
                        id INT PRIMARY KEY,
                        age_range VARCHAR(50),
                        min_age INT,
                        max_age INT,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    )",
                    'sample_data' => [
                        [1, '18-25', 18, 25],
                        [2, '26-30', 26, 30],
                        [3, '31-35', 31, 35],
                        [4, '36-40', 36, 40],
                        [5, '41-45', 41, 45]
                    ]
                ],
                'regions' => [
                    'sql' => "CREATE TABLE IF NOT EXISTS regions (
                        id INT PRIMARY KEY,
                        name_ar VARCHAR(255),
                        name_en VARCHAR(255),
                        country_id INT,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    )",
                    'sample_data' => [
                        [1, 'الرياض', 'Riyadh', 1],
                        [2, 'جدة', 'Jeddah', 1],
                        [3, 'الدمام', 'Dammam', 1]
                    ]
                ],
                'languages' => [
                    'sql' => "CREATE TABLE IF NOT EXISTS languages (
                        id INT PRIMARY KEY,
                        name_ar VARCHAR(255),
                        name_en VARCHAR(255),
                        code VARCHAR(5),
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    )",
                    'sample_data' => [
                        [1, 'العربية', 'Arabic', 'ar'],
                        [2, 'الإنجليزية', 'English', 'en'],
                        [3, 'التاغالوغ', 'Tagalog', 'tl']
                    ]
                ],
                'experiences' => [
                    'sql' => "CREATE TABLE IF NOT EXISTS experiences (
                        id INT PRIMARY KEY,
                        name_ar VARCHAR(255),
                        name_en VARCHAR(255),
                        years_range VARCHAR(50),
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    )",
                    'sample_data' => [
                        [1, 'بدون خبرة', 'No Experience', '0'],
                        [2, 'خبرة قليلة', 'Little Experience', '1-2'],
                        [3, 'خبرة متوسطة', 'Medium Experience', '3-5'],
                        [4, 'خبرة عالية', 'High Experience', '5+']
                    ]
                ],
                'social_statuses' => [
                    'sql' => "CREATE TABLE IF NOT EXISTS social_statuses (
                        id INT PRIMARY KEY,
                        name_ar VARCHAR(255),
                        name_en VARCHAR(255),
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    )",
                    'sample_data' => [
                        [1, 'أعزب', 'Single'],
                        [2, 'متزوج', 'Married'],
                        [3, 'مطلق', 'Divorced'],
                        [4, 'أرمل', 'Widow']
                    ]
                ],
                'offices' => [
                    'sql' => "CREATE TABLE IF NOT EXISTS offices (
                        id INT PRIMARY KEY,
                        name VARCHAR(255),
                        location VARCHAR(255),
                        phone VARCHAR(50),
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    )",
                    'sample_data' => [
                        [1, 'المكتب الرئيسي', 'الرياض', '+966112345678']
                    ]
                ],
                'airports' => [
                    'sql' => "CREATE TABLE IF NOT EXISTS airports (
                        id INT PRIMARY KEY,
                        name_ar VARCHAR(255),
                        name_en VARCHAR(255),
                        code VARCHAR(10),
                        city VARCHAR(255),
                        country_id INT,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    )",
                    'sample_data' => [
                        [1, 'مطار الملك خالد الدولي', 'King Khalid International Airport', 'RUH', 'الرياض', 1]
                    ]
                ]
            ];

            $createdTables = [];
            $errors = [];

            foreach ($tablesToCreate as $tableName => $config) {
                try {
                    // إنشاء الجدول
                    $newDb->exec($config['sql']);

                    // إدراج البيانات التجريبية
                    $sampleInserted = 0;
                    foreach ($config['sample_data'] as $row) {
                        try {
                            $placeholders = str_repeat('?,', count($row) - 1) . '?';
                            $insertSql = "INSERT IGNORE INTO $tableName VALUES ($placeholders, NOW(), NOW())";
                            $stmt = $newDb->prepare($insertSql);
                            $stmt->execute($row);
                            $sampleInserted++;
                        } catch (Exception $e) {
                            // تجاهل إذا كان موجوداً
                        }
                    }

                    $createdTables[$tableName] = [
                        'created' => true,
                        'sample_data_inserted' => $sampleInserted
                    ];

                } catch (Exception $e) {
                    $errors[$tableName] = $e->getMessage();
                }
            }

            // تنظيف وإرسال JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }

            echo json_encode([
                'success' => true,
                'created_tables' => $createdTables,
                'errors' => $errors,
                'message' => 'تم إنشاء ' . count($createdTables) . ' جدول مرتبط'
            ], JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'populate_related_tables':
            // ملء الجداول المرتبطة بالبيانات الفعلية من cvs
            $results = [];

            // البحث عن القيم المستخدمة في كل حقل
            $fieldsToProcess = [
                'occuption_id' => 'jobs',
                'age_id' => 'ages',
                'region_id' => 'regions',
                'lang_id' => 'languages',
                'exper_id' => 'experiences',
                'social_id' => 'social_statuses',
                'office' => 'offices',
                'airport_id' => 'airports'
            ];

            foreach ($fieldsToProcess as $field => $targetTable) {
                try {
                    // الحصول على القيم المستخدمة
                    $usedValues = $oldDb->query("
                        SELECT DISTINCT $field, COUNT(*) as usage_count
                        FROM cvs
                        WHERE $field IS NOT NULL AND $field != 0 AND $field != ''
                        GROUP BY $field
                        ORDER BY $field
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    $inserted = 0;

                    // إدراج القيم في الجدول المناسب
                    foreach ($usedValues as $value) {
                        try {
                            $id = $value[$field];

                            if ($targetTable === 'ages') {
                                $ageRange = calculateAgeRange($id);
                                $sql = "INSERT IGNORE INTO ages (id, age_range, min_age, max_age, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                                $stmt = $newDb->prepare($sql);
                                $stmt->execute([$id, $ageRange['range'], $ageRange['min'], $ageRange['max']]);
                            } elseif ($targetTable === 'regions') {
                                $sql = "INSERT IGNORE INTO regions (id, name_ar, name_en, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
                                $stmt = $newDb->prepare($sql);
                                $stmt->execute([$id, "منطقة $id", "Region $id"]);
                            } elseif ($targetTable === 'languages') {
                                $langName = getLanguageName($id);
                                $sql = "INSERT IGNORE INTO languages (id, name_ar, name_en, code, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                                $stmt = $newDb->prepare($sql);
                                $stmt->execute([$id, $langName['ar'], $langName['en'], $langName['code']]);
                            } elseif ($targetTable === 'experiences') {
                                $expName = getExperienceName($id);
                                $sql = "INSERT IGNORE INTO experiences (id, name_ar, name_en, years_range, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                                $stmt = $newDb->prepare($sql);
                                $stmt->execute([$id, $expName['ar'], $expName['en'], $expName['range']]);
                            } elseif ($targetTable === 'social_statuses') {
                                $socialName = getSocialStatusName($id);
                                $sql = "INSERT IGNORE INTO social_statuses (id, name_ar, name_en, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
                                $stmt = $newDb->prepare($sql);
                                $stmt->execute([$id, $socialName['ar'], $socialName['en']]);
                            } elseif ($targetTable === 'offices') {
                                $sql = "INSERT IGNORE INTO offices (id, name, location, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
                                $stmt = $newDb->prepare($sql);
                                $stmt->execute([$id, "مكتب $id", "موقع $id"]);
                            } elseif ($targetTable === 'airports') {
                                $airportName = getAirportName($id);
                                $sql = "INSERT IGNORE INTO airports (id, name_ar, name_en, code, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                                $stmt = $newDb->prepare($sql);
                                $stmt->execute([$id, $airportName['ar'], $airportName['en'], "AP$id"]);
                            } else {
                                // جدول عام
                                $sql = "INSERT IGNORE INTO $targetTable (id, name, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
                                $stmt = $newDb->prepare($sql);
                                $stmt->execute([$id, "عنصر $id"]);
                            }

                            $inserted++;

                        } catch (Exception $e) {
                            error_log("خطأ في إدراج $targetTable ID $id: " . $e->getMessage());
                        }
                    }

                    $results[$field] = [
                        'target_table' => $targetTable,
                        'unique_values' => count($usedValues),
                        'inserted' => $inserted,
                        'total_usage' => array_sum(array_column($usedValues, 'usage_count'))
                    ];

                } catch (Exception $e) {
                    $results[$field] = [
                        'error' => $e->getMessage()
                    ];
                }
            }

            // تنظيف وإرسال JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }

            echo json_encode([
                'success' => true,
                'results' => $results,
                'message' => 'تم ملء الجداول المرتبطة بالبيانات الفعلية'
            ], JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'find_existing_tables':
            // البحث عن الجداول الموجودة في القاعدة الجديدة
            $allTables = $newDb->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            $relatedKeywords = [
                'job', 'profession', 'occupation', 'work',
                'skill', 'ability', 'talent',
                'age', 'year', 'old',
                'region', 'area', 'zone', 'location',
                'language', 'lang', 'speak',
                'experience', 'exp', 'work_exp',
                'social', 'marital', 'family',
                'office', 'branch', 'center',
                'airport', 'air', 'flight'
            ];

            $categorizedTables = [];

            foreach ($allTables as $table) {
                $tableLower = strtolower($table);
                $category = 'other';

                foreach ($relatedKeywords as $keyword) {
                    if (strpos($tableLower, $keyword) !== false) {
                        $category = $keyword;
                        break;
                    }
                }

                if ($category !== 'other') {
                    try {
                        $count = $newDb->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                        $columns = $newDb->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);

                        $categorizedTables[$category][] = [
                            'table' => $table,
                            'count' => $count,
                            'columns' => $columns
                        ];
                    } catch (Exception $e) {
                        // تجاهل الجداول التي لا يمكن الوصول إليها
                    }
                }
            }

            // تنظيف وإرسال JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }

            echo json_encode([
                'success' => true,
                'all_tables_count' => count($allTables),
                'categorized_tables' => $categorizedTables,
                'categories_found' => array_keys($categorizedTables),
                'message' => 'تم العثور على ' . count($categorizedTables) . ' فئة من الجداول المرتبطة'
            ], JSON_UNESCAPED_UNICODE);
            exit();
            break;

        default:
            // تنظيف وإرسال JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }

            echo json_encode([
                'success' => false,
                'message' => 'إجراء غير صالح'
            ], JSON_UNESCAPED_UNICODE);
            exit();
    }

} catch (Exception $e) {
    // تنظيف أي output قبل إرسال JSON
    if (ob_get_level() > 0) {
        ob_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage(),
        'error_details' => [
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// تنظيف نهائي
if (ob_get_level() > 0) {
    ob_end_clean();
}

// وظائف مساعدة
function findPossibleTables($db, $suggestedName) {
    try {
        $allTables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $possible = [];

        $keywords = explode('/', $suggestedName);

        foreach ($allTables as $table) {
            foreach ($keywords as $keyword) {
                if (strpos(strtolower($table), strtolower($keyword)) !== false) {
                    $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                    $possible[] = [
                        'table' => $table,
                        'count' => $count,
                        'match_keyword' => $keyword
                    ];
                    break;
                }
            }
        }

        return $possible;
    } catch (Exception $e) {
        return [];
    }
}

function calculateAgeRange($ageId) {
    // تخمين نطاق العمر بناءً على ID
    $ranges = [
        1 => ['range' => '18-25', 'min' => 18, 'max' => 25],
        2 => ['range' => '26-30', 'min' => 26, 'max' => 30],
        3 => ['range' => '31-35', 'min' => 31, 'max' => 35],
        4 => ['range' => '36-40', 'min' => 36, 'max' => 40],
        5 => ['range' => '41-45', 'min' => 41, 'max' => 45]
    ];

    return $ranges[$ageId] ?? ['range' => "$ageId سنة", 'min' => $ageId, 'max' => $ageId];
}

function getLanguageName($langId) {
    $languages = [
        1 => ['ar' => 'العربية', 'en' => 'Arabic', 'code' => 'ar'],
        2 => ['ar' => 'الإنجليزية', 'en' => 'English', 'code' => 'en'],
        3 => ['ar' => 'التاغالوغ', 'en' => 'Tagalog', 'code' => 'tl'],
        4 => ['ar' => 'الأوردو', 'en' => 'Urdu', 'code' => 'ur'],
        5 => ['ar' => 'الهندية', 'en' => 'Hindi', 'code' => 'hi']
    ];

    return $languages[$langId] ?? ['ar' => "لغة $langId", 'en' => "Language $langId", 'code' => "L$langId"];
}

function getExperienceName($expId) {
    $experiences = [
        1 => ['ar' => 'بدون خبرة', 'en' => 'No Experience', 'range' => '0'],
        2 => ['ar' => 'خبرة قليلة', 'en' => 'Little Experience', 'range' => '1-2'],
        3 => ['ar' => 'خبرة متوسطة', 'en' => 'Medium Experience', 'range' => '3-5'],
        4 => ['ar' => 'خبرة عالية', 'en' => 'High Experience', 'range' => '5+']
    ];

    return $experiences[$expId] ?? ['ar' => "خبرة $expId", 'en' => "Experience $expId", 'range' => "$expId"];
}

function getSocialStatusName($socialId) {
    $statuses = [
        1 => ['ar' => 'أعزب', 'en' => 'Single'],
        2 => ['ar' => 'متزوج', 'en' => 'Married'],
        3 => ['ar' => 'مطلق', 'en' => 'Divorced'],
        4 => ['ar' => 'أرمل', 'en' => 'Widow']
    ];

    return $statuses[$socialId] ?? ['ar' => "حالة $socialId", 'en' => "Status $socialId"];
}

function getAirportName($airportId) {
    $airports = [
        1 => ['ar' => 'مطار الملك خالد الدولي', 'en' => 'King Khalid International Airport'],
        2 => ['ar' => 'مطار الملك عبدالعزيز الدولي', 'en' => 'King Abdulaziz International Airport'],
        3 => ['ar' => 'مطار الملك فهد الدولي', 'en' => 'King Fahd International Airport']
    ];

    return $airports[$airportId] ?? ['ar' => "مطار $airportId", 'en' => "Airport $airportId"];
}
?>
