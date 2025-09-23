<?php
/**
 * نقل العاملات - تنفيذ مباشر
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? 'analyze';

    // الاتصال بالقواعد
    $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root');
    $newDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root');

    switch ($action) {
        case 'analyze':
            // فحص جدول cvs في القديم
            $cvsExists = $oldDb->query("SHOW TABLES LIKE 'cvs'")->rowCount() > 0;

            if ($cvsExists) {
                $cvsCount = $oldDb->query("SELECT COUNT(*) FROM cvs")->fetchColumn();
                $cvsColumns = $oldDb->query("DESCRIBE cvs")->fetchAll(PDO::FETCH_COLUMN);

                // فحص cv_status
                $cvStatusStats = $oldDb->query("
                    SELECT
                        cv_status,
                        COUNT(*) as count,
                        CASE
                            WHEN cv_status = 0 THEN 'غير محجوز'
                            WHEN cv_status = 1 THEN 'محجوز'
                            WHEN cv_status = 2 THEN 'باك آوت'
                            WHEN cv_status = 3 THEN 'معلق'
                            WHEN cv_status = 4 THEN 'تم الترحيل'
                            WHEN cv_status = 5 THEN 'هروب'
                            ELSE 'غير معروف'
                        END as status_name
                    FROM cvs
                    GROUP BY cv_status
                    ORDER BY cv_status
                ")->fetchAll(PDO::FETCH_ASSOC);

                // فحص جدول maids في الجديد
                $maidsExists = $newDb->query("SHOW TABLES LIKE 'maids'")->rowCount() > 0;
                $maidsCount = $maidsExists ? $newDb->query("SELECT COUNT(*) FROM maids")->fetchColumn() : 0;
                $maidsColumns = $maidsExists ? $newDb->query("DESCRIBE maids")->fetchAll(PDO::FETCH_COLUMN) : [];

                echo json_encode([
                    'success' => true,
                    'old_cvs' => [
                        'exists' => true,
                        'count' => $cvsCount,
                        'columns' => $cvsColumns,
                        'cv_status_stats' => $cvStatusStats
                    ],
                    'new_maids' => [
                        'exists' => $maidsExists,
                        'count' => $maidsCount,
                        'columns' => $maidsColumns
                    ],
                    'ready_to_migrate' => $cvsExists && $maidsExists,
                    'message' => "تحليل مكتمل: cvs ($cvsCount) → maids ($maidsCount)"
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'جدول cvs غير موجود في القاعدة القديمة'
                ], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'prepare':
            // تحضير جدول maids أولاً
            $maidsExists = $newDb->query("SHOW TABLES LIKE 'maids'")->rowCount() > 0;

            if (!$maidsExists) {
                throw new Exception('جدول maids غير موجود في القاعدة الجديدة');
            }

            // إضافة عمود cv_status إذا لم يكن موجوداً
            $cvStatusExists = $newDb->query("SHOW COLUMNS FROM maids LIKE 'cv_status'")->rowCount() > 0;

            if (!$cvStatusExists) {
                $newDb->exec("ALTER TABLE maids ADD COLUMN cv_status TINYINT DEFAULT 0 COMMENT '0=غير محجوز 1=محجوز 2=باك آوت 3=معلق 4=تم الترحيل 5=هروب'");
            }

            // إضافة أعمدة أخرى مطلوبة
            $columnsToAdd = [
                'birth_date' => 'DATE NULL',
                'is_pinned' => 'BOOLEAN DEFAULT FALSE',
                'experience_years' => 'VARCHAR(255) DEFAULT ""',
                'experience_status' => 'ENUM("has_experience","no_experience") DEFAULT "no_experience"',
                'cv_preview' => 'VARCHAR(255) NULL',
                'office_id' => 'BIGINT UNSIGNED NULL',
                'image' => 'VARCHAR(255) DEFAULT ""',
                'height' => 'VARCHAR(255) NULL',
                'weight' => 'VARCHAR(255) NULL',
                'id_number' => 'VARCHAR(255) NULL',
                'id_expiry_date' => 'DATE NULL',
                'skills' => 'LONGTEXT NULL',
                'languages' => 'LONGTEXT NULL'
            ];

            $addedColumns = [];

            foreach ($columnsToAdd as $column => $definition) {
                $columnExists = $newDb->query("SHOW COLUMNS FROM maids LIKE '$column'")->rowCount() > 0;
                if (!$columnExists) {
                    try {
                        $newDb->exec("ALTER TABLE maids ADD COLUMN $column $definition");
                        $addedColumns[] = $column;
                    } catch (Exception $e) {
                        // تجاهل إذا فشلت الإضافة
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'cv_status_added' => !$cvStatusExists,
                'added_columns' => $addedColumns,
                'message' => 'تم تحضير جدول maids: cv_status ' . ($cvStatusExists ? 'موجود' : 'تم إضافته') . ', أعمدة مضافة: ' . count($addedColumns)
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'migrate':
            // تنفيذ النقل
            $cvTable = $_POST['cv_table'] ?? 'cvs';
            $preserveStatus = $_POST['preserve_cv_status'] ?? 'true';

            // فحص الجدول المصدر
            if (!$oldDb->query("SHOW TABLES LIKE '$cvTable'")->rowCount()) {
                throw new Exception("الجدول $cvTable غير موجود");
            }

            // تعطيل Foreign Key checks مؤقتاً لتجنب مشاكل العلاقات
            $newDb->exec("SET FOREIGN_KEY_CHECKS = 0");

            // إضافة سجلات افتراضية في nationalities و religions إذا لم تكن موجودة
            $newDb->exec("INSERT IGNORE INTO nationalities (id, phone_code, created_at) VALUES (1, '+966', NOW())");
            $newDb->exec("INSERT IGNORE INTO religions (id, created_at) VALUES (1, NOW())");

            // جلب البيانات
            $data = $oldDb->query("SELECT * FROM $cvTable")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($data)) {
                throw new Exception("الجدول $cvTable فارغ");
            }

            $processed = 0;
            $skipped = 0;
            $conversions = [];
            $debugInfo = [];

            foreach ($data as $row) {
                try {
                    // تحضير البيانات للنقل
                    $maidData = [];

                    // الأعمدة الأساسية مع معالجة القيم الفارغة
                    $maidData['id'] = $row['id'] ?? null;
                    $maidData['name'] = $row['name'] ?? 'اسم غير محدد';
                    $maidData['passport_num'] = $row['passport_id'] ?? $row['passport_num'] ?? 'غير محدد';
                    $maidData['price'] = floatval($row['service_price'] ?? $row['price'] ?? 0);
                    $maidData['salary'] = floatval($row['salary'] ?? 0);
                    $maidData['age'] = floatval($row['age_id'] ?? $row['age'] ?? 25);
                    $maidData['cv_file'] = $row['cv_file'] ?? 'default_cv.pdf'; // قيمة افتراضية لتجنب NULL
                    $maidData['created_at'] = $row['created_at'] ?? date('Y-m-d H:i:s');
                    $maidData['updated_at'] = $row['updated_at'] ?? date('Y-m-d H:i:s');

                    // إضافة القيم الافتراضية للأعمدة المطلوبة
                    $maidData['is_show'] = 1;
                    $maidData['available_at'] = date('Y-m-d');
                    $maidData['is_pinned'] = 0;
                    $maidData['birth_date'] = $row['birth_date'] ?? null;
                    $maidData['experience_years'] = $row['experience_years'] ?? '0'; // قيمة افتراضية
                    $maidData['experience_status'] = $row['experience_status'] ?? 'no_experience';
                    $maidData['cv_preview'] = $row['cv_preview'] ?? null;
                    $maidData['office_id'] = $row['office_id'] ?? null;
                    $maidData['image'] = $row['image'] ?? $row['images'] ?? '';
                    $maidData['height'] = $row['height'] ?? '160'; // قيمة افتراضية
                    $maidData['weight'] = $row['weight'] ?? '55'; // قيمة افتراضية
                    $maidData['id_number'] = $row['id_number'] ?? null;
                    $maidData['id_expiry_date'] = $row['id_expiry_date'] ?? null;
                    $maidData['skills'] = $row['skills'] ?? null;
                    $maidData['languages'] = $row['languages'] ?? null;

                    // التحويلات المهمة
                    $maidData['nationality_id'] = $row['national_id'] ?? $row['nationality_id'] ?? 1;
                    $maidData['religion_id'] = $row['religion_id'] ?? 1;

                    // معالجة cv_status بعناية
                    if (isset($row['cv_status'])) {
                        if ($preserveStatus === 'true') {
                            // الحفاظ على cv_status كما هو
                            $maidData['cv_status'] = $row['cv_status'];

                            // تحويل إلى available أيضاً
                            $maidData['available'] = ($row['cv_status'] == 0) ? 1 : 0;

                            // تحويل إلى status
                            $maidData['status'] = in_array($row['cv_status'], [0, 1]) ? 'available' : 'unavailable';
                        } else {
                            // تحويل cv_status إلى available فقط
                            $maidData['available'] = ($row['cv_status'] == 0) ? 1 : 0;
                            $maidData['status'] = ($row['cv_status'] == 0) ? 'available' : 'reserved';
                        }

                        $conversions[$row['cv_status']] = ($conversions[$row['cv_status']] ?? 0) + 1;
                    }

                    // تنظيف البيانات
                    foreach ($maidData as $key => $value) {
                        if ($value === 'NULL' || $value === null || $value === '') {
                            $maidData[$key] = null;
                        }
                    }

                    // فحص الأعمدة الموجودة في جدول maids
                    $maidsColumns = $newDb->query("DESCRIBE maids")->fetchAll(PDO::FETCH_COLUMN);

                    // فلترة البيانات للأعمدة الموجودة فقط
                    $filteredData = [];
                    foreach ($maidData as $col => $value) {
                        if (in_array($col, $maidsColumns)) {
                            $filteredData[$col] = $value;
                        }
                    }

                    if (empty($filteredData)) {
                        $skipped++;
                        continue;
                    }

                    // إدراج في جدول maids
                    $columns = array_keys($filteredData);
                    $placeholders = array_fill(0, count($columns), '?');

                    $sql = "INSERT INTO maids (`" . implode('`, `', $columns) . "`)
                            VALUES (" . implode(', ', $placeholders) . ")
                            ON DUPLICATE KEY UPDATE ";

                    $updateParts = [];
                    foreach ($columns as $col) {
                        if (!in_array($col, ['id', 'created_at'])) {
                            $updateParts[] = "`$col` = VALUES(`$col`)";
                        }
                    }
                    $sql .= implode(', ', $updateParts);

                    $stmt = $newDb->prepare($sql);
                    $stmt->execute(array_values($filteredData));
                    $processed++;

                } catch (Exception $e) {
                    $skipped++;
                    $error = "خطأ في نقل CV {$row['id']}: " . $e->getMessage();
                    error_log($error);

                    // تسجيل تفاصيل إضافية للتشخيص
                    if ($skipped <= 3) { // أول 3 أخطاء فقط
                        $debugInfo[] = [
                            'id' => $row['id'],
                            'error' => $e->getMessage(),
                            'attempted_columns' => array_keys($filteredData ?? []),
                            'available_columns' => $maidsColumns ?? []
                        ];
                    }
                }
            }

            // إعادة تفعيل Foreign Key checks
            $newDb->exec("SET FOREIGN_KEY_CHECKS = 1");

            echo json_encode([
                'success' => true,
                'total_records' => count($data),
                'processed' => $processed,
                'skipped' => $skipped,
                'cv_status_conversions' => $conversions,
                'debug_info' => $debugInfo,
                'message' => "تم نقل $processed من " . count($data) . " سجل CV بنجاح"
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'check_result':
            // فحص النتيجة النهائية
            $maidsCount = $newDb->query("SELECT COUNT(*) FROM maids")->fetchColumn();

            // إحصائيات cv_status
            $statusStats = [];
            try {
                $statusQuery = $newDb->query("
                    SELECT
                        cv_status,
                        COUNT(*) as count,
                        available,
                        status
                    FROM maids
                    WHERE cv_status IS NOT NULL
                    GROUP BY cv_status, available, status
                    ORDER BY cv_status
                ");
                $statusStats = $statusQuery->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $statusStats = ['error' => $e->getMessage()];
            }

            echo json_encode([
                'success' => true,
                'maids_count' => $maidsCount,
                'status_stats' => $statusStats,
                'message' => "إجمالي العاملات في الجدول الجديد: $maidsCount"
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'إجراء غير صالح'
            ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
