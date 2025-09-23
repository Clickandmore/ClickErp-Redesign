<?php
/**
 * معالج دفعات النقل
 * Batch Processing Engine
 */

if (php_sapi_name() === 'cli') {
    // تشغيل من command line
    $batch_id = $argv[1] ?? null;
    $options = json_decode($argv[2] ?? '{}', true);

    if ($batch_id) {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../includes/functions.php';
        processBatch($batch_id, $options);
    }
} else {
    // تشغيل من web request
    require_once '../config/database.php';
    require_once '../includes/functions.php';
}

/**
 * معالجة دفعة نقل
 */
function processBatch($batch_id, $options = []) {
    global $db;

    try {
        // الحصول على معلومات الدفعة
        $batch = $db->fetch("
            SELECT * FROM migration_batches WHERE id = ?
        ", [$batch_id]);

        if (!$batch) {
            throw new Exception("الدفعة غير موجودة: {$batch_id}");
        }

        // تحديث الحالة إلى "قيد المعالجة"
        updateBatchStatus($batch_id, 'processing');

        $source_table = $batch['source_table'];
        $target_table = $batch['target_table'];

        // إنشاء نسخة احتياطية إذا طُلب ذلك
        if ($options['backup'] ?? false) {
            createBackup($target_table);
        }

        // الحصول على البيانات المؤقتة
        $temp_data = $db->fetchAll("
            SELECT * FROM temp_import_data
            WHERE batch_id = ? AND is_processed = 0
            ORDER BY row_number
        ", [$batch_id]);

        if (empty($temp_data)) {
            throw new Exception("لا توجد بيانات للمعالجة");
        }

        $successful_count = 0;
        $failed_count = 0;
        $batch_size = $options['batch_size'] ?? 500;

        // معالجة البيانات في دفعات
        $chunks = array_chunk($temp_data, $batch_size);

        foreach ($chunks as $chunk) {
            $results = processBatchChunk($batch_id, $chunk, $source_table, $target_table, $options);
            $successful_count += $results['successful'];
            $failed_count += $results['failed'];

            // تحديث التقدم
            updateBatchStatus($batch_id, 'processing', [
                'processed_records' => $successful_count + $failed_count,
                'successful_records' => $successful_count,
                'failed_records' => $failed_count
            ]);

            // توقف قصير لتجنب الحمل الزائد
            usleep(100000); // 0.1 second
        }

        // تحديد الحالة النهائية
        $final_status = $failed_count > 0 ? 'completed' : 'completed';

        // التراجع التلقائي في حالة الفشل الكامل
        if ($options['auto_rollback'] && $successful_count == 0 && $failed_count > 0) {
            rollbackBatch($batch_id, $target_table);
            $final_status = 'failed';
        }

        updateBatchStatus($batch_id, $final_status);

        // تنظيف البيانات المؤقتة
        cleanupTempData($batch_id);

        logActivity('batch_processed', "Batch {$batch_id} processed: {$successful_count} successful, {$failed_count} failed");

    } catch (Exception $e) {
        // تسجيل الخطأ وتحديث الحالة
        error_log("Batch processing error for batch {$batch_id}: " . $e->getMessage());

        logMigrationError($batch_id, 0, 'SYSTEM_ERROR', $e->getMessage());
        updateBatchStatus($batch_id, 'failed');

        // التراجع في حالة الخطأ
        if ($options['auto_rollback'] ?? false) {
            try {
                rollbackBatch($batch_id, $batch['target_table'] ?? '');
            } catch (Exception $rollback_error) {
                error_log("Rollback error for batch {$batch_id}: " . $rollback_error->getMessage());
            }
        }
    }
}

/**
 * معالجة جزء من البيانات
 */
function processBatchChunk($batch_id, $chunk, $source_table, $target_table, $options) {
    global $db;

    $successful = 0;
    $failed = 0;

    foreach ($chunk as $temp_record) {
        try {
            $raw_data = json_decode($temp_record['raw_data'], true);

            // التحقق من صحة البيانات إذا طُلب ذلك
            if ($options['validate'] ?? true) {
                $validation_result = validateSingleRecord($raw_data, $source_table);
                if (!$validation_result['valid']) {
                    foreach ($validation_result['errors'] as $error) {
                        logMigrationError($batch_id, $temp_record['row_number'], 'VALIDATION_ERROR', $error);
                    }
                    $failed++;
                    continue;
                }
            }

            // تحويل البيانات حسب نوع الجدول
            $converted_data = convertDataForTable($raw_data, $source_table, $target_table);

            // فحص التكرار إذا طُلب ذلك
            if ($options['skip_duplicates'] ?? false) {
                if (isDuplicateRecord($converted_data, $target_table)) {
                    logMigrationError($batch_id, $temp_record['row_number'], 'DUPLICATE_RECORD', 'السجل موجود مسبقاً');
                    $failed++;
                    continue;
                }
            }

            // إدراج البيانات
            $target_id = insertRecord($converted_data, $target_table);

            // حفظ تطابق المعرفات
            if (isset($raw_data['id'])) {
                saveIdMapping($source_table, $raw_data['id'], $target_table, $target_id);
            }

            // تحديث البيانات المؤقتة
            $db->update('temp_import_data', [
                'is_processed' => 1,
                'processed_data' => json_encode($converted_data, JSON_UNESCAPED_UNICODE)
            ], "id = {$temp_record['id']}", 'migration');

            $successful++;

        } catch (Exception $e) {
            logMigrationError($batch_id, $temp_record['row_number'], 'PROCESSING_ERROR', $e->getMessage(), $raw_data);
            $failed++;
        }
    }

    return ['successful' => $successful, 'failed' => $failed];
}

/**
 * تحويل البيانات حسب نوع الجدول
 */
function convertDataForTable($raw_data, $source_table, $target_table) {
    $converted = [];

    switch ($source_table) {
        case 'users':
            $converted = convertUsersData($raw_data);
            break;

        case 'maids':
            $converted = convertMaidsData($raw_data);
            break;

        case 'orders':
            $converted = convertOrdersData($raw_data);
            break;

        default:
            // نسخ مباشر للجداول غير المعرفة
            $converted = $raw_data;
    }

    // إضافة timestamps
    $converted['created_at'] = $converted['created_at'] ?? date('Y-m-d H:i:s');
    $converted['updated_at'] = $converted['updated_at'] ?? date('Y-m-d H:i:s');

    return $converted;
}

/**
 * تحويل بيانات المستخدمين
 */
function convertUsersData($raw_data) {
    $converted = [
        'name' => $raw_data['name'] ?? '',
        'email' => $raw_data['email'] ?? null,
        'phone' => cleanPhoneNumber($raw_data['phone'] ?? ''),
        'password' => isset($raw_data['password']) ? password_hash($raw_data['password'], PASSWORD_DEFAULT) : null,
        'address' => $raw_data['address'] ?? null,
        'country' => $raw_data['country'] ?? 'Saudi Arabia',
        'city' => $raw_data['city'] ?? null,
        'user_type' => 'customer',
        'phone_verified_at' => !empty($raw_data['phone']) ? date('Y-m-d H:i:s') : null
    ];

    return array_filter($converted, function($value) {
        return $value !== null && $value !== '';
    });
}

/**
 * تحويل بيانات العاملات إلى سير ذاتية
 */
function convertMaidsData($raw_data) {
    $converted = [
        'passport_id' => $raw_data['passport_num'] ?? $raw_data['passport_id'] ?? '',
        'salary' => floatval($raw_data['salary'] ?? 0),
        'service_price' => floatval($raw_data['price'] ?? $raw_data['service_price'] ?? 0),
        'national_id' => intval($raw_data['nationality_id'] ?? $raw_data['national_id'] ?? 1),
        'age_id' => convertAgeToCategory($raw_data['age'] ?? 25),
        'social_id' => intval($raw_data['social_status_id'] ?? $raw_data['social_id'] ?? 1),
        'region_id' => intval($raw_data['religion_id'] ?? $raw_data['region_id'] ?? 1),
        'personal_image' => $raw_data['personal_image'] ?? null,
        'cv_file' => $raw_data['cv_file'] ?? null,
        'cv_rev_type' => 1 // سيرة حجز
    ];

    return array_filter($converted, function($value) {
        return $value !== null && $value !== '';
    });
}

/**
 * تحويل بيانات الطلبات
 */
function convertOrdersData($raw_data) {
    $converted = [
        'order_code' => $raw_data['order_code'] ?? generateOrderCode(),
        'status' => convertOrderStatus($raw_data['status'] ?? 'new'),
        'user_id' => getMappedId('users', $raw_data['user_id'] ?? 0),
        'biography_id' => getMappedId('maids', $raw_data['maid_id'] ?? 0, 'cvs'),
        'order_date' => $raw_data['created_at'] ?? date('Y-m-d H:i:s')
    ];

    return array_filter($converted, function($value) {
        return $value !== null && $value !== '';
    });
}

/**
 * وظائف مساعدة للتحويل
 */
function cleanPhoneNumber($phone) {
    // إزالة الرموز والمسافات
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

function convertAgeToCategory($age) {
    $age = intval($age);
    if ($age <= 25) return 1;
    if ($age <= 35) return 2;
    if ($age <= 45) return 3;
    return 4;
}

function convertOrderStatus($status) {
    $status_map = [
        'new' => 'new',
        'pending' => 'new',
        'completed' => 'finished',
        'cancelled' => 'canceled',
        'processing' => 'under_work'
    ];

    return $status_map[strtolower($status)] ?? 'new';
}

function getMappedId($source_table, $source_id, $target_table = null) {
    if (!$source_id) return null;

    $mapping = getIdMapping($source_table, $source_id, $target_table);
    return $mapping ? $mapping['target_id'] : null;
}

function generateOrderCode() {
    return 'ORD_' . date('Ymd') . '_' . substr(uniqid(), -6);
}

/**
 * التحقق من تكرار السجل
 */
function isDuplicateRecord($data, $table) {
    global $db;

    $unique_fields = getUniqueFields($table);

    foreach ($unique_fields as $field) {
        if (isset($data[$field]) && !empty($data[$field])) {
            $existing = $db->fetch("
                SELECT COUNT(*) as count FROM {$table} WHERE {$field} = ?
            ", [$data[$field]], 'platinum');

            if ($existing && $existing['count'] > 0) {
                return true;
            }
        }
    }

    return false;
}

function getUniqueFields($table) {
    $unique_fields = [
        'users' => ['email', 'phone'],
        'cvs' => ['passport_id'],
        'orders' => ['order_code']
    ];

    return $unique_fields[$table] ?? [];
}

/**
 * إدراج سجل في قاعدة البيانات
 */
function insertRecord($data, $table) {
    global $db;
    return $db->insert($table, $data, 'platinum');
}

/**
 * التحقق من صحة سجل واحد
 */
function validateSingleRecord($data, $table) {
    $validation = validateImportData([$data], $table);

    return [
        'valid' => $validation['total_errors'] === 0,
        'errors' => $validation['total_errors'] > 0 ? $validation['errors'][0]['errors'] : []
    ];
}

/**
 * إنشاء نسخة احتياطية
 */
function createBackup($table) {
    global $db;

    try {
        $backup_table = $db->backupTable($table, 'platinum');
        logActivity('backup_created', "Created backup table: {$backup_table} for {$table}");
        return $backup_table;
    } catch (Exception $e) {
        error_log("Backup creation failed for {$table}: " . $e->getMessage());
        throw new Exception("فشل في إنشاء النسخة الاحتياطية للجدول {$table}");
    }
}

/**
 * التراجع عن دفعة النقل
 */
function rollbackBatch($batch_id, $target_table) {
    global $db;

    try {
        // الحصول على المعرفات المدرجة من هذه الدفعة
        $inserted_ids = $db->fetchAll("
            SELECT target_id FROM id_mapping im
            JOIN temp_import_data tid ON tid.batch_id = ?
            WHERE im.target_table = ? AND im.source_table = (
                SELECT source_table FROM migration_batches WHERE id = ?
            )
        ", [$batch_id, $target_table, $batch_id]);

        if (!empty($inserted_ids)) {
            $ids = array_column($inserted_ids, 'target_id');
            $ids_list = implode(',', $ids);

            // حذف السجلات المدرجة
            $db->query("DELETE FROM {$target_table} WHERE id IN ({$ids_list})", [], 'platinum');

            // حذف التطابقات
            $db->query("
                DELETE FROM id_mapping
                WHERE target_table = ? AND target_id IN ({$ids_list})
            ", [$target_table]);
        }

        logActivity('batch_rollback', "Rolled back batch {$batch_id} from {$target_table}");

    } catch (Exception $e) {
        error_log("Rollback failed for batch {$batch_id}: " . $e->getMessage());
        throw new Exception("فشل في التراجع عن الدفعة");
    }
}

/**
 * تنظيف البيانات المؤقتة
 */
function cleanupTempData($batch_id) {
    global $db;

    try {
        // حذف البيانات المؤقتة القديمة (أكثر من 7 أيام)
        $db->query("
            DELETE FROM temp_import_data
            WHERE batch_id = ? OR created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ", [$batch_id]);

        // حذف الملف المرفوع إذا كان موجوداً
        $batch = $db->fetch("SELECT file_path FROM migration_batches WHERE id = ?", [$batch_id]);
        if ($batch && !empty($batch['file_path']) && file_exists($batch['file_path'])) {
            unlink($batch['file_path']);
        }

    } catch (Exception $e) {
        error_log("Cleanup failed for batch {$batch_id}: " . $e->getMessage());
        // عدم رمي exception هنا لأنه ليس خطأ حرج
    }
}
?>
