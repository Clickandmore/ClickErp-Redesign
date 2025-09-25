<?php
/**
 * إصلاح مشكلة الجداول المرجعية للعاملات
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? 'analyze';

    // الاتصال بالقواعد
    $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root');
    $newDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root');

    switch ($action) {
        case 'analyze':
            // تحليل المشكلة
            $analysis = [];

            // فحص nationalities
            $oldNats = $oldDb->query("SELECT MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as count FROM nationalities")->fetch();
            $newNats = $newDb->query("SELECT MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as count FROM nationalities")->fetch();

            // فحص religions (إذا كان موجوداً)
            $oldRels = ['min_id' => 1, 'max_id' => 5, 'count' => 5]; // افتراضي
            $newRels = $newDb->query("SELECT MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as count FROM religions")->fetch();

            // محاولة فحص القاعدة القديمة
            try {
                $religionsExists = $oldDb->query("SHOW TABLES LIKE 'religions'")->rowCount() > 0;
                if ($religionsExists) {
                    $oldRels = $oldDb->query("SELECT MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as count FROM religions")->fetch();
                }
            } catch (Exception $e) {
                // الجدول غير موجود، نستخدم الافتراضي
            }

            // فحص العاملات وعلاقاتها
            $maidsAnalysis = [];
            $maidsExists = $oldDb->query("SHOW TABLES LIKE 'cvs'")->rowCount() > 0;

            if ($maidsExists) {
                $maidsCount = $oldDb->query("SELECT COUNT(*) FROM cvs")->fetchColumn();

                // فحص الـ IDs المستخدمة في العاملات
                $usedNatIds = $oldDb->query("SELECT DISTINCT national_id FROM cvs WHERE national_id IS NOT NULL ORDER BY national_id")->fetchAll(PDO::FETCH_COLUMN);

                // فحص الأعمدة الموجودة فعلاً
                $cvsColumns = $oldDb->query("DESCRIBE cvs")->fetchAll(PDO::FETCH_COLUMN);

                $maidsAnalysis = [
                    'total_cvs' => $maidsCount,
                    'used_nationality_ids' => $usedNatIds,
                    'max_nationality_id' => max($usedNatIds ?: [0]),
                    'available_columns' => $cvsColumns,
                    'has_religion_id' => in_array('religion_id', $cvsColumns)
                ];
            }

            $analysis = [
                'nationalities' => [
                    'old' => $oldNats,
                    'new' => $newNats,
                    'issue' => $oldNats['max_id'] > $newNats['max_id'],
                    'missing_range' => $newNats['max_id'] . ' إلى ' . $oldNats['max_id']
                ],
                'religions' => [
                    'old' => $oldRels,
                    'new' => $newRels,
                    'issue' => $oldRels['max_id'] > $newRels['max_id'],
                    'missing_range' => $newRels['max_id'] . ' إلى ' . $oldRels['max_id']
                ],
                'maids' => $maidsAnalysis
            ];

            echo json_encode([
                'success' => true,
                'analysis' => $analysis,
                'solution_needed' => $oldNats['max_id'] > $newNats['max_id'] || $oldRels['max_id'] > $newRels['max_id'],
                'message' => 'تحليل مكتمل - يوجد مشكلة في مطابقة الـ IDs'
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'fix_reference_tables':
            // إصلاح الجداول المرجعية
            $results = [];

            // تعطيل Foreign Key checks مؤقتاً
            $newDb->exec("SET FOREIGN_KEY_CHECKS = 0");

            // مسح الجداول المرجعية في القاعدة الجديدة وإعادة إنشائها
            $newDb->exec("DELETE FROM nationalities");
            $newDb->exec("DELETE FROM religions");

            // نقل nationalities مع الحفاظ على الـ IDs
            $nationalities = $oldDb->query("SELECT * FROM nationalities")->fetchAll(PDO::FETCH_ASSOC);
            $natsProcessed = 0;

            foreach ($nationalities as $nat) {
                try {
                    $columns = array_keys($nat);
                    $placeholders = array_fill(0, count($columns), '?');

                    $sql = "INSERT INTO nationalities (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $newDb->prepare($sql);
                    $stmt->execute(array_values($nat));
                    $natsProcessed++;
                } catch (Exception $e) {
                    error_log("خطأ في نقل nationality {$nat['id']}: " . $e->getMessage());
                }
            }

            // نقل religions إذا كان موجوداً، وإلا إنشاء سجلات افتراضية
            $relsProcessed = 0;

            try {
                $religionsExists = $oldDb->query("SHOW TABLES LIKE 'religions'")->rowCount() > 0;
                if ($religionsExists) {
                    $religions = $oldDb->query("SELECT * FROM religions")->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($religions as $rel) {
                        try {
                            $columns = array_keys($rel);
                            $placeholders = array_fill(0, count($columns), '?');

                            $sql = "INSERT INTO religions (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                            $stmt = $newDb->prepare($sql);
                            $stmt->execute(array_values($rel));
                            $relsProcessed++;
                        } catch (Exception $e) {
                            error_log("خطأ في نقل religion {$rel['id']}: " . $e->getMessage());
                        }
                    }
                } else {
                    // إنشاء سجلات افتراضية للأديان
                    $defaultReligions = [
                        ['id' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                        ['id' => 2, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                        ['id' => 3, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                        ['id' => 4, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                        ['id' => 5, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]
                    ];

                    foreach ($defaultReligions as $rel) {
                        try {
                            $sql = "INSERT INTO religions (id, created_at, updated_at) VALUES (?, ?, ?)";
                            $stmt = $newDb->prepare($sql);
                            $stmt->execute([$rel['id'], $rel['created_at'], $rel['updated_at']]);
                            $relsProcessed++;
                        } catch (Exception $e) {
                            // تجاهل إذا كان موجوداً
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("مشكلة في religions: " . $e->getMessage());
            }

            // إعادة تفعيل Foreign Key checks
            $newDb->exec("SET FOREIGN_KEY_CHECKS = 1");

            $results = [
                'nationalities_processed' => $natsProcessed,
                'religions_processed' => $relsProcessed
            ];

            echo json_encode([
                'success' => true,
                'results' => $results,
                'message' => "تم إصلاح الجداول المرجعية - nationalities: $natsProcessed, religions: $relsProcessed"
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'migrate_complete_maids':
            // نقل العاملات كاملة بعد إصلاح المراجع
            $cvTable = $_POST['cv_table'] ?? 'cvs';

            // التحقق من وجود الجدول
            if (!$oldDb->query("SHOW TABLES LIKE '$cvTable'")->rowCount()) {
                throw new Exception("الجدول $cvTable غير موجود");
            }

            // تعطيل Foreign Key checks
            $newDb->exec("SET FOREIGN_KEY_CHECKS = 0");

            // مسح البيانات الموجودة لإعادة النقل
            $newDb->exec("DELETE FROM maids");

            // جلب جميع البيانات
            $data = $oldDb->query("SELECT * FROM $cvTable")->fetchAll(PDO::FETCH_ASSOC);

            if (empty($data)) {
                throw new Exception("الجدول $cvTable فارغ");
            }

            $processed = 0;
            $skipped = 0;
            $errors = [];

            foreach ($data as $row) {
                try {
                    // تحضير البيانات مع جميع الحقول المتاحة
                    $maidData = [];

                    // الحقول الأساسية مع معالجة شاملة (حسب الأعمدة الموجودة فعلاً)
                    $maidData['id'] = $row['id'];
                    $maidData['name'] = $row['cvs_name'] ?? 'عاملة ' . $row['id']; // cvs_name هو الحقل الصحيح
                    $maidData['nationality_id'] = $row['national_id'] ?? 1;
                    $maidData['passport_num'] = $row['passport_id'] ?? 'CV_' . $row['id'];
                    $maidData['price'] = floatval($row['service_price'] ?? 0);
                    $maidData['salary'] = floatval($row['salary'] ?? 0);
                    $maidData['religion_id'] = 1; // افتراضي لأنه غير موجود في الجدول القديم
                    $maidData['age'] = floatval($row['age_id'] ?? 25);
                    $maidData['cv_file'] = $row['cv_file'] ?? 'cv_' . $row['id'] . '.pdf';
                    $maidData['birth_date'] = $row['birth_date'] ?? null;

                    // معالجة cv_status بعناية
                    $maidData['cv_status'] = $row['cv_status'] ?? 0;
                    $maidData['available'] = ($row['cv_status'] == 0) ? 1 : 0;
                    $maidData['status'] = in_array($row['cv_status'] ?? 0, [0, 1]) ? 'available' : 'unavailable';

                    // باقي الحقول
                    $maidData['is_show'] = 1;
                    $maidData['is_pinned'] = 0;
                    $maidData['available_at'] = date('Y-m-d');
                    $maidData['birth_date'] = $row['birth_date'] ?? null;
                    $maidData['experience_years'] = $row['experience_years'] ?? '0';
                    $maidData['experience_status'] = 'no_experience';
                    $maidData['cv_preview'] = $row['cv_preview'] ?? null;
                    $maidData['office_id'] = $row['office_id'] ?? null;
                    $maidData['image'] = $row['images'] ?? $row['image'] ?? '';
                    $maidData['height'] = $row['height'] ?? '160';
                    $maidData['weight'] = $row['weight'] ?? '55';
                    $maidData['skills'] = $row['skills'] ?? null;
                    $maidData['languages'] = $row['languages'] ?? null;
                    $maidData['created_at'] = $row['created_at'] ?? date('Y-m-d H:i:s');
                    $maidData['updated_at'] = $row['updated_at'] ?? date('Y-m-d H:i:s');

                    // تنظيف القيم NULL
                    foreach ($maidData as $key => $value) {
                        if ($value === 'NULL' || $value === '') {
                            $maidData[$key] = null;
                        }
                    }

                    // إدراج في الجدول
                    $columns = array_keys($maidData);
                    $placeholders = array_fill(0, count($columns), '?');

                    $sql = "INSERT INTO maids (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $newDb->prepare($sql);
                    $stmt->execute(array_values($maidData));
                    $processed++;

                } catch (Exception $e) {
                    $skipped++;
                    $errors[] = "CV {$row['id']}: " . $e->getMessage();

                    if (count($errors) <= 5) { // أول 5 أخطاء فقط
                        error_log("خطأ في نقل CV {$row['id']}: " . $e->getMessage());
                    }
                }
            }

            // إعادة تفعيل Foreign Key checks
            $newDb->exec("SET FOREIGN_KEY_CHECKS = 1");

            // إحصائيات cv_status
            $cvStatusStats = $newDb->query("
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
                FROM maids
                WHERE cv_status IS NOT NULL
                GROUP BY cv_status
                ORDER BY cv_status
            ")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'total_records' => count($data),
                'processed' => $processed,
                'skipped' => $skipped,
                'errors_count' => count($errors),
                'first_errors' => array_slice($errors, 0, 3),
                'cv_status_stats' => $cvStatusStats,
                'message' => "تم نقل $processed من " . count($data) . " عاملة بنجاح (تخطي: $skipped)"
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'check_result':
            // فحص النتيجة النهائية
            $maidsCount = $newDb->query("SELECT COUNT(*) FROM maids")->fetchColumn();
            $natsCount = $newDb->query("SELECT COUNT(*) FROM nationalities")->fetchColumn();
            $relsCount = $newDb->query("SELECT COUNT(*) FROM religions")->fetchColumn();

            // إحصائيات cv_status
            $statusStats = [];
            try {
                $statusQuery = $newDb->query("
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
                    FROM maids
                    WHERE cv_status IS NOT NULL
                    GROUP BY cv_status
                    ORDER BY cv_status
                ");
                $statusStats = $statusQuery->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $statusStats = ['error' => $e->getMessage()];
            }

            echo json_encode([
                'success' => true,
                'maids_count' => $maidsCount,
                'nationalities_count' => $natsCount,
                'religions_count' => $relsCount,
                'status_stats' => $statusStats,
                'message' => "العاملات: $maidsCount | الجنسيات: $natsCount | الأديان: $relsCount"
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
