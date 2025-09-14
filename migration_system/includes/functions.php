<?php
/**
 * وظائف مساعدة لنظام النقل
 * Helper Functions for Migration System
 */

/**
 * الحصول على إحصائيات النقل العامة
 */
function getMigrationStatistics() {
    try {
        $db = Database::getInstance();

        $stats = [
            'total_batches' => 0,
            'total_processed' => 0,
            'total_successful' => 0,
            'total_failed' => 0,
            'success_rate' => 0,
            'active_batches' => 0
        ];

        // إجمالي الدفعات والسجلات
        $result = $db->fetch("
            SELECT
                COUNT(*) as total_batches,
                COALESCE(SUM(processed_records), 0) as total_processed,
                COALESCE(SUM(successful_records), 0) as total_successful,
                COALESCE(SUM(failed_records), 0) as total_failed,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as active_batches
            FROM migration_batches
            WHERE batch_name != 'DATABASE_SETUP'
        ", [], 'migration');

        if ($result) {
            $stats = array_merge($stats, $result);
            $stats['success_rate'] = $stats['total_processed'] > 0
                ? round(($stats['total_successful'] / $stats['total_processed']) * 100, 2)
                : 0;
        }

    } catch (Exception $e) {
        error_log("Error getting migration statistics: " . $e->getMessage());
        // إرجاع إحصائيات افتراضية في حالة الخطأ
    }

    return $stats;
}

/**
 * الحصول على آخر الدفعات
 */
function getRecentBatches($limit = 10) {
    try {
        $db = Database::getInstance();

        return $db->fetchAll("
            SELECT
                id, batch_name, source_table, target_table, status,
                total_records, successful_records, failed_records,
                created_at, updated_at,
                TIMESTAMPDIFF(MINUTE, started_at, COALESCE(completed_at, NOW())) as duration_minutes
            FROM migration_batches
            WHERE batch_name != 'DATABASE_SETUP'
            ORDER BY created_at DESC
            LIMIT ?
        ", [$limit], 'migration');

    } catch (Exception $e) {
        error_log("Error getting recent batches: " . $e->getMessage());
        return [];
    }
}

/**
 * إنشاء دفعة نقل جديدة
 */
function createMigrationBatch($batch_name, $source_table, $target_table, $file_name = null, $user_id = null) {
    try {
        $db = Database::getInstance();

        $data = [
            'batch_name' => $batch_name ?? 'دفعة بدون اسم',
            'source_table' => $source_table ?? 'unknown',
            'target_table' => $target_table ?? 'unknown',
            'file_name' => $file_name,
            'created_by' => $user_id ?? $_SESSION['migration_user']['id'] ?? 1,
            'status' => 'pending'
        ];

        return $db->insert('migration_batches', $data, 'migration');

    } catch (Exception $e) {
        error_log("Error creating migration batch: " . $e->getMessage());
        throw new Exception("فشل في إنشاء دفعة النقل: " . $e->getMessage());
    }
}

/**
 * تحديث حالة الدفعة
 */
function updateBatchStatus($batch_id, $status, $updates = []) {
    global $db;

    try {
        $data = array_merge([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ], $updates);

        // تحديث تواريخ خاصة حسب الحالة
        if ($status === 'processing' && !isset($updates['started_at'])) {
            $data['started_at'] = date('Y-m-d H:i:s');
        } elseif (in_array($status, ['completed', 'failed']) && !isset($updates['completed_at'])) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        return $db->update('migration_batches', $data, "id = {$batch_id}");

    } catch (Exception $e) {
        error_log("Error updating batch status: " . $e->getMessage());
        throw new Exception("فشل في تحديث حالة الدفعة");
    }
}

/**
 * تسجيل خطأ في دفعة النقل
 */
function logMigrationError($batch_id, $row_number, $error_type, $error_message, $raw_data = null) {
    global $db;

    try {
        $data = [
            'batch_id' => $batch_id,
            'row_number' => $row_number,
            'error_type' => $error_type,
            'error_message' => $error_message,
            'raw_data' => $raw_data ? json_encode($raw_data) : null
        ];

        return $db->insert('migration_errors', $data);

    } catch (Exception $e) {
        error_log("Error logging migration error: " . $e->getMessage());
    }
}

/**
 * قراءة ملف CSV
 */
function readCsvFile($file_path, $delimiter = ',', $limit = null) {
    if (!file_exists($file_path)) {
        throw new Exception("الملف غير موجود: {$file_path}");
    }

    $data = [];
    $headers = [];
    $row_count = 0;

    if (($handle = fopen($file_path, "r")) !== false) {
        // قراءة العناوين
        if (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $headers = array_map('trim', $row);
        }

        // قراءة البيانات
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false && ($limit === null || $row_count < $limit)) {
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, array_map('trim', $row));
                $row_count++;
            }
        }

        fclose($handle);
    }

    return [
        'headers' => $headers,
        'data' => $data,
        'total_rows' => $row_count
    ];
}

/**
 * التحقق من صحة البيانات
 */
