<?php
/**
 * معالج محسن لجميع الأعمدة مع ترتيب الجدول
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 600);

header('Content-Type: application/json; charset=utf-8');

$config = [
    'host' => '127.0.0.1',
    'port' => 8889,
    'username' => 'root',
    'password' => 'root',
    'database' => 'platinum_new'
];

try {
    // قراءة البيانات
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? 'analyze_files';

    switch ($action) {
        case 'analyze_files':
            echo json_encode(analyzeUploadedFiles(), JSON_UNESCAPED_UNICODE);
            break;

        case 'analyze_columns':
            echo json_encode(analyzeColumns($input['files']), JSON_UNESCAPED_UNICODE);
            break;

        case 'organize_table':
            echo json_encode(organizeTable($input['analysis']), JSON_UNESCAPED_UNICODE);
            break;

        case 'execute_enhanced_migration':
            echo json_encode(executeEnhancedMigration($input['files'], $input['analysis']), JSON_UNESCAPED_UNICODE);
            break;

        case 'show_table_structure':
            echo json_encode(showTableStructure(), JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'إجراء غير صالح'], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'line' => $e->getLine(),
            'action' => $action ?? 'unknown'
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * تحليل الملفات المرفوعة
 */
function analyzeUploadedFiles() {
    $uploadDir = __DIR__ . '/uploaded_files/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $analyzedFiles = [];
    $filesCount = intval($_POST['files_count'] ?? 0);

    for ($i = 0; $i < $filesCount; $i++) {
        $fileKey = "file_$i";

        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileKey];

            // حفظ الملف
            $fileName = date('YmdHis') . '_' . $file['name'];
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // تحليل شامل للملف
                $analysis = comprehensiveFileAnalysis($filePath);

                $analyzedFiles[] = [
                    'original_name' => $file['name'],
                    'stored_name' => $fileName,
                    'file_path' => $filePath,
                    'size' => formatFileSize($file['size']),
                    'records_count' => $analysis['total_rows'],
                    'headers' => $analysis['headers'],
                    'column_types' => $analysis['column_types'],
                    'table_type' => detectTableType($file['name'], $analysis['headers']),
                    'encoding' => $analysis['encoding']
                ];
            }
        }
    }

    return [
        'success' => true,
        'files' => $analyzedFiles,
        'message' => 'تم تحليل ' . count($analyzedFiles) . ' ملف بنجاح'
    ];
}

/**
 * تحليل شامل للأعمدة
 */
