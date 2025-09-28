<?php
/**
 * معالج الملفات المرفوعة - يستخدم الملفات فعلياً وليس قاعدة البيانات
 * Uploaded Files Processor - Uses actual uploaded files, not database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

header('Content-Type: application/json; charset=utf-8');

$config = [
    'host' => '127.0.0.1',
    'port' => 8889,
    'username' => 'root',
    'password' => 'root'
];

try {
    $action = $_POST['action'] ?? 'unknown';

    switch ($action) {
        case 'check_uploaded_files':
            echo json_encode(checkUploadedFiles(), JSON_UNESCAPED_UNICODE);
            break;

        case 'upload_and_analyze':
            echo json_encode(uploadAndAnalyze(), JSON_UNESCAPED_UNICODE);
            break;

        case 'process_uploaded_data':
            echo json_encode(processUploadedData(), JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'إجراء غير صالح'], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

/**
 * فحص الملفات المرفوعة
 */
function checkUploadedFiles() {
    $uploadDir = __DIR__ . '/uploads/';
    $files = [];

    if (is_dir($uploadDir)) {
        $fileList = glob($uploadDir . '*.csv');

        foreach ($fileList as $file) {
            $analysis = analyzeCSVFile($file);

            $files[] = [
                'name' => basename($file),
                'path' => $file,
                'size' => formatFileSize(filesize($file)),
                'records_count' => $analysis['total_rows'],
                'headers' => $analysis['headers'],
                'last_modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
    }

    return [
        'success' => true,
        'files' => $files,
        'total_files' => count($files)
    ];
}

/**
 * رفع وتحليل الملفات
 */
function uploadAndAnalyze() {
    $uploadedFiles = [];
    $uploadDir = __DIR__ . '/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES['files'])) {
        foreach ($_FILES['files']['name'] as $key => $name) {
            if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['files']['tmp_name'][$key];
                $size = $_FILES['files']['size'][$key];

                // حفظ الملف بالاسم الأصلي (مع timestamp لتجنب التضارب)
                $fileName = date('YmdHis') . '_' . $name;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $filePath)) {
                    // تحليل مفصل للملف
                    $analysis = analyzeCSVFile($filePath);

                    $uploadedFiles[] = [
                        'original_name' => $name,
                        'stored_name' => $fileName,
                        'path' => $filePath,
                        'size' => formatFileSize($size),
                        'records_count' => $analysis['total_rows'],
                        'headers' => $analysis['headers'],
                        'preview_data' => $analysis['preview_data'],
                        'table_type' => detectTableType($name, $analysis['headers'])
                    ];
                }
            }
        }
    }

    return [
        'success' => true,
        'files' => $uploadedFiles,
        'message' => 'تم رفع وتحليل ' . count($uploadedFiles) . ' ملف بنجاح'
    ];
}

/**
 * معالجة البيانات المرفوعة فعلياً
 */
function processUploadedData() {
    global $config;

    try {
        // الاتصال بقاعدة البيانات الهدف
        $target_pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname=platinum_new;charset=utf8mb4",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $results = [
            'files_processed' => 0,
            'total_records_inserted' => 0,
            'users_from_files' => 0,
            'maids_from_files' => 0,
            'orders_from_files' => 0,
            'columns_added' => 0,
            'processing_details' => []
        ];

        // الحصول على قائمة الملفات المرفوعة
        $uploadDir = __DIR__ . '/uploads/';
        $uploadedFiles = glob($uploadDir . '*.csv');

        foreach ($uploadedFiles as $filePath) {
            $fileName = basename($filePath);
            $analysis = analyzeCSVFile($filePath);
            $tableType = detectTableType($fileName, $analysis['headers']);

            // معالجة كل ملف حسب نوعه
            switch ($tableType) {
                case 'users':
                    $inserted = processUsersFile($target_pdo, $filePath, $analysis);
                    $results['users_from_files'] += $inserted;
                    $results['processing_details'][] = "معالجة $fileName: $inserted مستخدم";
                    break;

                case 'maids':
                    $inserted = processMaidsFile($target_pdo, $filePath, $analysis);
                    $results['maids_from_files'] += $inserted;
                    $results['processing_details'][] = "معالجة $fileName: $inserted عاملة";
                    break;

                case 'orders':
                    $inserted = processOrdersFile($target_pdo, $filePath, $analysis);
                    $results['orders_from_files'] += $inserted;
                    $results['processing_details'][] = "معالجة $fileName: $inserted طلب";
                    break;

                default:
                    $results['processing_details'][] = "تجاهل $fileName: نوع غير معروف";
            }

            $results['files_processed']++;
            $results['total_records_inserted'] += $inserted ?? 0;
        }

        // إضافة الأعمدة المفقودة
        $results['columns_added'] = addMissingColumns($target_pdo);

        return [
            'success' => true,
            'results' => $results,
            'message' => "تم معالجة {$results['files_processed']} ملف وإدراج {$results['total_records_inserted']} سجل"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في معالجة الملفات: ' . $e->getMessage()
        ];
    }
}

/**
 * معالجة ملف المستخدمين المرفوع
 */
