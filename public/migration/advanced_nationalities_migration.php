<?php
/**
 * نقل متقدم لجدول nationalities
 * من platinum_old إلى platinum_new (nationalities + nationalities_translations)
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
header_remove();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // تنظيف أي output سابق
    if (ob_get_level() > 0) {
        ob_clean();
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? 'analyze';

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
        case 'analyze_structure':
            // تحليل بنية الجداول
            $analysis = [];

            // تحليل الجدول القديم
            try {
                $oldColumns = $oldDb->query("DESCRIBE nationalities")->fetchAll(PDO::FETCH_ASSOC);
                $oldCount = $oldDb->query("SELECT COUNT(*) FROM nationalities")->fetchColumn();
                $oldSample = $oldDb->query("SELECT * FROM nationalities LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

                $analysis['old_table'] = [
                    'name' => 'nationalities',
                    'columns' => $oldColumns,
                    'count' => $oldCount,
                    'sample_data' => $oldSample
                ];
            } catch (Exception $e) {
                $analysis['old_table'] = ['error' => $e->getMessage()];
            }

            // تحليل الجداول الجديدة
            try {
                $newMainColumns = $newDb->query("DESCRIBE nationalities")->fetchAll(PDO::FETCH_ASSOC);
                $newTransColumns = $newDb->query("DESCRIBE nationalities_translations")->fetchAll(PDO::FETCH_ASSOC);
                $newMainCount = $newDb->query("SELECT COUNT(*) FROM nationalities")->fetchColumn();
                $newTransCount = $newDb->query("SELECT COUNT(*) FROM nationalities_translations")->fetchColumn();

                $analysis['new_tables'] = [
                    'nationalities' => [
                        'columns' => $newMainColumns,
                        'count' => $newMainCount
                    ],
                    'nationalities_translations' => [
                        'columns' => $newTransColumns,
                        'count' => $newTransCount
                    ]
                ];
            } catch (Exception $e) {
                $analysis['new_tables'] = ['error' => $e->getMessage()];
            }

            // تطابق الأعمدة المحدد
            $analysis['column_mapping'] = [
                'main_table' => [
                    'id' => 'id',
                    'logo' => 'flag_image',
                    'price' => 'salary',
                    'service_transportation_cost' => 'sponsor_salary',
                    'code' => 'code',
                    'key_comment' => 'key_comment',
                    'rank' => 'rank',
                    'key_code' => 'key_code',
                    'status' => 'status',
                    'apper_home' => 'apper_home'
                ],
                'translations' => [
                    'name' => 'title - sub_title (combined)'
                ]
            ];

            sendJsonResponse([
                'success' => true,
                'analysis' => $analysis,
                'message' => 'تحليل بنية nationalities مكتمل'
            ]);
            break;

        case 'prepare_migration':
            // تحضير عملية النقل

            // فحص البيانات في الجدول القديم
            try {
                // اختبار بسيط أولاً
                $sql = "SELECT * FROM nationalities ORDER BY id LIMIT 5";
                $stmt = $oldDb->query($sql);
                $oldData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                throw new Exception("خطأ في SQL: " . $e->getMessage() . " - الاستعلام: $sql");
            }

            if (empty($oldData)) {
                throw new Exception('لا توجد بيانات في جدول nationalities القديم');
            }

            // تحليل البيانات وتحضير التحويل
            $prepared = [];
            $issues = [];

            foreach ($oldData as $row) {
                try {
                    // تحضير البيانات للجدول الرئيسي
                    $mainData = [
                        'id' => $row['id'],
                        'code' => $row['code'],
                        'key_comment' => $row['key_comment'],
                        'rank' => $row['rank'],
                        'flag_image' => $row['flag_image'], // logo في الجديد
                        'salary' => $row['salary'], // price في الجديد
                        'sponsor_salary' => $row['sponsor_salary'], // service_transportation_cost في الجديد
                        'key_code' => $row['key_code'],
                        'status' => $row['status'],
                        'apper_home' => $row['apper_home'],
                        'created_at' => $row['created_at'] ?: date('Y-m-d H:i:s'),
                        'updated_at' => $row['updated_at'] ?: date('Y-m-d H:i:s')
                    ];

                    // تحضير الترجمات
                    $translations = [];

                    // ترجمة عربية (من name)
                    if (!empty($row['name'])) {
                        $translations[] = [
                            'nationality_id' => $row['id'],
                            'locale' => 'ar',
                            'title' => $row['name'],
                            'sub_title' => '', // يمكن تقسيم name إذا احتوى على أجزاء
                            'created_at' => $row['created_at'] ?: date('Y-m-d H:i:s'),
                            'updated_at' => $row['updated_at'] ?: date('Y-m-d H:i:s')
                        ];

                        // ترجمة إنجليزية (تحويل تلقائي أو فارغة)
                        $englishName = translateToEnglish($row['name']);
                        $translations[] = [
                            'nationality_id' => $row['id'],
                            'locale' => 'en',
                            'title' => $englishName,
                            'sub_title' => '',
                            'created_at' => $row['created_at'] ?: date('Y-m-d H:i:s'),
                            'updated_at' => $row['updated_at'] ?: date('Y-m-d H:i:s')
                        ];
                    }

                    $prepared[] = [
                        'main' => $mainData,
                        'translations' => $translations
                    ];

                } catch (Exception $e) {
                    $issues[] = "مشكلة في السجل ID {$row['id']}: " . $e->getMessage();
                }
            }

            sendJsonResponse([
                'success' => true,
                'total_records' => count($oldData),
                'prepared_records' => count($prepared),
                'issues' => $issues,
                'sample_prepared' => array_slice($prepared, 0, 2),
                'message' => 'تم تحضير ' . count($prepared) . ' سجل للنقل'
            ]);
            break;

        case 'execute_migration':
            // تنفيذ النقل الفعلي
            $batchSize = (int)($_POST['batch_size'] ?? 10);
            $offset = (int)($_POST['offset'] ?? 0);
            $clearFirst = $_POST['clear_first'] ?? 'no';

            // مسح البيانات أولاً إذا طُلب
            if ($clearFirst === 'yes' && $offset === 0) {
                $newDb->exec("SET FOREIGN_KEY_CHECKS = 0");
                $newDb->exec("DELETE FROM nationalities_translations");
                $newDb->exec("DELETE FROM nationalities");
                $newDb->exec("SET FOREIGN_KEY_CHECKS = 1");
            }

            // جلب البيانات من الجدول القديم
            $stmt = $oldDb->query("
                SELECT id, code, key_comment, name, rank, flag_image, salary, sponsor_salary, key_code, status, apper_home, created_at, updated_at
                FROM nationalities
                ORDER BY id
                LIMIT $batchSize OFFSET $offset
            ");
            $oldData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($oldData)) {
                sendJsonResponse([
                    'success' => true,
                    'processed' => 0,
                    'message' => 'لا توجد بيانات أخرى للمعالجة',
                    'completed' => true
                ]);
                break;
            }

            $processed = 0;
            $errors = [];

            foreach ($oldData as $row) {
                try {
                    // إدراج في الجدول الرئيسي
                    $mainSql = "
                        INSERT INTO nationalities (
                            id, code, key_comment, rank, flag_image, salary, sponsor_salary,
                            key_code, status, apper_home, created_at, updated_at
                        ) VALUES (
                            :id, :code, :key_comment, :rank, :flag_image, :salary, :sponsor_salary,
                            :key_code, :status, :apper_home, :created_at, :updated_at
                        ) ON DUPLICATE KEY UPDATE
                            code = VALUES(code),
                            key_comment = VALUES(key_comment),
                            rank = VALUES(rank),
                            flag_image = VALUES(flag_image),
                            salary = VALUES(salary),
                            sponsor_salary = VALUES(sponsor_salary),
                            key_code = VALUES(key_code),
                            status = VALUES(status),
                            apper_home = VALUES(apper_home),
                            updated_at = VALUES(updated_at)
                    ";

                    $stmt = $newDb->prepare($mainSql);
                    $stmt->execute([
                        'id' => $row['id'],
                        'code' => $row['code'],
                        'key_comment' => $row['key_comment'],
                        'rank' => $row['rank'],
                        'flag_image' => $row['flag_image'],
                        'salary' => $row['salary'],
                        'sponsor_salary' => $row['sponsor_salary'],
                        'key_code' => $row['key_code'],
                        'status' => $row['status'],
                        'apper_home' => $row['apper_home'],
                        'created_at' => $row['created_at'] ?: date('Y-m-d H:i:s'),
                        'updated_at' => $row['updated_at'] ?: date('Y-m-d H:i:s')
                    ]);

                    // إدراج الترجمات
                    if (!empty($row['name'])) {
                        // حذف الترجمات القديمة
                        $newDb->prepare("DELETE FROM nationalities_translations WHERE nationality_id = ?")->execute([$row['id']]);

                        // ترجمة عربية
                        $transSql = "
                            INSERT INTO nationalities_translations (
                                nationality_id, locale, title, sub_title, created_at, updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ";

                        $transStmt = $newDb->prepare($transSql);

                        // عربي
                        $transStmt->execute([
                            $row['id'],
                            'ar',
                            $row['name'],
                            '',
                            $row['created_at'] ?: date('Y-m-d H:i:s'),
                            $row['updated_at'] ?: date('Y-m-d H:i:s')
                        ]);

                        // إنجليزي
                        $englishName = translateToEnglish($row['name']);
                        $transStmt->execute([
                            $row['id'],
                            'en',
                            $englishName,
                            '',
                            $row['created_at'] ?: date('Y-m-d H:i:s'),
                            $row['updated_at'] ?: date('Y-m-d H:i:s')
                        ]);
                    }

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
                'has_more' => count($oldData) === $batchSize,
                'message' => "تم معالجة $processed سجل من nationalities"
            ]);
            break;

        case 'verify_migration':
            // التحقق من النقل
            $oldCount = $oldDb->query("SELECT COUNT(*) FROM nationalities")->fetchColumn();
            $newMainCount = $newDb->query("SELECT COUNT(*) FROM nationalities")->fetchColumn();
            $newTransCount = $newDb->query("SELECT COUNT(*) FROM nationalities_translations")->fetchColumn();

            // عينة للمقارنة
            $comparison = [];
            $sample = $oldDb->query("SELECT * FROM nationalities LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sample as $oldRecord) {
                $stmt = $newDb->prepare("SELECT * FROM nationalities WHERE id = ?");
                $stmt->execute([$oldRecord['id']]);
                $newMain = $stmt->fetch(PDO::FETCH_ASSOC);

                $stmt = $newDb->prepare("SELECT * FROM nationalities_translations WHERE nationality_id = ?");
                $stmt->execute([$oldRecord['id']]);
                $newTrans = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $comparison[] = [
                    'old' => $oldRecord,
                    'new_main' => $newMain,
                    'new_translations' => $newTrans
                ];
            }

            sendJsonResponse([
                'success' => true,
                'verification' => [
                    'old_count' => $oldCount,
                    'new_main_count' => $newMainCount,
                    'new_translations_count' => $newTransCount,
                    'expected_translations' => $oldCount * 2, // عربي + إنجليزي
                    'main_match' => $oldCount === $newMainCount,
                    'translations_match' => $newTransCount >= $oldCount
                ],
                'comparison_sample' => $comparison,
                'message' => 'التحقق من النقل مكتمل'
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
function translateToEnglish($arabicName) {
    // قاموس ترجمة بسيط للجنسيات الشائعة
    $translations = [
        'سعودي' => 'Saudi',
        'مصري' => 'Egyptian',
        'فلبيني' => 'Filipino',
        'باكستاني' => 'Pakistani',
        'هندي' => 'Indian',
        'بنغلاديشي' => 'Bangladeshi',
        'سوداني' => 'Sudanese',
        'يمني' => 'Yemeni',
        'سوري' => 'Syrian',
        'أردني' => 'Jordanian',
        'لبناني' => 'Lebanese'
    ];

    return $translations[$arabicName] ?? $arabicName;
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
