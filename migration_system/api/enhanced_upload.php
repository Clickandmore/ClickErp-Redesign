<?php
/**
 * معالج رفع محسن للملفات الكبيرة والمعقدة
 * Enhanced Upload Handler for Large Complex Files
 */

// زيادة حدود PHP للملفات الكبيرة
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 600);
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');

// تفعيل عرض الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers للاستجابة
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

// إنشاء جلسة افتراضية
if (!isset($_SESSION['migration_user'])) {
    $_SESSION['migration_user'] = ['id' => 1, 'name' => 'Migration Admin'];
}

try {
    // فحص الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('يُسمح بطلبات POST فقط');
    }

    $action = $_POST['action'] ?? 'upload';

    // فحص وجود الملف
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من المسموح في إعدادات PHP',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح في النموذج',
            UPLOAD_ERR_PARTIAL => 'تم رفع الملف جزئياً فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد tmp مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف',
            UPLOAD_ERR_EXTENSION => 'امتداد PHP منع رفع الملف'
        ];

        $error_code = $_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        throw new Exception($error_messages[$error_code] ?? 'خطأ غير معروف في رفع الملف');
    }

    $file = $_FILES['csv_file'];

    // التحقق من نوع وامتداد الملف
    $allowed_types = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, ['csv']) && !in_array($file['type'], $allowed_types)) {
        throw new Exception('نوع الملف غير مدعوم. يُسمح بملفات CSV فقط. نوع الملف: ' . $file['type']);
    }

    // التحقق من حجم الملف (100MB حد أقصى)
    $max_size = 100 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        throw new Exception('حجم الملف كبير جداً. الحد الأقصى: 100MB. حجم الملف: ' . formatFileSize($file['size']));
    }

    // إنشاء مجلد الحفظ
    $upload_dir = __DIR__ . '/../uploads/' . date('Y/m/d');
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('فشل في إنشاء مجلد الحفظ: ' . $upload_dir);
        }
    }

    // التأكد من أن المجلد قابل للكتابة
    if (!is_writable($upload_dir)) {
        throw new Exception('مجلد الحفظ غير قابل للكتابة: ' . $upload_dir);
    }

    // إنشاء اسم ملف فريد
    $filename = date('YmdHis') . '_' . uniqid() . '.csv';
    $file_path = $upload_dir . '/' . $filename;

    // نقل الملف
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('فشل في حفظ الملف إلى: ' . $file_path);
    }

    // قراءة وتحليل الملف
    $csv_data = parseComplexCsv($file_path);

    if (empty($csv_data['headers'])) {
        unlink($file_path); // حذف الملف عند الخطأ
        throw new Exception('لم يتم العثور على عناوين في الملف');
    }

    if (empty($csv_data['data'])) {
        unlink($file_path); // حذف الملف عند الخطأ
        throw new Exception('الملف فارغ أو لا يحتوي على بيانات صالحة');
    }

    // إرسال الاستجابة حسب نوع الطلب
    if ($action === 'preview') {
        // معاينة البيانات (أول 10 صفوف)
        $preview_data = array_slice($csv_data['data'], 0, 10);

        echo json_encode([
            'success' => true,
            'data' => [
                'headers' => $csv_data['headers'],
                'preview' => $preview_data,
                'total_rows' => $csv_data['total_rows'],
                'valid_rows' => $csv_data['valid_rows'],
                'file_info' => [
                    'name' => $file['name'],
                    'size' => $file['size'],
                    'type' => $file['type'],
                    'path' => $file_path
                ],
                'parsing_info' => [
                    'total_fields' => count($csv_data['headers']),
                    'encoding' => $csv_data['encoding'] ?? 'UTF-8',
                    'delimiter' => $csv_data['delimiter'] ?? ',',
                    'has_errors' => !empty($csv_data['errors']),
                    'errors_count' => count($csv_data['errors'] ?? [])
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);

    } else {
        // رفع كامل للملف

        // حفظ إحصائيات الملف في سجل مؤقت
        $upload_log = [
            'filename' => $file['name'],
            'path' => $file_path,
            'size' => $file['size'],
            'total_rows' => $csv_data['total_rows'],
            'valid_rows' => $csv_data['valid_rows'],
            'headers' => $csv_data['headers'],
            'uploaded_at' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['migration_user']['id']
        ];

        // حفظ معلومات الرفع في ملف JSON
        $log_file = $upload_dir . '/upload_log_' . basename($file_path, '.csv') . '.json';
        file_put_contents($log_file, json_encode($upload_log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        echo json_encode([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح!',
            'file_info' => [
                'name' => $file['name'],
                'size' => $file['size'],
                'path' => $file_path,
                'processed_rows' => $csv_data['valid_rows'],
                'total_rows' => $csv_data['total_rows'],
                'headers' => $csv_data['headers'],
                'sample_data' => array_slice($csv_data['data'], 0, 3)
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    // تسجيل الخطأ
    error_log("Enhanced Upload Error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'tmp_dir' => sys_get_temp_dir(),
            'uploads_dir_exists' => is_dir(__DIR__ . '/../uploads/'),
            'uploads_dir_writable' => is_writable(__DIR__ . '/../uploads/'),
            'current_memory_usage' => formatFileSize(memory_get_usage()),
            'peak_memory_usage' => formatFileSize(memory_get_peak_usage()),
            'file_info' => isset($_FILES['csv_file']) ? [
                'name' => $_FILES['csv_file']['name'] ?? 'unknown',
                'size' => $_FILES['csv_file']['size'] ?? 0,
                'type' => $_FILES['csv_file']['type'] ?? 'unknown',
                'error' => $_FILES['csv_file']['error'] ?? 'unknown'
            ] : 'No file uploaded'
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * تحليل ملف CSV معقد مع معالجة الأخطاء
 */
function parseComplexCsv($file_path) {
    if (!file_exists($file_path)) {
        throw new Exception("الملف غير موجود: $file_path");
    }

    $data = [];
    $headers = [];
    $errors = [];
    $valid_rows = 0;
    $total_rows = 0;
    $encoding = 'UTF-8';
    $delimiter = ',';

    // محاولة تحديد الترميز
    $file_content = file_get_contents($file_path);
    if (!mb_check_encoding($file_content, 'UTF-8')) {
        $encoding = 'Windows-1256'; // ترميز عربي شائع
        $file_content = mb_convert_encoding($file_content, 'UTF-8', $encoding);
        file_put_contents($file_path, $file_content);
    }

    // تحديد نوع المحدد
    $delimiters = [',', ';', '\t', '|'];
    $delimiter_counts = [];

    $first_line = strtok($file_content, "\n");
    foreach ($delimiters as $del) {
        $delimiter_counts[$del] = substr_count($first_line, $del);
    }
    $delimiter = array_keys($delimiter_counts, max($delimiter_counts))[0];

    if (($handle = fopen($file_path, "r")) !== false) {
        $row_number = 0;

        // قراءة العناوين
        if (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $headers = array_map('trim', $row);
            $headers = array_map(function($header) {
                // تنظيف العناوين من علامات الاقتباس والمسافات
                return trim($header, '"\'');
            }, $headers);
            $row_number++;
        }

        if (empty($headers)) {
            fclose($handle);
            throw new Exception('لم يتم العثور على عناوين صالحة في الملف');
        }

        // قراءة البيانات
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $row_number++;
            $total_rows++;

            // تنظيف البيانات
            $cleaned_row = array_map(function($cell) {
                if ($cell === 'NULL' || $cell === 'null') {
                    return null;
                }
                return trim($cell, '"\'');
            }, $row);

            // التأكد من تطابق عدد الحقول
            if (count($cleaned_row) === count($headers)) {
                $record = array_combine($headers, $cleaned_row);

                // تنظيف إضافي للبيانات
                foreach ($record as $key => $value) {
                    if ($value === '' || $value === 'NULL') {
                        $record[$key] = null;
                    }
                }

                $data[] = $record;
                $valid_rows++;
            } else {
                $errors[] = [
                    'row' => $row_number,
                    'message' => 'عدد الحقول لا يتطابق مع العناوين',
                    'expected' => count($headers),
                    'actual' => count($cleaned_row)
                ];
            }

            // تحديد عدد الصفوف للمعاينة (لتجنب استهلاك الذاكرة)
            if ($action === 'preview' && $valid_rows >= 20) {
                break;
            }
        }

        fclose($handle);
    }

    return [
        'headers' => $headers,
        'data' => $data,
        'total_rows' => $total_rows,
        'valid_rows' => $valid_rows,
        'errors' => $errors,
        'encoding' => $encoding,
        'delimiter' => $delimiter
    ];
}

/**
 * تنسيق حجم الملف
 */
function formatFileSize($size) {
    if ($size == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($size, 1024));
    return round($size / pow(1024, $i), 2) . ' ' . $units[$i];
}
?>