function processUsersFile($pdo, $filePath, $analysis) {
    // إضافة الأعمدة المطلوبة أولاً
    addUserColumns($pdo);

    $insertedCount = 0;

    if (($handle = fopen($filePath, "r")) !== false) {
        // قراءة العناوين
        $headers = fgetcsv($handle, 0, ',');
        $headers = array_map('trim', $headers);

        // تحضير الاستعلام
        $sql = "INSERT IGNORE INTO users (
            name, first_name, last_name, phone, email, password,
            address, city, nationality_id, created_at, updated_at,
            register_from, national_num, phone2, birthdate
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        // معالجة كل صف من الملف
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) === count($headers)) {
                $data = array_combine($headers, array_map('trim', $row));

                // معالجة البيانات
                $name = $data['name'] ?? '';
                $first_name = extractFirstName($name);
                $last_name = extractLastName($name);
                $phone = cleanPhoneNumber($data['phone'] ?? '');
                $email = $data['email'] ?? null;
                $password = $data['password'] ?? null;
                $address = $data['address'] ?? null;
                $city = $data['city'] ?? null;
                $nationality_id = intval($data['nationality_id'] ?? 1);
                $created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
                $updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');
                $national_num = $data['national_num'] ?? null;
                $phone2 = $data['phone2'] ?? null;
                $birthdate = $data['birthdate'] ?? null;

                // تنفيذ الإدراج
                try {
                    $stmt->execute([
                        $name, $first_name, $last_name, $phone, $email, $password,
                        $address, $city, $nationality_id, $created_at, $updated_at,
                        'uploaded_file', $national_num, $phone2, $birthdate
                    ]);

                    $insertedCount++;
                } catch (Exception $e) {
                    // تجاهل الأخطاء (مثل التكرار)
                }
            }
        }

        fclose($handle);
    }

    return $insertedCount;
}

/**
 * معالجة ملف العاملات المرفوع
 */
function processMaidsFile($pdo, $filePath, $analysis) {
    $insertedCount = 0;

    if (($handle = fopen($filePath, "r")) !== false) {
        $headers = fgetcsv($handle, 0, ',');
        $headers = array_map('trim', $headers);

        $sql = "INSERT IGNORE INTO maids (
            name, nationality_id, passport_num, price, salary, religion_id,
            age, birth_date, cv_file, available, status, experience_years,
            experience_status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) === count($headers)) {
                $data = array_combine($headers, array_map('trim', $row));

                try {
                    $stmt->execute([
                        $data['name'] ?? '',
                        intval($data['nationality_id'] ?? 1),
                        $data['passport_num'] ?? '',
                        floatval($data['price'] ?? 0),
                        floatval($data['salary'] ?? 0),
                        intval($data['religion_id'] ?? 1),
                        floatval($data['age'] ?? 25),
                        $data['birth_date'] ?? null,
                        $data['cv_file'] ?? '',
                        1, // available
                        'available',
                        $data['experience_years'] ?? '0',
                        'no_experience',
                        $data['created_at'] ?? date('Y-m-d H:i:s'),
                        $data['updated_at'] ?? date('Y-m-d H:i:s')
                    ]);

                    $insertedCount++;
                } catch (Exception $e) {
                    // تجاهل الأخطاء
                }
            }
        }

        fclose($handle);
    }

    return $insertedCount;
}

/**
 * وظائف مساعدة
 */
function analyzeCSVFile($filePath) {
    $headers = [];
    $previewData = [];
    $totalRows = 0;

    if (($handle = fopen($filePath, "r")) !== false) {
        // قراءة العناوين
        if (($row = fgetcsv($handle, 0, ',')) !== false) {
            $headers = array_map('trim', $row);
        }

        // قراءة البيانات للمعاينة
        $previewCount = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $totalRows++;

            if ($previewCount < 5 && count($row) === count($headers)) {
                $previewData[] = array_combine($headers, array_map('trim', $row));
                $previewCount++;
            }
        }

        fclose($handle);
    }

    return [
        'headers' => $headers,
        'preview_data' => $previewData,
        'total_rows' => $totalRows
    ];
}

function detectTableType($fileName, $headers) {
    $fileName = strtolower($fileName);

    if (strpos($fileName, 'user') !== false) {
        return 'users';
    } elseif (strpos($fileName, 'maid') !== false) {
        return 'maids';
    } elseif (strpos($fileName, 'order') !== false) {
        return 'orders';
    } elseif (strpos($fileName, 'contract') !== false) {
        return 'contracts';
    }

    // فحص العناوين
    if (in_array('email', $headers) && in_array('phone', $headers)) {
        return 'users';
    } elseif (in_array('passport_num', $headers) || in_array('passport_id', $headers)) {
        return 'maids';
    }

    return 'unknown';
}

function addUserColumns($pdo) {
    $columns = [
        'first_name' => 'VARCHAR(255) NULL',
        'last_name' => 'VARCHAR(255) NULL',
        'national_num' => 'VARCHAR(50) NULL',
        'phone2' => 'VARCHAR(50) NULL',
        'birthdate' => 'DATE NULL',
        'register_from' => 'VARCHAR(255) NOT NULL DEFAULT "web"'
    ];

    $added = 0;
    foreach ($columns as $column => $definition) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
            $added++;
        } catch (Exception $e) {
            // العمود موجود مسبقاً
        }
    }

    return $added;
}

function addMissingColumns($pdo) {
    return addUserColumns($pdo);
}

function extractFirstName($fullName) {
    if (empty($fullName)) return '';

    $parts = explode(' ', trim($fullName));
    return trim($parts[0] ?? '');
}

function extractLastName($fullName) {
    if (empty($fullName)) return '';

    $parts = explode(' ', trim($fullName));
    if (count($parts) > 1) {
        return trim(end($parts));
    }

    return '';
}

function cleanPhoneNumber($phone) {
    // إزالة المسافات والرموز
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // إزالة 966 إذا كانت في البداية
    if (substr($phone, 0, 3) === '966') {
        $phone = substr($phone, 3);
    }

    // إضافة 0 في البداية إذا لم تكن موجودة
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

function processOrdersFile($pdo, $filePath, $analysis) {
    // تنفيذ مشابه للمستخدمين
    return 0; // placeholder
}
?>
