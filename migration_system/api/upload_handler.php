<?php
/**
 * معالج رفع وإدارة ملفات النقل
 * Upload and Migration Handler
 */

// تفعيل عرض الأخطاء للتشخيص
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// إنشاء جلسة افتراضية للاختبار
if (!isset($_SESSION['migration_user'])) {
    $_SESSION['migration_user'] = [
        'id' => 1,
        'name' => 'Migration Admin',
        'role' => 'admin'
    ];
}

try {
    $action = $_POST['action'] ?? 'upload';

    switch ($action) {
        case 'preview':
            handlePreview();
            break;

        case 'upload':
            handleUpload();
            break;

        default:
            sendJsonResponse(['success' => false, 'message' => 'إجراء غير صالح']);
    }

} catch (Exception $e) {
    error_log("Upload Handler Error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

/**
 * معاينة محتوى الملف
 */
function handlePreview() {
    try {
        $file = handleFileUpload('csv_file');
        $source_table = $_POST['source_table'] ?? '';

        if (empty($source_table)) {
            throw new Exception('نوع الجدول غير محدد');
        }

        $csv_data = readCsvFile($file['file_path'], ',', 100);

        // حذف الملف المؤقت بعد القراءة
        unlink($file['file_path']);

        sendJsonResponse([
            'success' => true,
            'data' => [
                'headers' => $csv_data['headers'],
                'preview' => array_slice($csv_data['data'], 0, 10),
                'total_rows' => $csv_data['total_rows'],
                'file_info' => [
                    'name' => $file['original_name'],
                    'size' => $file['size']
                ]
            ]
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * رفع ومعالجة الملف
 */
function handleUpload() {
    global $db;

    try {
        // التحقق من البيانات المطلوبة
        $source_table = $_POST['source_table'] ?? '';
        $target_table = $_POST['target_table'] ?? '';
        $batch_name = $_POST['batch_name'] ?? '';
        $batch_size = intval($_POST['batch_size'] ?? 500);

        if (empty($source_table) || empty($target_table) || empty($batch_name)) {
            throw new Exception('بيانات مطلوبة مفقودة');
        }

        // رفع الملف
        $file = handleFileUpload('csv_file');

        // إنشاء دفعة النقل
        $batch_id = createMigrationBatch(
            $batch_name,
            $source_table,
            $target_table,
            $file['filename'],
            $_SESSION['migration_user']['id']
        );

        // حفظ مسار الملف
        $db->update('migration_batches', [
            'file_path' => $file['file_path']
        ], "id = {$batch_id}");

        // قراءة محتوى الملف
        $csv_data = readCsvFile($file['file_path']);

        // تحديث العدد الإجمالي للسجلات
        updateBatchStatus($batch_id, 'pending', [
            'total_records' => $csv_data['total_rows']
        ]);

        // حفظ البيانات في الجدول المؤقت للمعالجة
        saveTempImportData($batch_id, $csv_data['data']);

        // بدء المعالجة في الخلفية
        if ($_POST['validate_before_import'] ?? false) {
            processBatchInBackground($batch_id, [
                'validate' => true,
                'backup' => $_POST['backup_before_import'] ?? false,
                'skip_duplicates' => $_POST['skip_duplicates'] ?? false,
                'auto_rollback' => $_POST['auto_rollback'] ?? false,
                'batch_size' => $batch_size
            ]);
        } else {
            processBatchInBackground($batch_id, [
                'validate' => false,
                'backup' => $_POST['backup_before_import'] ?? false,
                'skip_duplicates' => $_POST['skip_duplicates'] ?? false,
                'auto_rollback' => $_POST['auto_rollback'] ?? false,
                'batch_size' => $batch_size
            ]);
        }

        logActivity('file_upload', "Uploaded file: {$file['original_name']} for batch: {$batch_name}");

        sendJsonResponse([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح وبدء المعالجة',
            'batch_id' => $batch_id
        ]);

    } catch (Exception $e) {
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * حفظ البيانات المؤقتة للمعالجة
 */
function saveTempImportData($batch_id, $data) {
    global $db;

    $stmt = $db->query("
        INSERT INTO temp_import_data (batch_id, row_number, raw_data)
        VALUES (?, ?, ?)
    ", [], 'migration');

    foreach ($data as $index => $row) {
        $db->query("
            INSERT INTO temp_import_data (batch_id, row_number, raw_data)
            VALUES (?, ?, ?)
        ", [
            $batch_id,
            $index + 1,
            json_encode($row, JSON_UNESCAPED_UNICODE)
        ], 'migration');
    }
}

/**
 * معالجة الدفعة في الخلفية
 */
function processBatchInBackground($batch_id, $options = []) {
    // في بيئة الإنتاج، يمكن استخدام queue system مثل Redis أو RabbitMQ
    // هنا سنستخدم مُعالجة مباشرة مع تحديث الحالة

    // يمكن استخدام exec() لتشغيل سكريبت منفصل
    $command = "php " . __DIR__ . "/../processors/batch_processor.php {$batch_id} '" .
               json_encode($options, JSON_UNESCAPED_UNICODE) . "' > /dev/null 2>&1 &";

    if (function_exists('exec')) {
        exec($command);
    } else {
        // معالجة مباشرة إذا لم يكن exec متاح
        require_once '../processors/batch_processor.php';
        processBatch($batch_id, $options);
    }
}

/**
 * التحقق من نوع الملف وقراءته
 */
function readFileByType($file_path, $extension) {
    switch (strtolower($extension)) {
        case 'csv':
            return readCsvFile($file_path);

        case 'xlsx':
            return readExcelFile($file_path);

        default:
            throw new Exception('نوع ملف غير مدعوم: ' . $extension);
    }
}

/**
 * قراءة ملف Excel (يحتاج PhpSpreadsheet)
 */
function readExcelFile($file_path) {
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        throw new Exception('مكتبة PhpSpreadsheet غير متوفرة لقراءة ملفات Excel');
    }

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        if (empty($data)) {
            throw new Exception('الملف فارغ');
        }

        $headers = array_shift($data);
        $result_data = [];

        foreach ($data as $row) {
            if (array_filter($row)) { // تجاهل الصفوف الفارغة
                $result_data[] = array_combine($headers, $row);
            }
        }

        return [
            'headers' => $headers,
            'data' => $result_data,
            'total_rows' => count($result_data)
        ];

    } catch (Exception $e) {
        throw new Exception('خطأ في قراءة ملف Excel: ' . $e->getMessage());
    }
}
?>
