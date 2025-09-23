<?php
/**
 * معالج رفع مبسط للاختبار
 */

// تفعيل عرض الأخطاء للتشخيص
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Headers للاستجابة JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// إنشاء جلسة افتراضية للاختبار
if (!isset($_SESSION['migration_user'])) {
    $_SESSION['migration_user'] = [
        'id' => 1,
        'name' => 'Test User'
    ];
}

try {
    $action = $_POST['action'] ?? 'upload';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('يُسمح بطلبات POST فقط');
    }

    if (!isset($_FILES['csv_file'])) {
        throw new Exception('لم يتم رفع أي ملف');
    }

    $file = $_FILES['csv_file'];

    // فحص أخطاء الرفع
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من المسموح في PHP',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح في النموذج',
            UPLOAD_ERR_PARTIAL => 'تم رفع الملف جزئياً فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد tmp مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف للقرص',
            UPLOAD_ERR_EXTENSION => 'امتداد PHP أوقف رفع الملف'
        ];
        throw new Exception($errors[$file['error']] ?? 'خطأ غير معروف في رفع الملف');
    }

    // فحص نوع الملف
    $allowed_types = ['text/csv', 'text/plain', 'application/csv'];
    $file_type = mime_content_type($file['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        // فحص إضافي بالامتداد
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            throw new Exception('نوع الملف غير مدعوم. يُسمح بملفات CSV فقط. نوع الملف المكتشف: ' . $file_type);
        }
    }

    // فحص حجم الملف (10MB حد أقصى)
    $max_size = 10 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        throw new Exception('حجم الملف كبير جداً. الحد الأقصى: 10MB');
    }

    // إنشاء مجلد الحفظ
    $upload_dir = __DIR__ . '/../uploads/' . date('Y/m/d');
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('فشل في إنشاء مجلد الحفظ');
        }
    }

    // إنشاء اسم ملف فريد
    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
    $file_path = $upload_dir . '/' . $filename;

    // نقل الملف
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('فشل في حفظ الملف');
    }

    // قراءة الملف للاختبار
    $csv_data = [];
    $headers = [];

    if (($handle = fopen($file_path, "r")) !== false) {
        // قراءة العناوين
        if (($row = fgetcsv($handle, 0, ',')) !== false) {
            $headers = array_map('trim', $row);
        }

        // قراءة أول 5 صفوف للمعاينة
        $row_count = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false && $row_count < 5) {
            if (count($row) === count($headers)) {
                $csv_data[] = array_combine($headers, array_map('trim', $row));
                $row_count++;
            }
        }
        fclose($handle);
    }

    if ($action === 'preview') {
        echo json_encode([
            'success' => true,
            'data' => [
                'headers' => $headers,
                'preview' => $csv_data,
                'total_rows' => count($csv_data),
                'file_info' => [
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'type' => $file_type
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح',
            'file_info' => [
                'name' => $file['name'],
                'size' => $file['size'],
                'type' => $file_type,
                'path' => $file_path,
                'headers' => $headers,
                'sample_data' => $csv_data
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'php_version' => PHP_VERSION,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'tmp_dir' => sys_get_temp_dir(),
            'uploads_dir' => __DIR__ . '/../uploads/',
            'uploads_writable' => is_writable(__DIR__ . '/../uploads/') ? 'yes' : 'no'
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