function validateImportData($data, $table_name) {
    global $db;

    $errors = [];
    $valid_records = [];

    // الحصول على قواعد التحقق للجدول
    $validation_rules = $db->fetchAll("
        SELECT field_name, rule_type, rule_value, error_message, priority
        FROM validation_rules
        WHERE table_name = ? AND is_active = 1
        ORDER BY priority ASC
    ", [$table_name]);

    foreach ($data as $index => $record) {
        $record_errors = [];

        foreach ($validation_rules as $rule) {
            $field = $rule['field_name'];
            $value = $record[$field] ?? '';

            switch ($rule['rule_type']) {
                case 'required':
                    if (empty($value)) {
                        $record_errors[] = $rule['error_message'];
                    }
                    break;

                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $record_errors[] = $rule['error_message'];
                    }
                    break;

                case 'numeric':
                    if (!empty($value) && !is_numeric($value)) {
                        $record_errors[] = $rule['error_message'];
                    }
                    break;

                case 'regex':
                    if (!empty($value) && !preg_match('/' . $rule['rule_value'] . '/', $value)) {
                        $record_errors[] = $rule['error_message'];
                    }
                    break;

                case 'unique':
                    // فحص التكرار في قاعدة البيانات المستهدفة
                    if (!empty($value)) {
                        list($table, $column) = explode('.', $rule['rule_value']);
                        $existing = $db->fetch("SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?", [$value], 'platinum');
                        if ($existing && $existing['count'] > 0) {
                            $record_errors[] = $rule['error_message'];
                        }
                    }
                    break;
            }
        }

        if (empty($record_errors)) {
            $valid_records[] = $record;
        } else {
            $errors[] = [
                'row' => $index + 1,
                'errors' => $record_errors,
                'data' => $record
            ];
        }
    }

    return [
        'valid_records' => $valid_records,
        'errors' => $errors,
        'total_valid' => count($valid_records),
        'total_errors' => count($errors)
    ];
}

/**
 * رفع الملفات مع التحقق
 */
function handleFileUpload($input_name, $allowed_types = ['csv', 'xlsx'], $max_size = 10485760) {
    if (!isset($_FILES[$input_name]) || $_FILES[$input_name]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('لم يتم رفع أي ملف أو حدث خطأ في الرفع');
    }

    $file = $_FILES[$input_name];

    // فحص حجم الملف
    if ($file['size'] > $max_size) {
        throw new Exception('حجم الملف كبير جداً. الحد الأقصى: ' . formatBytes($max_size));
    }

    // فحص نوع الملف
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception('نوع الملف غير مدعوم. الأنواع المدعومة: ' . implode(', ', $allowed_types));
    }

    // إنشاء مجلد التحميل إذا لم يكن موجوداً
    $upload_dir = __DIR__ . '/../uploads/' . date('Y/m/d');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // إنشاء اسم ملف فريد
    $filename = uniqid() . '_' . sanitizeFilename($file['name']);
    $file_path = $upload_dir . '/' . $filename;

    // نقل الملف
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('فشل في حفظ الملف');
    }

    return [
        'original_name' => $file['name'],
        'filename' => $filename,
        'file_path' => $file_path,
        'size' => $file['size'],
        'extension' => $file_extension
    ];
}

/**
 * تنظيف اسم الملف
 */
function sanitizeFilename($filename) {
    if ($filename === null) {
        $filename = 'file_' . uniqid();
    }

    // إزالة الأحرف غير المسموحة (بدون استخدام unicode ranges المعقدة)
    $filename = preg_replace('/[^\w\.\-_]/', '_', $filename);
    return trim($filename ?? '', '._-');
}

/**
 * تنسيق حجم الملف
 */
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}

/**
 * الحصول على تطابق المعرفات
 */
function getIdMapping($source_table, $source_id, $target_table = null) {
    global $db;

    $sql = "SELECT target_id, target_table FROM id_mapping WHERE source_table = ? AND source_id = ?";
    $params = [$source_table, $source_id];

    if ($target_table) {
        $sql .= " AND target_table = ?";
        $params[] = $target_table;
    }

    return $db->fetch($sql, $params);
}

/**
 * حفظ تطابق المعرفات
 */
function saveIdMapping($source_table, $source_id, $target_table, $target_id, $additional_data = null) {
    global $db;

    $data = [
        'source_table' => $source_table,
        'source_id' => $source_id,
        'target_table' => $target_table,
        'target_id' => $target_id,
        'additional_data' => $additional_data ? json_encode($additional_data) : null
    ];

    // التحقق من وجود التطابق مسبقاً
    $existing = getIdMapping($source_table, $source_id, $target_table);
    if ($existing) {
        return $db->update('id_mapping', $data, "source_table = '{$source_table}' AND source_id = {$source_id} AND target_table = '{$target_table}'");
    } else {
        return $db->insert('id_mapping', $data);
    }
}

/**
 * تنسيق التاريخ للعرض
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * الحصول على لون حالة الدفعة
 */
function getBatchStatusClass($status) {
    $classes = [
        'pending' => 'status-pending',
        'processing' => 'status-processing',
        'completed' => 'status-completed',
        'failed' => 'status-failed',
        'paused' => 'status-pending'
    ];

    return $classes[$status] ?? 'status-pending';
}

/**
 * تنسيق أسماء الحالات للعرض
 */
function formatBatchStatus($status) {
    $statuses = [
        'pending' => 'في الانتظار',
        'processing' => 'قيد المعالجة',
        'completed' => 'مكتمل',
        'failed' => 'فاشل',
        'paused' => 'متوقف'
    ];

    return $statuses[$status] ?? $status;
}

/**
 * إرسال استجابة JSON
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * تسجيل النشاط
 */
function logActivity($action, $details = '', $user_id = null) {
    $log_file = __DIR__ . '/../logs/activity.log';
    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $user_id = $user_id ?? $_SESSION['migration_user']['id'] ?? 0;
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $log_entry = "[{$timestamp}] User:{$user_id} IP:{$ip} Action:{$action} Details:{$details}" . PHP_EOL;

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
?>
