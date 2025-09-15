<?php
/**
 * رفع مستقل بدون قاعدة بيانات - لحل المشكلة فوراً
 * Standalone Upload without Database - Immediate Fix
 */

// إعدادات محسنة
ini_set('memory_limit', '512M');
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');

header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['migration_user'])) {
    $_SESSION['migration_user'] = ['id' => 1, 'name' => 'Migration User'];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('يُسمح بطلبات POST فقط');
    }

    $action = $_POST['action'] ?? 'upload';

    if (!isset($_FILES['csv_file'])) {
        throw new Exception('لم يتم رفع أي ملف');
    }

    $file = $_FILES['csv_file'];

    // فحص أخطاء الرفع
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من المسموح',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من المسموح',
            UPLOAD_ERR_PARTIAL => 'تم رفع الملف جزئياً فقط',
            UPLOAD_ERR_NO_FILE => 'لم يتم رفع أي ملف',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد tmp مفقود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف',
            UPLOAD_ERR_EXTENSION => 'امتداد PHP منع الرفع'
        ];
        throw new Exception($errors[$file['error']] ?? 'خطأ غير معروف');
    }

    // إنشاء مجلد الحفظ
    $upload_dir = __DIR__ . '/../uploads/' . date('Y/m/d');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // إنشاء اسم ملف فريد
    $filename = date('YmdHis') . '_' . uniqid() . '_' . preg_replace('/[^\w\.\-]/', '_', $file['name']);
    $file_path = $upload_dir . '/' . $filename;

    // نقل الملف
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('فشل في حفظ الملف');
    }

    // تحليل الملف
    $analysis = analyzeCSV($file_path);

    // حفظ معلومات النقل في ملف JSON (بدلاً من قاعدة البيانات)
    $log_data = [
        'batch_id' => uniqid('batch_'),
        'file_info' => [
            'original_name' => $file['name'],
            'saved_name' => $filename,
            'path' => $file_path,
            'size' => $file['size']
        ],
        'analysis' => $analysis,
        'uploaded_at' => date('Y-m-d H:i:s'),
        'uploaded_by' => $_SESSION['migration_user']['id'],
        'status' => 'uploaded'
    ];

    $log_file = $upload_dir . '/log_' . date('YmdHis') . '.json';
    file_put_contents($log_file, json_encode($log_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    if ($action === 'preview') {
        echo json_encode([
            'success' => true,
            'data' => [
                'headers' => $analysis['headers'],
                'preview' => array_slice($analysis['data'], 0, 10),
                'total_rows' => $analysis['total_rows'],
                'file_info' => [
                    'name' => $file['name'],
                    'size' => $file['size']
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح!',
            'batch_id' => $log_data['batch_id'],
            'file_info' => [
                'name' => $file['name'],
                'size' => $file['size'],
                'total_rows' => $analysis['total_rows'],
                'headers_count' => count($analysis['headers']),
                'path' => $file_path,
                'log_file' => $log_file
            ],
            'note' => 'تم حفظ الملف بنجاح - يمكنك الآن استخدام النقل الشامل للمعالجة'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'solution' => 'استخدم الرابط البديل للنقل المباشر',
        'alternative_link' => 'http://127.0.0.1:8002/manual_migration.php'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * تحليل ملف CSV
 */
function analyzeCSV($file_path) {
    $data = [];
    $headers = [];
    $total_rows = 0;

    if (($handle = fopen($file_path, "r")) !== false) {
        // قراءة العناوين
        if (($row = fgetcsv($handle, 0, ',')) !== false) {
            $headers = array_map(function($h) {
                return trim($h ?? '', '"\'');
            }, $row);
        }

        // قراءة البيانات
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $total_rows++;

            if (count($data) < 20) { // حفظ أول 20 صف للمعاينة
                if (count($row) === count($headers)) {
                    $cleaned_row = array_map(function($cell) {
                        return trim($cell ?? '', '"\'');
                    }, $row);
                    $data[] = array_combine($headers, $cleaned_row);
                }
            }
        }

        fclose($handle);
    }

    return [
        'headers' => $headers,
        'data' => $data,
        'total_rows' => $total_rows,
        'analyzed_at' => date('Y-m-d H:i:s')
    ];
}
?>
