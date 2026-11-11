<?php
/**
 * نظام النقل الآمن المبسط - Backend
 */

// منع جميع الأخطاء والتحذيرات
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);

// تنظيف كامل للـ output buffer
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// إعداد headers نظيفة
header_remove(); // إزالة جميع الـ headers السابقة
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // تنظيف أي output سابق
    if (ob_get_level() > 0) {
        ob_clean();
    }

    // إنشاء المجلدات المطلوبة
    $exportDir = __DIR__ . '/exports';
    $reportDir = __DIR__ . '/reports';

    if (!is_dir($exportDir)) {
        @mkdir($exportDir, 0755, true);
    }

    if (!is_dir($reportDir)) {
        @mkdir($reportDir, 0755, true);
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? 'status';

    // الاتصال بالقواعد
    try {
        $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);

        $newDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
    } catch (Exception $e) {
        throw new Exception("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
    }

    switch ($action) {
        case 'check_status':
            // فحص حالة قواعد البيانات
            $oldTables = [
                'users' => getTableCount($oldDb, 'users'),
                'customers' => getTableCount($oldDb, 'customers'),
                'cvs' => getTableCount($oldDb, 'cvs'),
                'contracts' => getTableCount($oldDb, 'contracts'),
                'requests' => getTableCount($oldDb, 'requests'),
                'nationalities' => getTableCount($oldDb, 'nationalities'),
                'countries' => getTableCount($oldDb, 'countries')
            ];

            $newTables = [
                'users' => getTableCount($newDb, 'users'),
                'maids' => getTableCount($newDb, 'maids'),
                'contracts' => getTableCount($newDb, 'contracts'),
                'requests' => getTableCount($newDb, 'requests'),
                'nationalities' => getTableCount($newDb, 'nationalities'),
                'countries' => getTableCount($newDb, 'countries')
            ];

            sendJsonResponse([
                'success' => true,
                'old_db' => $oldTables,
                'new_db' => $newTables,
                'message' => 'فحص قواعد البيانات مكتمل'
            ]);
            break;

        case 'analyze_table':
            // تحليل جدول معين
            $tableName = $_GET['table'] ?? '';
            if (empty($tableName)) {
                throw new Exception('اسم الجدول مطلوب');
            }

            // التحقق من وجود الجدول
            $tableExists = $oldDb->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
            if (!$tableExists) {
                throw new Exception("الجدول $tableName غير موجود");
            }

            // عدد السجلات
            $count = $oldDb->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();

            // الأعمدة
            $columns = $oldDb->query("DESCRIBE $tableName")->fetchAll(PDO::FETCH_COLUMN);

            // عينة من البيانات
            $sampleData = $oldDb->query("SELECT * FROM $tableName LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

            sendJsonResponse([
                'success' => true,
                'table' => $tableName,
                'count' => $count,
                'columns' => $columns,
                'sample_data' => $sampleData,
                'message' => "تحليل الجدول $tableName مكتمل"
            ]);
            break;

        case 'export_table':
            // تصدير جدول إلى CSV
            $tableName = $_GET['table'] ?? '';
            if (empty($tableName)) {
                throw new Exception('اسم الجدول مطلوب');
            }

            // التحقق من وجود الجدول
            $tableExists = $oldDb->query("SHOW TABLES LIKE '$tableName'")->rowCount() > 0;
            if (!$tableExists) {
                throw new Exception("الجدول $tableName غير موجود");
            }

            // إنشاء مجلد exports إذا لم يكن موجوداً
            $exportDir = __DIR__ . '/exports';
            if (!is_dir($exportDir)) {
                mkdir($exportDir, 0755, true);
            }

            // اسم الملف
            $filename = $tableName . '_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = $exportDir . '/' . $filename;

            // تصدير البيانات
            $stmt = $oldDb->query("SELECT * FROM $tableName");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($data)) {
                throw new Exception("الجدول $tableName فارغ");
            }

            // كتابة CSV
            $file = fopen($filepath, 'w');

            // كتابة العناوين
            fputcsv($file, array_keys($data[0]));

            // كتابة البيانات
            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);

            // حساب حجم الملف
            $filesize = formatFileSize(filesize($filepath));

            sendJsonResponse([
                'success' => true,
                'table' => $tableName,
                'exported' => count($data),
                'filename' => $filename,
                'filepath' => $filepath,
                'filesize' => $filesize,
                'message' => "تم تصدير $tableName بنجاح - " . count($data) . " سجل"
            ]);
            break;

        case 'batch_migrate':
            // النقل التدريجي
            $tableName = $_POST['table'] ?? '';
            $batchSize = (int)($_POST['batch_size'] ?? 100);
            $offset = (int)($_POST['offset'] ?? 0);
            $columnMapping = json_decode($_POST['column_mapping'] ?? '{}', true);

            if (empty($tableName)) {
                throw new Exception('اسم الجدول مطلوب');
            }

            // جلب البيانات
            $stmt = $oldDb->prepare("SELECT * FROM $tableName LIMIT $batchSize OFFSET $offset");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($data)) {
                sendJsonResponse([
                    'success' => true,
                    'processed' => 0,
                    'message' => 'لا توجد بيانات للمعالجة',
                    'completed' => true
                ]);
                break;
            }

            // معالجة البيانات وإدراجها
            $processed = 0;
            $errors = [];

            foreach ($data as $row) {
                try {
                    // تطبيق تطابق الأعمدة
                    $transformedRow = applyColumnMapping($row, $columnMapping);

                    // إدراج في قاعدة البيانات الجديدة
                    $targetTable = getTargetTable($tableName);
                    insertRow($newDb, $targetTable, $transformedRow);

                    $processed++;
                } catch (Exception $e) {
                    $errors[] = "خطأ في السجل ID {$row['id']}: " . $e->getMessage();
                }
            }

            sendJsonResponse([
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'error_count' => count($errors),
                'batch_size' => $batchSize,
                'offset' => $offset,
                'message' => "تم معالجة $processed سجل من $tableName"
            ]);
            break;

        case 'verify_migration':
            // التحقق من النقل
            $verificationResults = [];

            $tablePairs = [
                'users' => 'users',
                'cvs' => 'maids',
                'contracts' => 'contracts',
                'requests' => 'requests'
            ];

            foreach ($tablePairs as $oldTable => $newTable) {
                $oldCount = getTableCount($oldDb, $oldTable);
                $newCount = getTableCount($newDb, $newTable);

                $verificationResults[$oldTable] = [
                    'old_count' => $oldCount,
                    'new_count' => $newCount,
                    'difference' => $oldCount - $newCount,
                    'status' => $oldCount === $newCount ? 'مطابق' : 'غير مطابق',
                    'percentage' => $oldCount > 0 ? round(($newCount / $oldCount) * 100, 2) : 0
                ];
            }

            sendJsonResponse([
                'success' => true,
                'verification' => $verificationResults,
                'message' => 'التحقق من النقل مكتمل'
            ]);
            break;

        case 'generate_report':
            // إنشاء تقرير مفصل
            $report = [
                'timestamp' => date('Y-m-d H:i:s'),
                'database_status' => 'فحص قواعد البيانات',
                'migration_summary' => 'ملخص النقل',
                'detailed_analysis' => 'تحليل مفصل'
            ];

            // حفظ التقرير كملف
            $reportFile = __DIR__ . '/reports/migration_report_' . date('Y-m-d_H-i-s') . '.json';
            $reportDir = dirname($reportFile);

            if (!is_dir($reportDir)) {
                mkdir($reportDir, 0755, true);
            }

            file_put_contents($reportFile, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            sendJsonResponse([
                'success' => true,
                'report' => $report,
                'report_file' => basename($reportFile),
                'message' => 'تم إنشاء التقرير بنجاح'
            ]);
            break;

        default:
            throw new Exception('إجراء غير صالح: ' . $action);
    }

} catch (Throwable $e) {
    // تنظيف شامل قبل إرسال خطأ JSON
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code(500);
    header_remove();
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'message' => 'خطأ في الخادم: ' . $e->getMessage(),
        'error_details' => [
            'line' => $e->getLine(),
            'file' => basename($e->getFile()),
            'action' => $action ?? 'غير محدد'
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// الوظائف المساعدة
function getTableCount($db, $tableName) {
    try {
        return (int)$db->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
    } catch (Exception $e) {
        return 0; // الجدول غير موجود
    }
}

function applyColumnMapping($row, $columnMapping) {
    if (empty($columnMapping)) {
        return $row; // لا توجد تحويلات
    }

    $transformedRow = [];

    foreach ($columnMapping as $oldColumn => $mapping) {
        $targetColumn = $mapping['target'];
        $transform = $mapping['transform'] ?? null;

        if (isset($row[$oldColumn])) {
            $value = $row[$oldColumn];

            // تطبيق التحويل إذا وُجد
            if ($transform) {
                $value = applyTransform($value, $transform);
            }

            $transformedRow[$targetColumn] = $value;
        }
    }

    return $transformedRow;
}

function applyTransform($value, $transform) {
    // أمثلة على التحويلات
    switch ($transform) {
        case 'cv_status_to_available':
            return $value == 0 ? 1 : 0; // عكس القيمة

        case 'split_name':
            $parts = explode(' ', $value, 2);
            return [
                'first_name' => $parts[0] ?? '',
                'last_name' => $parts[1] ?? ''
            ];

        case 'national_to_nationality':
            return $value; // تحويل بسيط

        default:
            return $value; // لا تحويل
    }
}

function getTargetTable($sourceTable) {
    $mapping = [
        'users' => 'users',
        'cvs' => 'maids',
        'contracts' => 'contracts',
        'requests' => 'requests',
        'nationalities' => 'nationalities',
        'countries' => 'countries'
    ];

    return $mapping[$sourceTable] ?? $sourceTable;
}

function insertRow($db, $tableName, $data) {
    if (empty($data)) {
        throw new Exception('لا توجد بيانات للإدراج');
    }

    $columns = array_keys($data);
    $placeholders = ':' . implode(', :', $columns);
    $columnsList = implode(', ', $columns);

    $sql = "INSERT INTO $tableName ($columnsList) VALUES ($placeholders)
            ON DUPLICATE KEY UPDATE ";

    $updateParts = [];
    foreach ($columns as $column) {
        if ($column !== 'id') { // تجاهل ID في التحديث
            $updateParts[] = "$column = VALUES($column)";
        }
    }
    $sql .= implode(', ', $updateParts);

    $stmt = $db->prepare($sql);
    $stmt->execute($data);
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;

    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }

    return round($bytes, 2) . ' ' . $units[$unitIndex];
}

function sendJsonResponse($data, $success = true) {
    // تنظيف شامل
    while (ob_get_level()) {
        ob_end_clean();
    }

    if (!$success) {
        http_response_code(400);
    } else {
        http_response_code(200);
    }

    // إعادة تعيين الـ headers
    header_remove();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $json = json_encode($data, JSON_UNESCAPED_UNICODE);

    if ($json === false) {
        // فشل في تحويل JSON
        $json = json_encode([
            'success' => false,
            'message' => 'خطأ في تحويل البيانات إلى JSON: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
    }

    echo $json;
    exit();
}
?>