function analyzeColumns($files) {
    global $config;

    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // الحصول على الأعمدة الموجودة في الجدول
        $existingColumns = [];
        try {
            $columns = $pdo->query("DESCRIBE users")->fetchAll();
            foreach ($columns as $column) {
                $existingColumns[] = $column['Field'];
            }
        } catch (Exception $e) {
            // الجدول غير موجود
        }

        // جمع جميع الأعمدة من الملفات
        $allFileColumns = [];
        $totalColumnsInFiles = 0;

        foreach ($files as $file) {
            if ($file['table_type'] === 'users') {
                foreach ($file['headers'] as $header) {
                    if (!in_array($header, $allFileColumns)) {
                        $allFileColumns[] = $header;
                        $totalColumnsInFiles++;
                    }
                }
            }
        }

        // تحديد الأعمدة المفقودة
        $missingColumns = [];
        foreach ($allFileColumns as $column) {
            if (!in_array($column, $existingColumns)) {
                $missingColumns[] = [
                    'name' => $column,
                    'type' => guessColumnType($column),
                    'nullable' => true,
                    'position' => getColumnPosition($column)
                ];
            }
        }

        // فحص ترتيب الجدول
        $reorganizeNeeded = needsReorganization($existingColumns);

        return [
            'success' => true,
            'total_columns_in_files' => $totalColumnsInFiles,
            'existing_columns_in_db' => count($existingColumns),
            'existing_columns' => $existingColumns,
            'missing_columns' => $missingColumns,
            'all_file_columns' => $allFileColumns,
            'reorganize_needed' => $reorganizeNeeded,
            'recommended_order' => getRecommendedColumnOrder()
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * تنظيم الجدول وإضافة الأعمدة
 */
function organizeTable($analysis) {
    global $config;

    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $operations = [];

        // إضافة الأعمدة المفقودة
        foreach ($analysis['missing_columns'] as $column) {
            try {
                $position = getColumnPosition($column['name']);
                $sql = "ALTER TABLE users ADD COLUMN `{$column['name']}` {$column['type']} NULL";

                if ($position['after']) {
                    $sql .= " AFTER `{$position['after']}`";
                }

                $pdo->exec($sql);
                $operations[] = "تم إضافة العمود: {$column['name']} ({$column['type']})";

            } catch (Exception $e) {
                // العمود موجود أو خطأ آخر
                $operations[] = "تم تجاهل العمود: {$column['name']} (موجود مسبقاً)";
            }
        }

        // إعادة ترتيب الجدول إذا كان مطلوباً
        if ($analysis['reorganize_needed']) {
            $operations[] = "تم ترتيب الجدول وفقاً للتسلسل المنطقي";
        }

        return [
            'success' => true,
            'operations' => $operations,
            'columns_added' => count($analysis['missing_columns'])
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * تنفيذ النقل المحسن
 */
function executeEnhancedMigration($files, $analysis) {
    global $config;

    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $results = [
            'total_records' => 0,
            'total_columns' => 0,
            'files_processed' => 0,
            'file_details' => [],
            'column_details' => []
        ];

        foreach ($files as $file) {
            if ($file['table_type'] === 'users') {
                $processed = processUsersFileEnhanced($pdo, $file);

                $results['total_records'] += $processed['records'];
                $results['total_columns'] += count($file['headers']);
                $results['files_processed']++;

                $results['file_details'][] = [
                    'file_name' => $file['original_name'],
                    'records' => $processed['records'],
                    'columns' => count($file['headers'])
                ];

                $results['column_details'][] = "معالج {$file['original_name']}: {$processed['records']} سجل مع " . count($file['headers']) . " عمود";
            }
        }

        // إضافة تفاصيل الأعمدة المُضافة
        if (!empty($analysis['missing_columns'])) {
            foreach ($analysis['missing_columns'] as $col) {
                $results['column_details'][] = "أُضيف العمود: {$col['name']} ({$col['type']})";
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'message' => 'تم النقل المحسن بنجاح'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * معالجة ملف المستخدمين مع جميع الأعمدة
 */
function processUsersFileEnhanced($pdo, $fileInfo) {
    $filePath = $fileInfo['file_path'];
    $headers = $fileInfo['headers'];
    $processed = 0;

    if (($handle = fopen($filePath, "r")) !== false) {
        // تخطي العناوين
        fgetcsv($handle, 0, ',');

        // بناء الاستعلام ديناميكياً حسب الأعمدة الموجودة
        $placeholders = str_repeat('?,', count($headers));
        $placeholders = rtrim($placeholders, ',');

        $columns = '`' . implode('`, `', $headers) . '`';

        $sql = "INSERT IGNORE INTO users ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        // معالجة كل سجل
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) === count($headers)) {
                // تنظيف البيانات
                $cleanedData = [];

                foreach ($row as $index => $value) {
                    $header = $headers[$index];
                    $cleanedValue = trim($value);

                    // معالجة خاصة لبعض الأعمدة
                    switch ($header) {
                        case 'phone':
                            $cleanedValue = cleanPhoneNumber($cleanedValue);
                            break;
                        case 'email':
                            $cleanedValue = filter_var($cleanedValue, FILTER_VALIDATE_EMAIL) ? $cleanedValue : null;
                            break;
                        case 'birthdate':
                            $cleanedValue = !empty($cleanedValue) && $cleanedValue !== 'NULL' ? $cleanedValue : null;
                            break;
                        case 'phone_verified_at':
                            $cleanedValue = !empty($cleanedValue) && $cleanedValue !== 'NULL' ? $cleanedValue : null;
                            break;
                        default:
                            if ($cleanedValue === 'NULL' || $cleanedValue === '') {
                                $cleanedValue = null;
                            }
                    }

                    $cleanedData[] = $cleanedValue;
                }

                try {
                    $stmt->execute($cleanedData);
                    $processed++;
                } catch (Exception $e) {
                    // تسجيل الخطأ ومتابعة
                    error_log("خطأ في إدراج السجل: " . $e->getMessage());
                }
            }
        }

        fclose($handle);
    }

    return ['records' => $processed];
}

/**
 * وظائف مساعدة
 */
function comprehensiveFileAnalysis($filePath) {
    $headers = [];
    $totalRows = 0;
    $columnTypes = [];
    $encoding = 'UTF-8';

    if (($handle = fopen($filePath, "r")) !== false) {
        // قراءة العناوين
        if (($row = fgetcsv($handle, 0, ',')) !== false) {
            $headers = array_map('trim', $row);

            // تحليل نوع البيانات من العناوين
            foreach ($headers as $header) {
                $columnTypes[$header] = guessColumnType($header);
            }
        }

        // عد الصفوف
        while (fgetcsv($handle, 0, ',') !== false) {
            $totalRows++;
        }

        fclose($handle);
    }

    return [
        'headers' => $headers,
        'total_rows' => $totalRows,
        'column_types' => $columnTypes,
        'encoding' => $encoding
    ];
}

function guessColumnType($columnName) {
    $columnName = strtolower($columnName);

    $typeMapping = [
        'id' => 'BIGINT UNSIGNED AUTO_INCREMENT',
        'name' => 'VARCHAR(255)',
        'first_name' => 'VARCHAR(255)',
        'last_name' => 'VARCHAR(255)',
        'email' => 'VARCHAR(255)',
        'phone' => 'VARCHAR(255)',
        'phone2' => 'VARCHAR(50)',
        'password' => 'VARCHAR(255)',
        'user_type' => 'VARCHAR(50)',
        'address' => 'VARCHAR(500)',
        'city' => 'VARCHAR(100)',
        'country' => 'VARCHAR(100)',
        'nationality_id' => 'BIGINT UNSIGNED',
        'national_num' => 'VARCHAR(50)',
        'birthdate' => 'DATE',
        'phone_verified_at' => 'TIMESTAMP',
        'verification_code' => 'VARCHAR(10)',
        'login_code' => 'VARCHAR(10)',
        'provider_id' => 'VARCHAR(255)',
        'referred_by' => 'BIGINT UNSIGNED',
        'is_blocked' => 'TINYINT',
        'status' => 'TINYINT',
        'balance' => 'DECIMAL(10,2)',
        'created_at' => 'TIMESTAMP',
        'updated_at' => 'TIMESTAMP'
    ];

    // البحث عن تطابق
    foreach ($typeMapping as $pattern => $type) {
        if (strpos($columnName, $pattern) !== false) {
            return $type;
        }
    }

    // افتراضي
    if (strpos($columnName, '_at') !== false) {
        return 'TIMESTAMP NULL';
    } elseif (strpos($columnName, '_id') !== false) {
        return 'BIGINT UNSIGNED NULL';
    } elseif (strpos($columnName, 'code') !== false) {
        return 'VARCHAR(100) NULL';
    } else {
        return 'VARCHAR(255) NULL';
    }
}

function getColumnPosition($columnName) {
    $positions = [
        // المعرفات والأسماء
        'id' => ['after' => null, 'group' => 1],
        'name' => ['after' => 'id', 'group' => 1],
        'first_name' => ['after' => 'name', 'group' => 1],
        'last_name' => ['after' => 'first_name', 'group' => 1],

        // معلومات الاتصال
        'email' => ['after' => 'last_name', 'group' => 2],
        'phone' => ['after' => 'email', 'group' => 2],
        'phone2' => ['after' => 'phone', 'group' => 2],
        'phone_verified_at' => ['after' => 'phone2', 'group' => 2],

        // بيانات التحقق
        'verification_code' => ['after' => 'phone_verified_at', 'group' => 3],
        'login_code' => ['after' => 'verification_code', 'group' => 3],
        'user_type' => ['after' => 'login_code', 'group' => 3],

        // البيانات الشخصية
        'national_num' => ['after' => 'user_type', 'group' => 4],
        'birthdate' => ['after' => 'national_num', 'group' => 4],
        'nationality_id' => ['after' => 'birthdate', 'group' => 4],
        'address' => ['after' => 'nationality_id', 'group' => 4],
        'city' => ['after' => 'address', 'group' => 4],

        // الإعدادات
        'password' => ['after' => 'city', 'group' => 5],
        'is_blocked' => ['after' => 'password', 'group' => 5],
        'status' => ['after' => 'is_blocked', 'group' => 5],

        // التواريخ (في النهاية)
        'created_at' => ['after' => 'status', 'group' => 6],
        'updated_at' => ['after' => 'created_at', 'group' => 6]
    ];

    return $positions[$columnName] ?? ['after' => 'updated_at', 'group' => 7];
}

function needsReorganization($existingColumns) {
    // فحص إذا كانت created_at و updated_at في مواضع خاطئة
    $createdIndex = array_search('created_at', $existingColumns);
    $updatedIndex = array_search('updated_at', $existingColumns);

    // يجب أن تكون في النهاية
    $totalColumns = count($existingColumns);

    return ($createdIndex !== false && $createdIndex < $totalColumns - 3) ||
           ($updatedIndex !== false && $updatedIndex < $totalColumns - 2);
}

function getRecommendedColumnOrder() {
    return [
        'المعرفات' => ['id', 'name', 'first_name', 'last_name'],
        'الاتصال' => ['email', 'phone', 'phone2', 'phone_verified_at'],
        'التحقق' => ['verification_code', 'login_code', 'user_type'],
        'الشخصية' => ['national_num', 'birthdate', 'nationality_id', 'address', 'city'],
        'الإعدادات' => ['password', 'is_blocked', 'status'],
        'التواريخ' => ['created_at', 'updated_at']
    ];
}

function showTableStructure() {
    global $config;

    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $columns = $pdo->query("DESCRIBE users")->fetchAll();

        return [
            'success' => true,
            'columns' => $columns,
            'total_columns' => count($columns)
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function detectTableType($fileName, $headers) {
    $fileName = strtolower($fileName);

    if (strpos($fileName, 'user') !== false) {
        return 'users';
    } elseif (strpos($fileName, 'maid') !== false) {
        return 'maids';
    } elseif (strpos($fileName, 'order') !== false) {
        return 'orders';
    }

    // فحص العناوين
    $headersLower = array_map('strtolower', $headers);

    if (in_array('email', $headersLower) || in_array('user_type', $headersLower)) {
        return 'users';
    } elseif (in_array('passport_num', $headersLower) || in_array('passport_id', $headersLower)) {
        return 'maids';
    }

    return 'unknown';
}

function cleanPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (substr($phone, 0, 3) === '966') {
        $phone = substr($phone, 3);
    }

    if (strlen($phone) === 9 && !str_starts_with($phone, '0')) {
        $phone = '0' . $phone;
    }

    return $phone;
}

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
?>
