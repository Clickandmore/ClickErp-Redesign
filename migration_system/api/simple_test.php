<?php
/**
 * اختبار بسيط لرفع الملفات بدون تعقيد
 */

// تفعيل عرض الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['migration_user'])) {
    $_SESSION['migration_user'] = ['id' => 1, 'name' => 'Test User'];
}

try {
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

    // إنشاء مجلد الحفظ
    $upload_dir = __DIR__ . '/../uploads/' . date('Y/m/d');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // إنشاء اسم ملف فريد
    $filename = date('YmdHis') . '_' . uniqid() . '.csv';
    $file_path = $upload_dir . '/' . $filename;

    // نقل الملف
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('فشل في حفظ الملف');
    }

    // قراءة وتحليل الملف
    $csv_data = [];
    $headers = [];
    $total_rows = 0;

    if (($handle = fopen($file_path, "r")) !== false) {
        // قراءة العناوين
        if (($row = fgetcsv($handle, 0, ',')) !== false) {
            $headers = array_map('trim', $row);
        }

        // قراءة البيانات (عينة)
        $sample_count = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false && $sample_count < 5) {
            if (count($row) === count($headers)) {
                $csv_data[] = array_combine($headers, array_map('trim', $row));
                $sample_count++;
            }
            $total_rows++;
        }

        // عد باقي الصفوف
        while (fgetcsv($handle, 0, ',') !== false) {
            $total_rows++;
        }

        fclose($handle);
    }

    // محاولة حفظ بيانات النقل (بدون قاعدة البيانات)
    $log_data = [
        'file_name' => $file['name'],
        'file_path' => $file_path,
        'file_size' => $file['size'],
        'total_rows' => $total_rows,
        'headers_count' => count($headers),
        'uploaded_at' => date('Y-m-d H:i:s'),
        'user_id' => $_SESSION['migration_user']['id']
    ];

    // حفظ في ملف JSON للمتابعة
    $log_file = $upload_dir . '/upload_log_' . date('YmdHis') . '.json';
    file_put_contents($log_file, json_encode($log_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    echo json_encode([
        'success' => true,
        'message' => 'تم رفع الملف بنجاح!',
        'file_info' => [
            'name' => $file['name'],
            'size' => $file['size'],
            'path' => $file_path,
            'total_rows' => $total_rows,
            'headers' => $headers,
            'sample_data' => $csv_data,
            'log_file' => $log_file
        ],
        'next_steps' => [
            'يمكنك الآن معالجة البيانات',
            'استخدم النقل الشامل لنقل جميع البيانات',
            'راجع الملف المحفوظ في: ' . $file_path
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'solution' => 'تم إصلاح المشاكل الأساسية، جرب مرة أخرى',
        'debug_info' => [
            'php_version' => PHP_VERSION,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'uploads_dir' => __DIR__ . '/../uploads/',
            'uploads_writable' => is_writable(__DIR__ . '/../uploads/') ? 'yes' : 'no'
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
