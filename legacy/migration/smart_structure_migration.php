<?php
/**
 * نقل ذكي للبنية الجديدة مع الترجمات
 */

// منع جميع الـ warnings والـ output قبل JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

try {
    // تنظيف أي output سابق
    if (ob_get_level() > 0) {
        ob_clean();
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? 'analyze';

    // الاتصال بالقواعد
    $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root');
    $newDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root');

    switch ($action) {
        case 'analyze_structure':
            // تحليل الهيكل الجديد مقابل القديم
            $analysis = [];

            // فحص nationalities
            $oldNatsColumns = $oldDb->query("DESCRIBE nationalities")->fetchAll(PDO::FETCH_COLUMN);
            $newNatsColumns = $newDb->query("DESCRIBE nationalities")->fetchAll(PDO::FETCH_COLUMN);
            $newNatsTransColumns = $newDb->query("DESCRIBE nationalities_translations")->fetchAll(PDO::FETCH_COLUMN);

            // فحص religions
            $newRelsColumns = $newDb->query("DESCRIBE religions")->fetchAll(PDO::FETCH_COLUMN);
            $newRelsTransColumns = [];
            try {
                $newRelsTransColumns = $newDb->query("DESCRIBE religions_translations")->fetchAll(PDO::FETCH_COLUMN);
            } catch (Exception $e) {
                // الجدول غير موجود
            }

            // فحص maids وجداوله المرتبطة
            $newMaidsColumns = $newDb->query("DESCRIBE maids")->fetchAll(PDO::FETCH_COLUMN);
            $oldCvsColumns = $oldDb->query("DESCRIBE cvs")->fetchAll(PDO::FETCH_COLUMN);

            // فحص الجداول المرتبطة الإضافية في النظام الجديد
            $additionalTables = [];
            $potentialTables = ['offices', 'skills', 'ages', 'maid_jobs', 'languages'];

            foreach ($potentialTables as $table) {
                try {
                    $exists = $newDb->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
                    if ($exists) {
                        $count = $newDb->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                        $columns = $newDb->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
                        $additionalTables[$table] = [
                            'exists' => true,
                            'count' => $count,
                            'columns' => $columns
                        ];
                    }
                } catch (Exception $e) {
                    // الجدول غير موجود
                }
            }

            $analysis = [
                'old_structure' => [
                    'nationalities_columns' => $oldNatsColumns,
                    'cvs_columns' => $oldCvsColumns
                ],
                'new_structure' => [
                    'nationalities_columns' => $newNatsColumns,
                    'nationalities_translations_columns' => $newNatsTransColumns,
                    'religions_columns' => $newRelsColumns,
                    'religions_translations_columns' => $newRelsTransColumns,
                    'maids_columns' => $newMaidsColumns,
                    'additional_tables' => $additionalTables
                ],
                'migration_strategy' => [
                    'nationalities' => 'نقل البيانات الأساسية + إنشاء ترجمات',
                    'religions' => 'نقل أو إنشاء + ترجمات',
                    'maids' => 'نقل شامل مع إنشاء الأعمدة المفقودة',
                    'related_tables' => 'إنشاء أو تحديث الجداول المرتبطة'
                ]
            ];

            // تنظيف وإرسال JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }

            echo json_encode([
                'success' => true,
                'analysis' => $analysis,
                'message' => 'تحليل الهيكل مكتمل - النظام الجديد أكثر تطوراً'
            ], JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_nationalities_smart':
            // نقل ذكي لـ nationalities مع الترجمات
            $results = [];

            // تعطيل فحص Foreign Keys
            $newDb->exec("SET FOREIGN_KEY_CHECKS = 0");

            // مسح البيانات الحالية
            $newDb->exec("DELETE FROM nationalities_translations");
            $newDb->exec("DELETE FROM nationalities");

            // نقل البيانات الأساسية
            $nationalities = $oldDb->query("SELECT * FROM nationalities")->fetchAll(PDO::FETCH_ASSOC);
            $natsProcessed = 0;
            $transProcessed = 0;

            foreach ($nationalities as $nat) {
                try {
                    // إدراج البيانات الأساسية (فقط الأعمدة الموجودة في الجدول الجديد)
                    $phoneCode = getPhoneCodeFromCountry($nat['code'] ?? '');

                    $sql = "INSERT INTO nationalities (id, logo, phone_code, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $newDb->prepare($sql);
                    $stmt->execute([
                        $nat['id'],
                        $nat['flag_image'] ?? 'flag_' . $nat['id'] . '.png',
                        $phoneCode,
                        $nat['created_at'] ?? date('Y-m-d H:i:s'),
                        $nat['updated_at'] ?? date('Y-m-d H:i:s')
                    ]);
                    $natsProcessed++;

                    // إنشاء الترجمات
                    $translations = [
                        'ar' => $nat['name'] ?? 'جنسية ' . $nat['id'],
                        'en' => translateNationalityName($nat['name'] ?? 'Nationality ' . $nat['id'])
                    ];

                    foreach ($translations as $locale => $title) {
                        $transSql = "INSERT INTO nationalities_translations (nationality_id, locale, title, sub_title, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
                        $transStmt = $newDb->prepare($transSql);
                        $transStmt->execute([
                            $nat['id'],
                            $locale,
                            $title,
                            $title, // sub_title نفس title
                            date('Y-m-d H:i:s'),
                            date('Y-m-d H:i:s')
                        ]);
                        $transProcessed++;
                    }

                } catch (Exception $e) {
                    error_log("خطأ في نقل nationality {$nat['id']}: " . $e->getMessage());
                }
            }

            // إعادة تفعيل Foreign Keys
            $newDb->exec("SET FOREIGN_KEY_CHECKS = 1");

            $results = [
                'nationalities_processed' => $natsProcessed,
                'translations_processed' => $transProcessed
            ];

            // تنظيف وإرسال JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }

            echo json_encode([
                'success' => true,
                'results' => $results,
                'message' => "تم نقل nationalities بالهيكل الجديد - أساسي: $natsProcessed، ترجمات: $transProcessed"
            ], JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_maids_complete':
            // نقل شامل للعاملات مع إنشاء الأعمدة المفقودة
            $cvData = $oldDb->query("SELECT * FROM cvs LIMIT 5")->fetchAll(PDO::FETCH_ASSOC); // عينة أولاً

            if (empty($cvData)) {
                throw new Exception('جدول cvs فارغ');
            }

            // تحليل الأعمدة المطلوبة vs الموجودة
            $cvColumns = array_keys($cvData[0]);
            $maidsColumns = $newDb->query("DESCRIBE maids")->fetchAll(PDO::FETCH_COLUMN);

            // خريطة تحويل الأعمدة
            $columnMapping = [
                'id' => 'id',
                'cvs_name' => 'name',
                'national_id' => 'nationality_id',
                'passport_id' => 'passport_num',
                'service_price' => 'price',
                'salary' => 'salary',
                'age_id' => 'age',
                'cv_file' => 'cv_file',
                'cv_status' => 'cv_status',
                'images' => 'image',
                'skills' => 'skills',
                'birth_date' => 'birth_date',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at'
            ];

            // إضافة الأعمدة المفقودة
            $addedColumns = [];
            foreach ($cvColumns as $oldCol) {
                if (!isset($columnMapping[$oldCol])) {
                    // عمود جديد، نحتاج إضافته
                    $newColName = $oldCol;

                    if (!in_array($newColName, $maidsColumns)) {
                        try {
                            $colType = guessColumnType($oldCol);
                            $newDb->exec("ALTER TABLE maids ADD COLUMN `$newColName` $colType");
                            $addedColumns[] = $newColName;
                        } catch (Exception $e) {
                            error_log("فشل إضافة العمود $newColName: " . $e->getMessage());
                        }
                    }
                }
            }

            // الآن ننقل البيانات الفعلية
            $allCvData = $oldDb->query("SELECT * FROM cvs")->fetchAll(PDO::FETCH_ASSOC);
            $processed = 0;
            $skipped = 0;

            $newDb->exec("SET FOREIGN_KEY_CHECKS = 0");
            $newDb->exec("DELETE FROM maids"); // مسح البيانات القديمة

            foreach ($allCvData as $cv) {
                try {
                    // تحضير البيانات للإدراج
                    $maidData = [];

                    // استخدام خريطة التحويل
                    foreach ($columnMapping as $oldCol => $newCol) {
                        if (isset($cv[$oldCol])) {
                            $maidData[$newCol] = $cv[$oldCol];
                        }
                    }

                    // إضافة الأعمدة الجديدة
                    foreach ($addedColumns as $newCol) {
                        if (isset($cv[$newCol])) {
                            $maidData[$newCol] = $cv[$newCol];
                        }
                    }

                    // القيم الافتراضية المطلوبة
                    $maidData['name'] = $maidData['name'] ?? 'عاملة ' . $cv['id'];
                    $maidData['passport_num'] = $maidData['passport_num'] ?? 'CV_' . $cv['id'];
                    $maidData['nationality_id'] = $maidData['nationality_id'] ?? 1;
                    $maidData['religion_id'] = 1;
                    $maidData['cv_file'] = $maidData['cv_file'] ?? 'cv_' . $cv['id'] . '.pdf';
                    $maidData['is_show'] = 1;
                    $maidData['available'] = ($cv['cv_status'] == 0) ? 1 : 0;
                    $maidData['status'] = in_array($cv['cv_status'] ?? 0, [0, 1]) ? 'available' : 'unavailable';
                    $maidData['height'] = $maidData['height'] ?? '160';
                    $maidData['weight'] = $maidData['weight'] ?? '55';
                    $maidData['experience_years'] = '0';
                    $maidData['experience_status'] = 'no_experience';

                    // تنظيف البيانات
                    foreach ($maidData as $key => $value) {
                        if ($value === 'NULL' || $value === '') {
                            $maidData[$key] = null;
                        }
                    }

                    // إدراج البيانات
                    $columns = array_keys($maidData);
                    $placeholders = array_fill(0, count($columns), '?');

                    $sql = "INSERT INTO maids (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $newDb->prepare($sql);
                    $stmt->execute(array_values($maidData));
                    $processed++;

                } catch (Exception $e) {
                    $skipped++;
                    error_log("خطأ في نقل CV {$cv['id']}: " . $e->getMessage());
                }
            }

            $newDb->exec("SET FOREIGN_KEY_CHECKS = 1");

            // تنظيف وإرسال JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }

            echo json_encode([
                'success' => true,
                'cv_columns' => $cvColumns,
                'maids_columns' => $maidsColumns,
                'column_mapping' => $columnMapping,
                'added_columns' => $addedColumns,
                'processed' => $processed,
                'skipped' => $skipped,
                'total_cvs' => count($allCvData),
                'message' => "نقل العاملات مكتمل - أعمدة CV: " . count($cvColumns) . "، أعمدة Maids: " . count($maidsColumns) . "، مُضاف: " . count($addedColumns) . "، معالج: $processed"
            ], JSON_UNESCAPED_UNICODE);
            exit();
            break;

        default:
            // تنظيف وإرسال JSON
            if (ob_get_level() > 0) {
                ob_clean();
            }

            echo json_encode([
                'success' => false,
                'message' => 'إجراء غير صالح'
            ], JSON_UNESCAPED_UNICODE);
            exit();
    }

} catch (Exception $e) {
    // تنظيف أي output قبل إرسال JSON
    if (ob_get_level() > 0) {
        ob_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage(),
        'error_details' => [
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// تنظيف نهائي
if (ob_get_level() > 0) {
    ob_end_clean();
}

// وظائف مساعدة
function getPhoneCodeFromCountry($code) {
    $codes = [
        'SA' => '+966', 'PH' => '+63', 'KE' => '+254', 'UG' => '+256',
        'ET' => '+251', 'BD' => '+880', 'LK' => '+94', 'NP' => '+977',
        'EG' => '+20', 'IN' => '+91'
    ];
    return $codes[$code] ?? '+966';
}

function translateNationalityName($arabicName) {
    $translations = [
        'السعودية' => 'Saudi Arabia',
        'الفلبين' => 'Philippines',
        'كينيا' => 'Kenya',
        'أوغندا' => 'Uganda',
        'إثيوبيا' => 'Ethiopia',
        'بنغلاديش' => 'Bangladesh',
        'سريلانكا' => 'Sri Lanka',
        'نيبال' => 'Nepal',
        'مصر' => 'Egypt',
        'الهند' => 'India'
    ];
    return $translations[$arabicName] ?? $arabicName;
}

function guessColumnType($columnName) {
    $columnName = strtolower($columnName);

    if (strpos($columnName, 'image') !== false || strpos($columnName, 'pic') !== false) {
        return 'VARCHAR(500) NULL';
    } elseif (strpos($columnName, 'price') !== false || strpos($columnName, 'salary') !== false) {
        return 'DECIMAL(10,2) DEFAULT 0';
    } elseif (strpos($columnName, 'date') !== false) {
        return 'DATE NULL';
    } elseif (strpos($columnName, '_id') !== false) {
        return 'BIGINT UNSIGNED NULL';
    } elseif (strpos($columnName, 'percentage') !== false) {
        return 'TINYINT DEFAULT 0';
    } else {
        return 'TEXT NULL';
    }
}
?>
