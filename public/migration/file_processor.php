<?php
/**
 * معالج النظام الثابت - تطوير مستمر للميزات
 * Fixed System Processor - Continuous Feature Development
 */

// منع أي output قبل JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 600);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

// إعدادات قاعدة البيانات - مرنة للاستخدامات المتعددة
$config = [
    'host' => $_POST['db_host'] ?? $_GET['db_host'] ?? '127.0.0.1',
    'port' => $_POST['db_port'] ?? $_GET['db_port'] ?? 8889,
    'database' => $_POST['db_name'] ?? $_GET['db_name'] ?? 'platinum_new',
    'username' => $_POST['db_user'] ?? $_GET['db_user'] ?? 'root',
    'password' => $_POST['db_pass'] ?? $_GET['db_pass'] ?? 'root'
];

try {
    $action = $_POST['action'] ?? 'upload_files';

    // تنظيف أي output سابق
    ob_clean();

    switch ($action) {
        case 'test_connection':
            echo json_encode(testDatabaseConnection(), JSON_UNESCAPED_UNICODE);
            break;

        case 'check_system_status':
            echo json_encode(checkSystemStatus(), JSON_UNESCAPED_UNICODE);
            break;

        case 'create_backup':
            echo json_encode(createManualBackup(), JSON_UNESCAPED_UNICODE);
            break;

        case 'upload_files':
        default:
            echo json_encode(handleFileUpload(), JSON_UNESCAPED_UNICODE);
            break;

        case 'process_files':
            ob_clean();
            echo json_encode(processDirectFileUpload(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'compare_results':
            echo json_encode(compareResults(), JSON_UNESCAPED_UNICODE);
            break;

        case 'add_missing_columns':
            echo json_encode(addMissingColumns(), JSON_UNESCAPED_UNICODE);
            break;

        case 'reorganize_table':
            ob_clean();
            echo json_encode(reorganizeTable(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'check_table_structure':
            ob_clean();
            echo json_encode(checkTableStructure(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'debug_file_processing':
            ob_clean();
            echo json_encode(debugFileProcessing(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'check_countries_table':
            ob_clean();
            echo json_encode(checkCountriesTable(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'prepare_countries_table':
            ob_clean();
            echo json_encode(prepareCountriesTable(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'prepare_related_tables':
            ob_clean();
            echo json_encode(prepareRelatedTables(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_cv_comprehensive':
            ob_clean();
            echo json_encode(migrateCVComprehensive(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'apply_custom_order':
            ob_clean();
            echo json_encode(applyCustomTableOrder(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'prepare_users_table':
            ob_clean();
            echo json_encode(prepareUsersTable(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'add_system_status_columns':
            ob_clean();
            echo json_encode(addSystemStatusColumns(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_users_simple':
            ob_clean();
            echo json_encode(migrateUsersSimple(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_admins_from_users':
            ob_clean();
            echo json_encode(migrateAdminsFromUsers(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_staff_from_users':
            ob_clean();
            echo json_encode(migrateStaffFromUsers(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_customers_simple':
            ob_clean();
            echo json_encode(migrateCustomersSimple(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'export_from_old_db':
            ob_clean();
            echo json_encode(exportFromOldDatabase(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'process_exported_file':
            ob_clean();
            echo json_encode(processExportedFile(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_contracts':
            ob_clean();
            echo json_encode(migrateContracts(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'contracts_summary':
            ob_clean();
            echo json_encode(getContractsSummary(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_contract_infos':
            ob_clean();
            echo json_encode(migrateContractInfos(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'contract_infos_summary':
            ob_clean();
            echo json_encode(getContractInfosSummary(), JSON_UNESCAPED_UNICODE);
            exit();
            break;
        case 'migrate_accommodation_types':
            ob_clean();
            echo json_encode(migrateAccommodationTypes(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'accommodation_types_summary':
            ob_clean();
            echo json_encode(getAccommodationTypesSummary(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_airports':
            ob_clean();
            echo json_encode(migrateAirports(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'airports_summary':
            ob_clean();
            echo json_encode(getAirportsSummary(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_job_titles':
            ob_clean();
            echo json_encode(migrateJobTitles(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_religions':
            ob_clean();
            echo json_encode(migrateReligions(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_social_status':
            ob_clean();
            echo json_encode(migrateSocialStatus(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_skills':
            ob_clean();
            echo json_encode(migrateSkills(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_experience':
            ob_clean();
            echo json_encode(migrateExperience(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_ages':
            ob_clean();
            echo json_encode(migrateAges(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_languages':
            ob_clean();
            echo json_encode(migrateLanguages(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_reservations_to_orders':
            ob_clean();
            echo json_encode(migrateReservationsToOrders(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_office_translations':
            ob_clean();
            echo json_encode(migrateOfficeTranslations(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_contract_logs':
            ob_clean();
            echo json_encode(migrateContractLogs(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'contract_logs_summary':
            ob_clean();
            echo json_encode(getContractLogsSummary(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_activity_logs':
            ob_clean();
            echo json_encode(migrateActivityLogs(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'activity_logs_summary':
            ob_clean();
            echo json_encode(getActivityLogsSummary(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_messages':
            ob_clean();
            echo json_encode(migrateMessages(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'messages_summary':
            ob_clean();
            echo json_encode(getMessagesSummary(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_contacts':
            ob_clean();
            echo json_encode(migrateContacts(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'contacts_summary':
            ob_clean();
            echo json_encode(getContactsSummary(), JSON_UNESCAPED_UNICODE);
            exit();
            break;


        case 'execute_sql':
            ob_clean();
            echo json_encode(handleExecuteSql(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'scan_old_database':
            ob_clean();
            echo json_encode(scanOldDatabaseColumns(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'add_all_missing_columns':
            ob_clean();
            echo json_encode(addAllMissingColumns(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'migrate_all_data':
            ob_clean();
            echo json_encode(migrateAllDataFromOld(), JSON_UNESCAPED_UNICODE);
            exit();
            break;

        case 'get_table_structure':
            echo json_encode(getTableStructure(), JSON_UNESCAPED_UNICODE);
            break;

        case 'merge_users_customers':
            $db = get_db_connection();
            $db->beginTransaction();

            try {
                // Process users file first (similar to process_files)
                $usersData = processCsv($_FILES['users']['tmp_name']); // Assume a function to parse CSV
                foreach ($usersData as $row) {
                    // Insert or update users logic
                }

                // Get columns from customers
                $customersData = processCsv($_FILES['customers']['tmp_name']);
                $customerColumns = array_keys($customersData[0]);

                // Add missing columns to users
                $existingColumns = getTableColumns('users'); // Assume function to get columns
                foreach ($customerColumns as $col) {
                    if (!in_array($col, $existingColumns) && $col !== $_POST['link_field']) {
                        $db->exec("ALTER TABLE users ADD COLUMN $col VARCHAR(255)");
                    }
                }

                // Merge: for each customer row, update users
                $linkField = $_POST['link_field'];
                foreach ($customersData as $row) {
                    $updateParts = [];
                    $params = [];
                    foreach ($row as $key => $value) {
                        if ($key !== $linkField) {
                            $updateParts[] = "$key = ?";
                            $params[] = $value;
                        }
                    }
                    $params[] = $row[$linkField];
                    $updateSql = "UPDATE users SET " . implode(', ', $updateParts) . " WHERE id = ?";
                    $stmt = $db->prepare($updateSql);
                    $stmt->execute($params);
                }

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'تم دمج Users و Customers بنجاح']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'bulk_import':
            $db = get_db_connection();
            $db->beginTransaction();

            try {
                $results = [];

                // ملفات السيرة الذاتية
                if (isset($_FILES['cv_bulk_file']) && $_FILES['cv_bulk_file']['error'] == 0) {
                    $results[] = processCvImport($_FILES['cv_bulk_file']['tmp_name'], $db);
                }

                // ملفات الحجوزات
                if (isset($_FILES['reserviton_bulk_file']) && $_FILES['reserviton_bulk_file']['error'] == 0) {
                    $results[] = processReservationImport($_FILES['reserviton_bulk_file']['tmp_name'], $db);
                }

                // ملفات العقود
                if (isset($_FILES['contract_bulk_file']) && $_FILES['contract_bulk_file']['error'] == 0) {
                    $results[] = processContractImport($_FILES['contract_bulk_file']['tmp_name'], $db);
                }

                // ملفات جدول الإيواء
                if (isset($_FILES['cv_sponsor_bulk_file']) && $_FILES['cv_sponsor_bulk_file']['error'] == 0) {
                    $results[] = processCvSponsorImport($_FILES['cv_sponsor_bulk_file']['tmp_name'], $db);
                }

                // ملفات حجوزات نقل الكفالة
                if (isset($_FILES['reservation_sponsor_bulk_file']) && $_FILES['reservation_sponsor_bulk_file']['error'] == 0) {
                    $results[] = processReservationSponsorImport($_FILES['reservation_sponsor_bulk_file']['tmp_name'], $db);
                }

                // ملفات تعليقات الإيواء
                if (isset($_FILES['lodging_notes_bulk_file']) && $_FILES['lodging_notes_bulk_file']['error'] == 0) {
                    $results[] = processLodgingNotesImport($_FILES['lodging_notes_bulk_file']['tmp_name'], $db);
                }

                // ملفات الإيواء (1-6)
                for ($i = 1; $i <= 6; $i++) {
                    $fileKey = "lodging_import_$i";
                    if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 0) {
                        $results[] = processLodgingImport($_FILES[$fileKey]['tmp_name'], $db, $i);
                    }
                }

                $db->commit();

                $successCount = count($results);
                echo json_encode([
                    'success' => true,
                    'message' => "تم الانتهاء من استيراد $successCount ملف بنجاح\n" . implode("\n", $results)
                ]);

            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'process_users_step':
            $db = null;
            try {
                $db = get_db_connection();
                $db->beginTransaction();

                if (!isset($_FILES['users_file']) || $_FILES['users_file']['error'] != 0) {
                    throw new Exception('لم يتم رفع ملف Users بشكل صحيح');
                }

                $data = processCsv($_FILES['users_file']['tmp_name']);
                if (empty($data)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ملف Users فارغ أو غير صالح'
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                // إضافة الأعمدة المفقودة بدلاً من إنشاء جدول جديد
                $addedColumns = addMissingColumnsToUsers($db);

                $successCount = 0;

                // بناء الاستعلام ديناميكياً حسب الأعمدة الموجودة في الملف
                $fileColumns = array_keys($data[0]);
                $dbColumns = [];
                $placeholders = [];

                // التحقق من وجود الأعمدة في قاعدة البيانات
                foreach ($fileColumns as $column) {
                    $checkStmt = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
                    $checkStmt->execute([$column]);
                    if ($checkStmt->rowCount() > 0) {
                        $dbColumns[] = $column;
                        $placeholders[] = '?';
                    }
                }

                // إضافة أعمدة ثابتة
                if (!in_array('register_from', $dbColumns)) {
                    $dbColumns[] = 'register_from';
                    $placeholders[] = '?';
                }

                $insertSql = "INSERT IGNORE INTO users (" . implode(', ', $dbColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $db->prepare($insertSql);

                foreach ($data as $row) {
                    try {
                        $row = cleanRowData($row);
                        if (empty($row['id'])) continue;

                        // معالجة ذكية للبيانات مع تقسيم name
                        $values = [];

                        // تقسيم name إلى first_name + last_name
                        $name = trim($row['name'] ?? '');
                        $nameParts = explode(' ', $name, 2);
                        $firstName = $nameParts[0] ?? '';
                        $lastName = $nameParts[1] ?? '';

                        foreach ($dbColumns as $column) {
                            if ($column === 'name') {
                                $values[] = $name;
                            } elseif ($column === 'first_name') {
                                $values[] = $firstName;
                            } elseif ($column === 'last_name') {
                                $values[] = $lastName;
                            } elseif ($column === 'register_from') {
                                $values[] = 'web';
                            } elseif ($column === 'phone') {
                                // تنظيف رقم الهاتف
                                $phone = cleanPhoneNumber($row['phone'] ?? '');
                                $values[] = $phone;
                            } else {
                                $values[] = $row[$column] ?? null;
                            }
                        }

                        $stmt->execute($values);
                        $successCount++;

                    } catch (Exception $e) {
                        error_log("خطأ في معالجة المستخدم: " . $e->getMessage());
                    }
                }

                $db->commit();
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'message' => "✅ تم رفع $successCount مستخدم بنجاح\n📋 أعمدة مُضافة: " . count($addedColumns) . "\n🔗 الجدول جاهز لربط Customers",
                    'added_columns' => $addedColumns
                ], JSON_UNESCAPED_UNICODE);
                exit();

            } catch (Exception $e) {
                if ($db && $db->inTransaction()) {
                    $db->rollBack();
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'خطأ في معالجة Users: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            break;

        case 'process_customers_step':
            $db = null;
            try {
                $db = get_db_connection();
                $db->beginTransaction();

                if (!isset($_FILES['customers_file']) || $_FILES['customers_file']['error'] != 0) {
                    throw new Exception('لم يتم رفع ملف Customers بشكل صحيح');
                }

                $data = processCsv($_FILES['customers_file']['tmp_name']);
                if (empty($data)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'ملف Customers فارغ أو غير صالح'
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }

                $linkField = $_POST['link_field'] ?? 'user_id';
                $createMissingUsers = isset($_POST['create_missing_users']) && $_POST['create_missing_users'] === 'true';
                $enableColumnMapping = isset($_POST['enable_column_mapping']) && $_POST['enable_column_mapping'] === 'true';

                // Column mapping rules
                $columnMapping = [
                    'national_identification_number' => 'national_num',
                    'other_phone' => 'phone2',
                    'job' => 'job'
                ];

                // Add missing columns to users table if column mapping is enabled
                if ($enableColumnMapping) {
                    $mappedColumns = [];
                    foreach ($data[0] as $customerCol => $value) {
                        if (isset($columnMapping[$customerCol])) {
                            $userCol = $columnMapping[$customerCol];
                            $mappedColumns[] = $userCol;

                            // Add column to users if it doesn't exist
                            $checkCol = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
                            $checkCol->execute([$userCol]);
                            if ($checkCol->rowCount() == 0) {
                                $colType = ($userCol === 'job') ? 'VARCHAR(255)' : 'VARCHAR(100)';
                                $db->exec("ALTER TABLE users ADD COLUMN $userCol $colType");
                            }
                        }
                    }
                }

                // Create customers table
                $columns = array_keys($data[0]);
                $columnDefs = [];
                foreach ($columns as $col) {
                    if ($col === 'id') {
                        $columnDefs[] = 'id INT PRIMARY KEY';
                    } elseif ($col === $linkField) {
                        $columnDefs[] = "$col INT";
                    } elseif (in_array($col, ['created_at', 'updated_at'])) {
                        $columnDefs[] = "$col TIMESTAMP NULL";
                    } else {
                        $columnDefs[] = "$col VARCHAR(255)";
                    }
                }

                $db->exec("CREATE TABLE IF NOT EXISTS customers (" . implode(', ', $columnDefs) . ")");

                $successCount = 0;
                $missingUsers = 0;
                $linkedCount = 0;

                foreach ($data as $row) {
                    try {
                        $row = cleanRowData($row);
                        if (empty($row['id'])) continue;

                        // التحقق من وجود User والقيام بالـ column mapping
                        if (!empty($row[$linkField])) {
                            $userId = (int)$row[$linkField];
                            $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
                            $stmt->execute([$userId]);

                            if (!$stmt->fetch()) {
                                if ($createMissingUsers) {
                                    // إنشاء user مفقود
                                    $registerFromValue = isset($_POST['setDefaultRegisterFrom']) && $_POST['setDefaultRegisterFrom'] === 'true' ? 'web' : 'auto_created';
                                    $createStmt = $db->prepare("INSERT IGNORE INTO users (id, register_from, created_at) VALUES (?, ?, NOW())");
                                    $createStmt->execute([$userId, $registerFromValue]);
                                    $missingUsers++;
                                }
                            }

                            // Column mapping: update user with customer data
                            if ($enableColumnMapping) {
                                $updateParts = [];
                                $updateParams = [];

                                foreach ($row as $customerCol => $value) {
                                    if (isset($columnMapping[$customerCol]) && !empty($value)) {
                                        $userCol = $columnMapping[$customerCol];
                                        $updateParts[] = "$userCol = ?";
                                        $updateParams[] = $value;
                                    }
                                }

                                if (!empty($updateParts)) {
                                    $updateParams[] = $userId;
                                    $updateSql = "UPDATE users SET " . implode(', ', $updateParts) . " WHERE id = ?";
                                    $updateStmt = $db->prepare($updateSql);
                                    $updateStmt->execute($updateParams);
                                }
                            }

                            $linkedCount++;
                        }

                        // Insert customer
                        $placeholders = implode(',', array_fill(0, count($columns), '?'));
                        $insertSql = "INSERT IGNORE INTO customers (" . implode(',', $columns) . ") VALUES ($placeholders)";
                        $stmt = $db->prepare($insertSql);
                        $stmt->execute(array_values($row));
                        $successCount++;

                    } catch (Exception $e) {
                        error_log("خطأ في معالجة العميل: " . $e->getMessage());
                    }
                }

                $db->commit();

                $mappingMessage = $enableColumnMapping ? "\n🔄 تم تطبيق column mapping" : "";
                echo json_encode([
                    'success' => true,
                    'message' => "✅ تم ربط $successCount عميل\n🔗 مرتبطين: $linkedCount\n➕ مستخدمين مُنشأين: $missingUsers$mappingMessage"
                ]);

            } catch (Exception $e) {
                if ($db && $db->inTransaction()) {
                    $db->rollBack();
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'خطأ في ربط Customers: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            break;

        case 'verify_relationship':
            try {
                $db = get_db_connection();
                $linkField = $_POST['link_field'] ?? 'user_id';

                // إحصائيات Users
                $usersStmt = $db->query("SELECT COUNT(*) as count FROM users");
                $usersCount = $usersStmt->fetch()['count'];

                // إحصائيات Customers
                $customersStmt = $db->query("SELECT COUNT(*) as count FROM customers");
                $customersCount = $customersStmt->fetch()['count'];

                // العملاء المرتبطين
                $linkedStmt = $db->query("SELECT COUNT(*) as count FROM customers WHERE $linkField IS NOT NULL AND $linkField != 0");
                $linkedCount = $linkedStmt->fetch()['count'];

                // العملاء غير المرتبطين
                $unlinkedCount = $customersCount - $linkedCount;

                // مستخدمين لهم عملاء
                $usersWithCustomersStmt = $db->query("SELECT COUNT(DISTINCT $linkField) as count FROM customers WHERE $linkField IS NOT NULL AND $linkField != 0");
                $usersWithCustomers = $usersWithCustomersStmt->fetch()['count'];

                $report = "
                    <ul>
                        <li><strong>إجمالي المستخدمين:</strong> $usersCount</li>
                        <li><strong>إجمالي العملاء:</strong> $customersCount</li>
                        <li><strong>عملاء مرتبطين:</strong> $linkedCount</li>
                        <li><strong>عملاء غير مرتبطين:</strong> $unlinkedCount</li>
                        <li><strong>مستخدمين لهم عملاء:</strong> $usersWithCustomers</li>
                        <li><strong>معدل الربط:</strong> " . round(($linkedCount / max($customersCount, 1)) * 100, 2) . "%</li>
                    </ul>
                ";

                echo json_encode([
                    'success' => true,
                    'report' => $report
                ]);

            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'خطأ في التحقق من الربط: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            break;
    }

} catch (Exception $e) {
    // تنظيف أي output قبل JSON
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    // إرسال JSON نظيف
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage(),
        'error_line' => $e->getLine(),
        'error_file' => basename($e->getFile())
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// إنهاء output buffer إذا كان مفتوحاً
if (ob_get_level() > 0) {
    ob_end_flush();
}

/**
 * إعادة ترتيب الجدول حسب المطلوب
 */
function reorganizeTable() {
    try {
        $db = get_db_connection();

        // التحقق من وجود الأعمدة الأساسية أولاً
        $requiredColumns = [
            'name' => 'VARCHAR(255) NULL',
            'first_name' => 'VARCHAR(255) NULL',
            'last_name' => 'VARCHAR(255) NULL',
            'phone_code' => 'VARCHAR(255) NULL DEFAULT "+966"',
            'phone' => 'VARCHAR(255) NULL',
            'email' => 'VARCHAR(255) NULL',
            'password' => 'VARCHAR(255) NOT NULL DEFAULT ""',
            'register_from' => 'VARCHAR(255) NULL DEFAULT "web"',
            'is_blocked' => 'TINYINT NULL DEFAULT 0',
            'status' => 'TINYINT NULL DEFAULT 1',
            'remember_token' => 'VARCHAR(100) NULL',
            'national_num' => 'VARCHAR(50) NULL',
            'nationality_id' => 'BIGINT UNSIGNED NULL',
            'job' => 'VARCHAR(255) NULL',
            'birthdate' => 'DATE NULL',
            'phone2' => 'VARCHAR(50) NULL',
            'city' => 'VARCHAR(100) NULL',
            'address' => 'VARCHAR(500) NULL'
        ];

        // إضافة الأعمدة المفقودة
        $addedColumns = [];
        foreach ($requiredColumns as $column => $definition) {
            $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
            $stmt->execute([$column]);
            if ($stmt->rowCount() == 0) {
                try {
                    $db->exec("ALTER TABLE users ADD COLUMN `$column` $definition");
                    $addedColumns[] = $column;
                } catch (Exception $e) {
                    // تجاهل الخطأ والمتابعة
                }
            }
        }

        // ترتيب الأعمدة المحسن - created_at و updated_at في النهاية
        $orderingSteps = [];

        // الترتيب المطلوب (created_at و updated_at في النهاية)
        $desiredOrder = [
            'id', 'name', 'first_name', 'last_name', 'phone_code', 'phone', 'phone2','national_num',
            'email', 'password', 'user_type',  'status','register_from', 'is_blocked',
            'remember_token', 'token',  'nationality_id', 'job',
            'birthdate',  'city', 'address','country', 'region',
            'beds_num', 'phone_verified_at', 'verification_code',
            'avatar','rooms_num',
            'family_num', 'children', 'profession', 'monthly_income',
            'created_at', 'updated_at'  // هذين في النهاية
        ];

        // الحصول على جميع الأعمدة الموجودة مع تفاصيلها
        $existingColumnsData = [];
        $result = $db->query("DESCRIBE users");
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $existingColumnsData[$row['Field']] = [
                'type' => $row['Type'],
                'null' => $row['Null'],
                'default' => $row['Default'],
                'extra' => $row['Extra']
            ];
        }

        // بناء الترتيب للأعمدة الموجودة فقط
        $existingOrderedColumns = [];

        // إضافة الأعمدة حسب الترتيب المرغوب
        foreach ($desiredOrder as $col) {
            if (isset($existingColumnsData[$col])) {
                $existingOrderedColumns[] = $col;
            }
        }

        // إضافة أي أعمدة أخرى لم تكن في الترتيب المرغوب (ما عدا created_at و updated_at)
        foreach ($existingColumnsData as $col => $data) {
            if (!in_array($col, $existingOrderedColumns) &&
                $col !== 'created_at' && $col !== 'updated_at') {
                // إدراج قبل created_at إذا وجد
                $insertIndex = array_search('created_at', $existingOrderedColumns);
                if ($insertIndex !== false) {
                    array_splice($existingOrderedColumns, $insertIndex, 0, [$col]);
                } else {
                    $existingOrderedColumns[] = $col;
                }
            }
        }

        // التأكد من أن created_at و updated_at في النهاية
        if (isset($existingColumnsData['created_at']) &&
            !in_array('created_at', $existingOrderedColumns)) {
            $existingOrderedColumns[] = 'created_at';
        }
        if (isset($existingColumnsData['updated_at']) &&
            !in_array('updated_at', $existingOrderedColumns)) {
            $existingOrderedColumns[] = 'updated_at';
        }

        // بناء أوامر الترتيب
        $prevColumn = null;
        foreach ($existingOrderedColumns as $col) {
            $colData = $existingColumnsData[$col];

            // بناء تعريف العمود
            $colDefinition = $colData['type'];

            if ($colData['null'] === 'NO') {
                $colDefinition .= ' NOT NULL';
            } else {
                $colDefinition .= ' NULL';
            }

            if ($colData['default'] !== null) {
                $colDefinition .= " DEFAULT '" . $colData['default'] . "'";
            }

            if (!empty($colData['extra'])) {
                $colDefinition .= ' ' . $colData['extra'];
            }

            // بناء أمر ALTER TABLE
            if ($prevColumn === null) {
                $orderingSteps[] = "ALTER TABLE users MODIFY `$col` $colDefinition FIRST";
            } else {
                $orderingSteps[] = "ALTER TABLE users MODIFY `$col` $colDefinition AFTER `$prevColumn`";
            }

            $prevColumn = $col;
        }

        $appliedSteps = 0;
        foreach ($orderingSteps as $sql) {
            try {
                $db->exec($sql);
                $appliedSteps++;
            } catch (Exception $e) {
                // متابعة حتى لو فشلت بعض الخطوات
            }
        }

        return [
            'success' => true,
            'message' => "✅ تم ترتيب الجدول بنجاح!\n📋 أعمدة مضافة: " . count($addedColumns) . "\n📐 خطوات ترتيب مطبقة: $appliedSteps/" . count($orderingSteps),
            'added_columns' => $addedColumns,
            'applied_steps' => $appliedSteps
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في ترتيب الجدول: ' . $e->getMessage()
        ];
    }
}

/**
 * إضافة الأعمدة المطلوبة من SYSTEM_STATUS.md
 */
function addSystemStatusColumns() {
    try {
        $db = get_db_connection();

        // الأعمدة المطلوبة حسب SYSTEM_STATUS.md
        $requiredColumns = [
            // البيانات الجديدة
            'first_name' => 'VARCHAR(255) NULL',
            'last_name' => 'VARCHAR(255) NULL',
            'phone_code' => 'VARCHAR(255) NULL DEFAULT "+966"',
            'register_from' => 'VARCHAR(255) NULL DEFAULT "migration"',
            'is_blocked' => 'TINYINT NULL DEFAULT 0',
            'status' => 'TINYINT NULL DEFAULT 1',

            // من customers
            'birthdate' => 'DATE NULL',
            'national_num' => 'VARCHAR(50) NULL',
            'phone2' => 'VARCHAR(50) NULL',
            'rooms_num' => 'VARCHAR(10) NULL',
            'family_num' => 'VARCHAR(10) NULL',
            'children' => 'VARCHAR(10) NULL',
            'job' => 'VARCHAR(255) NULL',
            'profession' => 'VARCHAR(255) NULL',
            'monthly_income' => 'DECIMAL(10,2) NULL',
            'beds_num' => 'INT NULL',

            // الأعمدة الناقصة من القديم
            'user_type' => 'VARCHAR(20) NULL',
            'phone_verified_at' => 'TIMESTAMP NULL',
            'verification_code' => 'TEXT NULL',
            'token' => 'TEXT NULL',
            'avatar' => 'VARCHAR(256) NULL',
            'country' => 'VARCHAR(30) NULL',
            'region' => 'VARCHAR(255) NULL'
        ];

        $addedColumns = [];

        foreach ($requiredColumns as $column => $definition) {
            try {
                $checkStmt = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
                $checkStmt->execute([$column]);

                if ($checkStmt->rowCount() == 0) {
                    $db->exec("ALTER TABLE users ADD COLUMN `$column` $definition");
                    $addedColumns[] = $column;
                }
            } catch (Exception $e) {
                // تسجيل ومتابعة
                error_log("خطأ في إضافة عمود $column: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'added_columns' => $addedColumns,
            'message' => "✅ تم إضافة " . count($addedColumns) . " عمود من SYSTEM_STATUS.md"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ: ' . $e->getMessage()
        ];
    }
}

/**
 * نقل المستخدمين بشكل بسيط ومباشر
 */
function migrateUsersSimple() {
    try {
        // الاتصال بالقاعدة القديمة
        $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_old;charset=utf8mb4", 'root', 'root');
        $newDb = get_db_connection();

        // جلب جميع المستخدمين من القاعدة القديمة
        $oldUsers = $oldDb->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($oldUsers)) {
            throw new Exception('لا توجد بيانات في القاعدة القديمة');
        }

        $migratedCount = 0;
        $updatedCount = 0;
        $namesSplit = 0;

        foreach ($oldUsers as $user) {
            try {
                // تحضير البيانات
                $data = [];

                // البيانات المباشرة
                $data['id'] = $user['id'];
                $data['name'] = $user['name'] ?? null;
                $data['email'] = $user['email'] ?? null;
                $data['phone'] = $user['phone'] ?? null;
                $data['password'] = $user['password'] ?? null;
                $data['remember_token'] = $user['remember_token'] ?? null;
                $data['created_at'] = $user['created_at'] ?? null;
                $data['updated_at'] = $user['updated_at'] ?? null;
                $data['address'] = $user['address'] ?? null;
                $data['city'] = $user['city'] ?? null;
                $data['nationality_id'] = $user['nationality_id'] ?? null;

                // تقسيم الاسم
                if (!empty($user['name'])) {
                    $nameParts = explode(' ', trim($user['name']), 2);
                    $data['first_name'] = $nameParts[0] ?? '';
                    $data['last_name'] = $nameParts[1] ?? '';
                    $namesSplit++;
                }

                // التحويلات
                $data['is_blocked'] = isset($user['banned']) ? (int)$user['banned'] : 0;
                $data['status'] = isset($user['banned']) ? (1 - (int)$user['banned']) : 1;
                $data['phone2'] = $user['whatsapp_phone'] ?? null;

                // الأعمدة الناقصة إن وجدت
                $data['user_type'] = $user['user_type'] ?? null;
                $data['phone_verified_at'] = $user['phone_verified_at'] ?? null;
                $data['verification_code'] = $user['verification_code'] ?? null;
                $data['token'] = $user['token'] ?? null;

                // القيم الافتراضية
                $data['register_from'] = 'migration';
                $data['phone_code'] = '+966';

                // التحقق من وجود المستخدم
                $checkStmt = $newDb->prepare("SELECT id FROM users WHERE id = ?");
                $checkStmt->execute([$user['id']]);

                if ($checkStmt->rowCount() > 0) {
                    // تحديث موجود
                    $updateFields = [];
                    $updateValues = [];
                    foreach ($data as $field => $value) {
                        if ($field !== 'id') {
                            $updateFields[] = "`$field` = ?";
                            $updateValues[] = $value;
                        }
                    }
                    $updateValues[] = $user['id'];

                    $updateSql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $updateStmt = $newDb->prepare($updateSql);
                    $updateStmt->execute($updateValues);
                    $updatedCount++;
                } else {
                    // إدراج جديد
                    $fields = array_keys($data);
                    $placeholders = array_fill(0, count($fields), '?');

                    $insertSql = "INSERT INTO users (`" . implode('`, `', $fields) . "`) VALUES (" . implode(', ', $placeholders) . ")";
                    $insertStmt = $newDb->prepare($insertSql);
                    $insertStmt->execute(array_values($data));
                    $migratedCount++;
                }

            } catch (Exception $e) {
                error_log("خطأ في نقل المستخدم {$user['id']}: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'total_users' => count($oldUsers),
            'migrated' => $migratedCount,
            'updated' => $updatedCount,
            'names_split' => $namesSplit,
            'message' => "✅ تم نقل المستخدمين بنجاح!\n📊 إجمالي: " . count($oldUsers) . "\n➕ جديد: $migratedCount\n🔄 محدث: $updatedCount"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في نقل المستخدمين: ' . $e->getMessage()
        ];
    }
}

function migrateAdminsFromUsers() {
    try {
        $db = get_db_connection();

        $totalAdmins = (int)$db->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'")->fetchColumn();

        if ($totalAdmins === 0) {
            return [
                'success' => false,
                'message' => 'لا يوجد أي مستخدمين بنوع admin في جدول users.'
            ];
        }

        // تحديث أي سجلات حالية في جدول admins مرتبطة بنفس البريد الإلكتروني
        $updateSql = "UPDATE admins a
            JOIN users u ON u.email = a.email AND u.user_type = 'admin'
            SET a.name = u.name,
                a.phone = NULLIF(u.phone, ''),
                a.password = u.password,
                a.remember_token = u.remember_token,
                a.admin_type = 'admin',
                a.sort = COALESCE(a.sort, 999),
                a.lang = COALESCE(a.lang, 'ar'),
                a.updated_at = COALESCE(u.updated_at, NOW()),
                a.whatsapp = COALESCE(NULLIF(u.phone, ''), a.whatsapp)";
        $updated = $db->exec($updateSql);

        // إدراج السجلات غير الموجودة مسبقًا
        $insertSql = "INSERT INTO admins (name, phone, email, password, remember_token, admin_type, sort, role_id, lang, created_at, updated_at, whatsapp)
            SELECT u.name,
                   NULLIF(u.phone, ''),
                   u.email,
                   u.password,
                   u.remember_token,
                   'admin',
                   999,
                   NULL,
                   'ar',
                   COALESCE(u.created_at, NOW()),
                   COALESCE(u.updated_at, NOW()),
                   COALESCE(NULLIF(u.phone, ''), '')
            FROM users u
            WHERE u.user_type = 'admin'
              AND u.email IS NOT NULL
              AND u.email <> ''
              AND NOT EXISTS (
                  SELECT 1 FROM admins a WHERE a.email = u.email
              )";
        $inserted = $db->exec($insertSql);

        return [
            'success' => true,
            'total_admin_users' => $totalAdmins,
            'inserted' => $inserted,
            'updated' => $updated,
            'message' => "✅ تمت مزامنة المدراء بنجاح.\nإجمالي في users: {$totalAdmins}\n➕ تمت إضافة: {$inserted}\n🔄 تم تحديث: {$updated}"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل مزامنة المدراء: ' . $e->getMessage()
        ];
    }
}

function migrateStaffFromUsers() {
    try {
        $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_old;charset=utf8mb4", 'root', 'root');
        $oldDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $newDb = get_db_connection();

        $staffUsers = $oldDb->query("SELECT * FROM users WHERE user_type = 'staff'")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($staffUsers)) {
            return [
                'success' => false,
                'message' => 'لا يوجد أي مستخدمين بنوع staff في القاعدة القديمة.'
            ];
        }

        $checkStmt = $newDb->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
        $updateStmt = $newDb->prepare("UPDATE admins SET name = ?, phone = ?, password = ?, remember_token = ?, admin_type = ?, lang = ?, whatsapp = ?, updated_at = ? WHERE id = ?");
        $insertStmt = $newDb->prepare("INSERT INTO admins (name, phone, email, password, remember_token, admin_type, sort, role_id, lang, created_at, updated_at, whatsapp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($staffUsers as $user) {
            $email = trim($user['email'] ?? '');
            if ($email === '') {
                $skipped++;
                continue;
            }

            $name = $user['name'] ?? null;
            $phone = trim($user['phone'] ?? '');
            $phoneValue = $phone === '' ? null : $phone;
            $whatsappPhone = trim($user['whatsapp_phone'] ?? '');
            $whatsapp = $whatsappPhone !== '' ? $whatsappPhone : ($phoneValue ?? '');
            $password = $user['password'] ?? null;
            $rememberToken = $user['remember_token'] ?? null;
            $lang = $user['lang'] ?? 'ar';
            $createdAt = $user['created_at'] ?? null;
            if (empty($createdAt) || $createdAt === '0000-00-00 00:00:00') {
                $createdAt = date('Y-m-d H:i:s');
            }
            $updatedAt = $user['updated_at'] ?? null;
            if (empty($updatedAt) || $updatedAt === '0000-00-00 00:00:00') {
                $updatedAt = date('Y-m-d H:i:s');
            }

            $checkStmt->execute([$email]);
            $adminId = $checkStmt->fetchColumn();

            if ($adminId) {
                $updateStmt->execute([
                    $name,
                    $phoneValue,
                    $password,
                    $rememberToken,
                    'staff',
                    $lang,
                    $whatsapp,
                    $updatedAt,
                    $adminId
                ]);
                $updated++;
            } else {
                $insertStmt->execute([
                    $name,
                    $phoneValue,
                    $email,
                    $password,
                    $rememberToken,
                    'staff',
                    999,
                    null,
                    $lang,
                    $createdAt,
                    $updatedAt,
                    $whatsapp
                ]);
                $inserted++;
            }
        }

        return [
            'success' => true,
            'total_staff' => count($staffUsers),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'message' => "✅ تمت مزامنة طاقم العمل بنجاح.\nإجمالي: " . count($staffUsers) . "\n➕ جديد: $inserted\n🔄 محدث: $updated\n⏭️ تم تخطي: $skipped"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل مزامنة الطاقم: ' . $e->getMessage()
        ];
    }
}

/**
 * نقل Customers ودمجها مع Users
 */
function migrateCustomersSimple() {
    try {
        // الاتصال بالقاعدة القديمة
        $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_old;charset=utf8mb4", 'root', 'root');
        $newDb = get_db_connection();

        // جلب جميع Customers من القاعدة القديمة
        $customers = $oldDb->query("SELECT * FROM customers")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($customers)) {
            return [
                'success' => true,
                'message' => 'لا توجد بيانات customers في القاعدة القديمة'
            ];
        }

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($customers as $customer) {
            try {
                $userId = $customer['user_id'] ?? null;

                if (!$userId) {
                    $skippedCount++;
                    continue;
                }

                // تحضير البيانات للتحديث
                $updateData = [];

                // التحويلات حسب SYSTEM_STATUS.md
                if (isset($customer['national_identification_number'])) {
                    $updateData['national_num'] = $customer['national_identification_number'];
                }

                if (isset($customer['other_phone'])) {
                    $updateData['phone2'] = $customer['other_phone'];
                }

                if (isset($customer['job'])) {
                    $updateData['job'] = $customer['job'];
                    $updateData['profession'] = $customer['job'];
                }

                if (isset($customer['birth_date'])) {
                    $updateData['birthdate'] = $customer['birth_date'];
                }

                if (isset($customer['rooms_number'])) {
                    $updateData['rooms_num'] = $customer['rooms_number'];
                }

                if (isset($customer['family_number'])) {
                    $updateData['family_num'] = $customer['family_number'];
                }

                if (isset($customer['childrens_under_age'])) {
                    $updateData['children'] = $customer['childrens_under_age'];
                }

                // تحديث المستخدم
                if (!empty($updateData)) {
                    $updateFields = [];
                    $updateValues = [];
                    foreach ($updateData as $field => $value) {
                        $updateFields[] = "`$field` = ?";
                        $updateValues[] = $value;
                    }
                    $updateValues[] = $userId;

                    $updateSql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $updateStmt = $newDb->prepare($updateSql);
                    $updateStmt->execute($updateValues);
                    $updatedCount++;
                }

            } catch (Exception $e) {
                $skippedCount++;
                error_log("خطأ في دمج customer للمستخدم {$userId}: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'total_customers' => count($customers),
            'updated_users' => $updatedCount,
            'skipped' => $skippedCount,
            'message' => "✅ تم دمج Customers بنجاح!\n📊 إجمالي: " . count($customers) . "\n🔄 محدث: $updatedCount\n⏭️ تخطي: $skippedCount"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في دمج Customers: ' . $e->getMessage()
        ];
    }
}

/**
 * معالجة رفع الملفات
 */
function handleFileUpload() {
    $uploadDir = __DIR__ . '/uploaded_files/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadedFiles = [];
    $filesCount = intval($_POST['files_count'] ?? 0);

    for ($i = 0; $i < $filesCount; $i++) {
        $fileKey = "file_$i";

        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileKey];

            // حفظ الملف
            $fileName = date('YmdHis') . '_' . $i . '_' . $file['name'];
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // تحليل دقيق للملف
                $analysis = analyzeCSVFile($filePath);

                $uploadedFiles[] = [
                    'original_name' => $file['name'],
                    'stored_name' => $fileName,
                    'file_path' => $filePath,
                    'size' => $file['size'],
                    'records_count' => $analysis['total_rows'],
                    'headers' => $analysis['headers'],
                    'preview_data' => $analysis['preview_data'],
                    'table_type' => detectTableType($file['name'], $analysis['headers'])
                ];
            }
        }
    }

    return [
        'success' => true,
        'files' => $uploadedFiles,
        'message' => 'تم رفع ' . count($uploadedFiles) . ' ملف بنجاح'
    ];
}

/**
 * معالجة الملفات المرفوعة فعلياً
 */
function processUploadedFiles() {
    global $config;

    try {
        // ترتيب الجدول أولاً قبل المعالجة
        $reorgResult = reorganizeTable();
        error_log("نتيجة ترتيب الجدول: " . json_encode($reorgResult));

        // قراءة معلومات الملفات المرفوعة
        $filesData = json_decode($_POST['files'] ?? '[]', true);

        if (empty($filesData)) {
            // البحث عن الملفات المرفوعة في المجلد
            $uploadDir = __DIR__ . '/uploaded_files/';
            $filesData = scanUploadedFiles($uploadDir);
        }

        // الاتصال بقاعدة البيانات المستهدفة
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // إضافة الأعمدة المطلوبة
        addRequiredColumns($pdo);

        $results = [
            'total_files' => 0,
            'total_records' => 0,
            'users_count' => 0,
            'maids_count' => 0,
            'orders_count' => 0,
            'file_details' => []
        ];

        // معالجة كل ملف
        foreach ($filesData as $fileInfo) {
            if (!file_exists($fileInfo['file_path'])) {
                continue;
            }

            $processed = processFile($pdo, $fileInfo);

            $results['total_files']++;
            $results['total_records'] += $processed['records_processed'];
            $results['file_details'][] = [
                'file_name' => $fileInfo['original_name'],
                'records_processed' => $processed['records_processed'],
                'table_type' => $fileInfo['table_type']
            ];

            // تصنيف النتائج
            switch ($fileInfo['table_type']) {
                case 'users':
                    $results['users_count'] += $processed['records_processed'];
                    break;
                case 'maids':
                    $results['maids_count'] += $processed['records_processed'];
                    break;
                case 'orders':
                    $results['orders_count'] += $processed['records_processed'];
                    break;
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'message' => "تم معالجة {$results['total_files']} ملف ونقل {$results['total_records']} سجل"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في معالجة الملفات: ' . $e->getMessage()
        ];
    }
}

/**
 * معالجة ملف واحد
 */
function processFile($pdo, $fileInfo) {
    $filePath = $fileInfo['file_path'];
    $tableType = $fileInfo['table_type'];
    $processed = 0;

    if (!file_exists($filePath)) {
        throw new Exception("الملف غير موجود: " . $fileInfo['original_name']);
    }

    if (($handle = fopen($filePath, "r")) !== false) {
        // قراءة العناوين
        $headers = fgetcsv($handle, 0, ',');
        if (!$headers) {
            throw new Exception("لا يمكن قراءة عناوين الملف: " . $fileInfo['original_name']);
        }

        $headers = array_map('trim', $headers);

        // معالجة حسب نوع الجدول
        switch ($tableType) {
            case 'users':
                $processed = processUsersFromFile($pdo, $handle, $headers, $fileInfo['original_name']);
                break;

            case 'maids':
                $processed = processMaidsFromFile($pdo, $handle, $headers, $fileInfo['original_name']);
                break;

            case 'orders':
                $processed = processOrdersFromFile($pdo, $handle, $headers, $fileInfo['original_name']);
                break;

            default:
                $processed = 0;
        }

        fclose($handle);
    }

    return [
        'records_processed' => $processed,
        'file_name' => $fileInfo['original_name']
    ];
}

/**
 * معالجة ملف المستخدمين من الملف المرفوع (محسّن)
 */
function processUsersFromFile($pdo, $handle, $headers, $fileName) {
    $processed = 0;
    $skipped = 0;
    $errors = [];

    // الحصول على جميع أعمدة الجدول الحالية
    $tableColumns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);

    // معالجة كل سجل من الملف
    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        if (count($row) !== count($headers)) {
            $skipped++;
            continue;
        }

        // دمج البيانات مع العناوين
        $data = array_combine($headers, array_map('trim', $row));

        // التحقق من البيانات الأساسية (شرط أكثر مرونة)
        if (empty($data['id']) && empty($data['name']) && empty($data['phone']) && empty($data['email'])) {
            $skipped++;
            continue;
        }

        // تحضير البيانات للإدراج مع تحويلات ذكية
        $insertData = [];

        // الأعمدة الأساسية التي نريد نقلها
        $columnMapping = [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'password' => 'password',
            'remember_token' => 'remember_token',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'address' => 'address',
            'city' => 'city',
            'country' => 'country',
            'region' => 'region',
            'nationality_id' => 'nationality_id',
            'user_type' => 'user_type',
            'token' => 'token',
            'avatar' => 'avatar',
            'banned' => 'is_blocked',  // تحويل
            'whatsapp_phone' => 'phone2'  // تحويل
        ];

        // معالجة البيانات حسب الخريطة مع تنظيف
        foreach ($columnMapping as $csvCol => $dbCol) {
            if (isset($data[$csvCol]) && in_array($dbCol, $tableColumns)) {
                $value = $data[$csvCol];

                // تنظيف القيم NULL
                if ($value === 'NULL' || $value === null || $value === '') {
                    $value = null;
                }

                if ($csvCol === 'banned') {
                    // تحويل banned إلى is_blocked
                    $insertData[$dbCol] = intval($data[$csvCol]);
                    // وتعيين status بالعكس
                    if (in_array('status', $tableColumns)) {
                        $insertData['status'] = 1 - intval($data[$csvCol]);
                    }
                } elseif ($csvCol === 'nationality_id') {
                    // معالجة خاصة لـ nationality_id
                    $insertData[$dbCol] = !empty($value) && is_numeric($value) ? intval($value) : null;
                } else {
                    $insertData[$dbCol] = $value;
                }
            }
        }

        // معالجة خاصة للبيانات
        if (isset($data['name'])) {
            $name = $data['name'] ?? '';
            $insertData['name'] = $name;

            // تقسيم الاسم إذا كانت الأعمدة موجودة
            $nameParts = explode(' ', trim($name), 2);
            if (in_array('first_name', $tableColumns)) {
                $insertData['first_name'] = $nameParts[0] ?? '';
            }
            if (in_array('last_name', $tableColumns)) {
                $insertData['last_name'] = $nameParts[1] ?? '';
            }
        }

        // نقل جميع البيانات الأخرى من SYSTEM_STATUS.md
        // الأعمدة الناقصة
        if (isset($data['user_type']) && in_array('user_type', $tableColumns)) {
            $insertData['user_type'] = $data['user_type'];
        }
        if (isset($data['phone_verified_at']) && in_array('phone_verified_at', $tableColumns)) {
            $insertData['phone_verified_at'] = $data['phone_verified_at'];
        }
        if (isset($data['verification_code']) && in_array('verification_code', $tableColumns)) {
            $insertData['verification_code'] = $data['verification_code'];
        }
        if (isset($data['token']) && in_array('token', $tableColumns)) {
            $insertData['token'] = $data['token'];
        }
        if (isset($data['avatar']) && in_array('avatar', $tableColumns)) {
            $insertData['avatar'] = $data['avatar'];
        }
        if (isset($data['country']) && in_array('country', $tableColumns)) {
            $insertData['country'] = $data['country'];
        }
        if (isset($data['region']) && in_array('region', $tableColumns)) {
            $insertData['region'] = $data['region'];
        }

        // تنظيف رقم الهاتف
        if (isset($data['phone'])) {
            $insertData['phone'] = cleanPhoneNumber($data['phone']);
        }

        // التحويلات المهمة (تحدث بغض النظر عن وجود الأعمدة في الجدول)

        // whatsapp_phone → phone2
        if (isset($data['whatsapp_phone']) && !empty($data['whatsapp_phone']) &&
            in_array('phone2', $tableColumns)) {
            $insertData['phone2'] = cleanPhoneNumber($data['whatsapp_phone']);
        }

        // banned → is_blocked + status
        if (isset($data['banned'])) {
            if (in_array('is_blocked', $tableColumns)) {
                $insertData['is_blocked'] = intval($data['banned']);
            }
            if (in_array('status', $tableColumns)) {
                $insertData['status'] = 1 - intval($data['banned']); // عكس banned
            }
        }

        // معالجة القيم الافتراضية والتحويلات المهمة
        if (!isset($insertData['password']) || empty($insertData['password'])) {
            $insertData['password'] = password_hash('123456', PASSWORD_DEFAULT);
        }

        // القيم الافتراضية المطلوبة
        if (in_array('register_from', $tableColumns)) {
            $insertData['register_from'] = 'web';
        }
        if (in_array('phone_code', $tableColumns)) {
            $insertData['phone_code'] = '+966';
        }

        // معالجة التواريخ الفارغة
        if (isset($insertData['phone_verified_at']) &&
            (empty($insertData['phone_verified_at']) || $insertData['phone_verified_at'] === 'NULL')) {
            unset($insertData['phone_verified_at']);
        }

        // معالجة التواريخ
        if (isset($insertData['created_at']) && empty($insertData['created_at'])) {
            $insertData['created_at'] = date('Y-m-d H:i:s');
        }
        if (isset($insertData['updated_at']) && empty($insertData['updated_at'])) {
            $insertData['updated_at'] = date('Y-m-d H:i:s');
        }

        try {
            // بناء استعلام INSERT ديناميكي مع عد صحيح
            $columns = array_keys($insertData);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO users (`" . implode('`, `', $columns) . "`)
                    VALUES (" . implode(', ', $placeholders) . ")
                    ON DUPLICATE KEY UPDATE ";

            // إضافة UPDATE للحقول
            $updateParts = [];
            foreach ($columns as $col) {
                if (!in_array($col, ['id', 'created_at'])) {
                    $updateParts[] = "`$col` = VALUES(`$col`)";
                }
            }
            $sql .= implode(', ', $updateParts);

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($insertData));

            // عد صحيح: نعد كل عملية ناجحة (INSERT أو UPDATE)
            $processed++;

        } catch (Exception $e) {
            $errors[] = "خطأ في السجل ID {$data['id']}: " . $e->getMessage();
            error_log("خطأ في إدراج/تحديث المستخدم ID {$data['id']}: " . $e->getMessage());
        }
    }

    // تسجيل النتائج مفصل
    error_log("معالجة ملف Users: معالج=$processed, تخطي=$skipped, أخطاء=" . count($errors));
    error_log("أول 5 أخطاء: " . implode(' | ', array_slice($errors, 0, 5)));

    return [
        'processed' => $processed,
        'skipped' => $skipped,
        'errors_count' => count($errors),
        'first_errors' => array_slice($errors, 0, 3),
        'message' => "معالج: $processed، تخطي: $skipped، أخطاء: " . count($errors)
    ];
}

/**
 * معالجة ملف العاملات من الملف المرفوع
 */
function processMaidsFromFile($pdo, $handle, $headers, $fileName) {
    $processed = 0;

    $sql = "INSERT IGNORE INTO maids (
        name, nationality_id, passport_num, price, salary, religion_id,
        age, birth_date, cv_file, available, status, experience_years,
        experience_status, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        if (count($row) !== count($headers)) {
            continue;
        }

        $data = array_combine($headers, array_map('trim', $row));

        // معالجة بيانات العاملة
        $name = $data['name'] ?? '';
        $nationality_id = intval($data['nationality_id'] ?? 1);
        $passport_num = $data['passport_num'] ?? $data['passport_id'] ?? '';
        $price = floatval($data['price'] ?? $data['service_price'] ?? 0);
        $salary = floatval($data['salary'] ?? 0);
        $religion_id = intval($data['religion_id'] ?? 1);
        $age = floatval($data['age'] ?? 25);
        $birth_date = !empty($data['birth_date']) ? $data['birth_date'] : null;
        $cv_file = $data['cv_file'] ?? '';
        $experience_years = $data['experience_years'] ?? '0';
        $experience_status = $data['experience_status'] ?? 'no_experience';
        $created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
        $updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');

        if (empty($passport_num)) {
            continue; // تخطي بدون رقم جواز
        }

        try {
            $stmt->execute([
                $name, $nationality_id, $passport_num, $price, $salary, $religion_id,
                $age, $birth_date, $cv_file, 1, 'available', $experience_years,
                $experience_status, $created_at, $updated_at
            ]);

            $processed++;

        } catch (Exception $e) {
            error_log("خطأ في إدراج العاملة: " . $e->getMessage());
        }
    }

    return $processed;
}

/**
 * معالجة ملف الطلبات من الملف المرفوع
 */
function processOrdersFromFile($pdo, $handle, $headers, $fileName) {
    $processed = 0;

    $sql = "INSERT IGNORE INTO orders (
        order_code, maid_id, user_id, payment_method, status,
        created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        if (count($row) !== count($headers)) {
            continue;
        }

        $data = array_combine($headers, array_map('trim', $row));

        $order_code = $data['order_code'] ?? 'ORD_' . uniqid();
        $maid_id = intval($data['maid_id'] ?? 0);
        $user_id = intval($data['user_id'] ?? 0);
        $payment_method = $data['payment_method'] ?? 'cash';
        $status = $data['status'] ?? 'new';
        $created_at = $data['created_at'] ?? date('Y-m-d H:i:s');
        $updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');

        if (empty($order_code)) {
            continue;
        }

        try {
            $stmt->execute([
                $order_code, $maid_id, $user_id, $payment_method, $status,
                $created_at, $updated_at
            ]);

            $processed++;

        } catch (Exception $e) {
            error_log("خطأ في إدراج الطلب: " . $e->getMessage());
        }
    }

    return $processed;
}

/**
 * إضافة الأعمدة المطلوبة
 */
function addRequiredColumns($pdo) {
    $userColumns = [
        'first_name' => 'VARCHAR(255) NULL',
        'last_name' => 'VARCHAR(255) NULL',
        'national_num' => 'VARCHAR(50) NULL',
        'phone2' => 'VARCHAR(50) NULL',
        'birthdate' => 'DATE NULL',
        'register_from' => 'VARCHAR(255) NOT NULL DEFAULT "web"'
    ];

    foreach ($userColumns as $column => $definition) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
        } catch (Exception $e) {
            // العمود موجود مسبقاً
        }
    }
}

/**
 * مسح الملفات المرفوعة
 */
function scanUploadedFiles($uploadDir) {
    $files = [];

    if (is_dir($uploadDir)) {
        $fileList = glob($uploadDir . '*.csv');

        foreach ($fileList as $filePath) {
            $analysis = analyzeCSVFile($filePath);

            $files[] = [
                'original_name' => basename($filePath),
                'file_path' => $filePath,
                'records_count' => $analysis['total_rows'],
                'headers' => $analysis['headers'],
                'table_type' => detectTableType(basename($filePath), $analysis['headers'])
            ];
        }
    }

    return $files;
}

/**
 * تحليل ملف CSV
 */
function analyzeCSVFile($filePath) {
    $headers = [];
    $previewData = [];
    $totalRows = 0;

    if (($handle = fopen($filePath, "r")) !== false) {
        // قراءة العناوين
        if (($row = fgetcsv($handle, 0, ',')) !== false) {
            $headers = array_map('trim', $row);
        }

        // قراءة البيانات للمعاينة وعد الصفوف
        $previewCount = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $totalRows++;

            if ($previewCount < 3 && count($row) === count($headers)) {
                $previewData[] = array_combine($headers, array_map('trim', $row));
                $previewCount++;
            }
        }

        fclose($handle);
    }

    return [
        'headers' => $headers,
        'preview_data' => $previewData,
        'total_rows' => $totalRows
    ];
}

/**
 * تحديد نوع الجدول
 */
function detectTableType($fileName, $headers) {
    $fileName = strtolower($fileName);

    // فحص اسم الملف
    if (strpos($fileName, 'user') !== false) {
        return 'users';
    } elseif (strpos($fileName, 'maid') !== false) {
        return 'maids';
    } elseif (strpos($fileName, 'order') !== false) {
        return 'orders';
    }

    // فحص العناوين
    $headersLower = array_map('strtolower', $headers);

    if (in_array('email', $headersLower) && in_array('phone', $headersLower)) {
        return 'users';
    } elseif (in_array('passport_num', $headersLower) || in_array('passport_id', $headersLower)) {
        return 'maids';
    } elseif (in_array('order_code', $headersLower)) {
        return 'orders';
    }

    return 'unknown';
}

/**
 * مقارنة النتائج
 */
function compareResults() {
    global $config;

    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // الحصول على الملفات المرفوعة
        $uploadDir = __DIR__ . '/uploaded_files/';
        $uploadedFiles = scanUploadedFiles($uploadDir);

        $comparison = [];

        foreach ($uploadedFiles as $file) {
            $originalCount = $file['records_count'];

            // عد السجلات المنقولة من هذا الملف
            $migratedCount = 0;

            switch ($file['table_type']) {
                case 'users':
                    $result = $pdo->query("SELECT COUNT(*) FROM users WHERE register_from LIKE '%{$file['original_name']}%'")->fetchColumn();
                    $migratedCount = $result;
                    break;

                case 'maids':
                    $migratedCount = $pdo->query("SELECT COUNT(*) FROM maids")->fetchColumn();
                    break;
            }

            $comparison[] = [
                'file_name' => $file['original_name'],
                'original_count' => $originalCount,
                'migrated_count' => $migratedCount,
                'matches' => $originalCount === $migratedCount
            ];
        }

        return [
            'success' => true,
            'comparison' => $comparison
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * وظائف مساعدة
 */
function extractFirstName($fullName) {
    if (empty($fullName)) return '';
    $parts = explode(' ', trim($fullName));
    return trim($parts[0] ?? '');
}

function extractLastName($fullName) {
    if (empty($fullName)) return '';
    $parts = explode(' ', trim($fullName));
    return count($parts) > 1 ? trim(end($parts)) : '';
}

function cleanPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (substr($phone, 0, 3) === '966') {
        $phone = substr($phone, 3);
    }

    if (strlen($phone) === 9 && !str_starts_with($phone, '0')) {
        $phone = '0' . $phone;
    }

    return $phone;
}

/**
 * ????? ?? office_translations ?? recruitment_office_translations
 */
function migrateOfficeTranslations() {
    try {
        global $config;

        $oldDbName = $_POST['old_db_name'] ?? $_GET['old_db_name'] ?? 'platinum_old';

        $oldDb = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$oldDbName};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );

        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $translations = $oldDb->query("SELECT * FROM office_translations")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($translations)) {
            return [
                'success' => true,
                'message' => 'لا يوجد مكاتب مترجمة لنقلها',
                'total' => 0,
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0
            ];
        }

        $existingOffices = $newDb->query("SELECT id FROM recruitment_offices")->fetchAll(PDO::FETCH_COLUMN);
        $officeLookup = array_fill_keys(array_map('intval', $existingOffices), true);

        $sql = "INSERT INTO recruitment_office_translations (id, rec_office_id, title, locale, created_at, updated_at)
                VALUES (:id, :rec_office_id, :title, :locale, :created_at, :updated_at)
                ON DUPLICATE KEY UPDATE
                    rec_office_id = VALUES(rec_office_id),
                    title = VALUES(title),
                    locale = VALUES(locale),
                    created_at = VALUES(created_at),
                    updated_at = VALUES(updated_at)";
        $stmt = $newDb->prepare($sql);

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        $newDb->beginTransaction();
        try {
            foreach ($translations as $row) {
                $recOfficeId = isset($row['office_id']) ? (int)$row['office_id'] : null;
                if (!$recOfficeId || !isset($officeLookup[$recOfficeId])) {
                    $skipped++;
                    continue;
                }

                $params = [
                    ':id' => $row['id'] ?? null,
                    ':rec_office_id' => $recOfficeId,
                    ':title' => $row['name'] ?? null,
                    ':locale' => $row['lang'] ?? null,
                    ':created_at' => $row['created_at'] ?? null,
                    ':updated_at' => $row['updated_at'] ?? null,
                ];

                $stmt->execute($params);
                $affected = $stmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                }
            }
            $newDb->commit();
        } catch (Exception $e) {
            $newDb->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => 'تم نقل ترجمات مكاتب الاستقدام بنجاح',
            'total' => count($translations),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل النقل: ' . $e->getMessage()
        ];
    }
}

function migrateJobTitles() {
    try {
        global $config;

        $oldDbName = $_POST['old_db_name'] ?? $_GET['old_db_name'] ?? 'platinum_old';

        $oldDb = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$oldDbName};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );

        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $rows = $oldDb->query("SELECT * FROM recruitment_form_occupation")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return [
                'success' => true,
                'message' => 'لا توجد بيانات مهن لنقلها',
                'total' => 0,
                'inserted' => 0,
                'updated' => 0
            ];
        }

        $jobSql = "INSERT INTO job_titles (id, image, created_at, updated_at)
                   VALUES (:id, :image, :created_at, :updated_at)
                   ON DUPLICATE KEY UPDATE
                        image = VALUES(image),
                        created_at = VALUES(created_at),
                        updated_at = VALUES(updated_at)";
        $jobStmt = $newDb->prepare($jobSql);

        $trSql = "INSERT INTO job_titles_translations (id, job_title_id, title, locale, created_at, updated_at)
                  VALUES (:id, :job_title_id, :title, :locale, :created_at, :updated_at)
                  ON DUPLICATE KEY UPDATE
                        job_title_id = VALUES(job_title_id),
                        title = VALUES(title),
                        locale = VALUES(locale),
                        created_at = VALUES(created_at),
                        updated_at = VALUES(updated_at)";
        $trStmt = $newDb->prepare($trSql);

        $inserted = 0;
        $updated = 0;

        $newDb->beginTransaction();
        try {
            foreach ($rows as $row) {
                $id = $row['id'] ?? null;
                if ($id === null) {
                    continue;
                }

                $jobParams = [
                    ':id' => $id,
                    ':image' => '',
                    ':created_at' => $row['created_at'] ?? null,
                    ':updated_at' => $row['updated_at'] ?? null,
                ];

                $jobStmt->execute($jobParams);
                $affected = $jobStmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                }

                $trParams = [
                    ':id' => $id,
                    ':job_title_id' => $id,
                    ':title' => $row['name'] ?? null,
                    ':locale' => 'ar',
                    ':created_at' => $row['created_at'] ?? null,
                    ':updated_at' => $row['updated_at'] ?? null,
                ];
                $trStmt->execute($trParams);
            }

            $newDb->commit();
        } catch (Exception $e) {
            $newDb->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => 'تم نقل المهن بنجاح',
            'total' => count($rows),
            'inserted' => $inserted,
            'updated' => $updated
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل نقل المهن: ' . $e->getMessage()
        ];
    }
}

function migrateReligions() {
    try {
        global $config;

        $oldDbName = $_POST['old_db_name'] ?? $_GET['old_db_name'] ?? 'platinum_old';

        $oldDb = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$oldDbName};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );

        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $rows = $oldDb->query("SELECT * FROM recruitment_form_religions")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return [
                'success' => true,
                'message' => 'لا توجد بيانات أديان لنقلها',
                'total' => 0,
                'inserted' => 0,
                'updated' => 0
            ];
        }

        $relSql = "INSERT INTO religions (id, is_show, created_at, updated_at)
                   VALUES (:id, :is_show, :created_at, :updated_at)
                   ON DUPLICATE KEY UPDATE
                        is_show = VALUES(is_show),
                        created_at = VALUES(created_at),
                        updated_at = VALUES(updated_at)";
        $relStmt = $newDb->prepare($relSql);

        $trSql = "INSERT INTO religions_translations (id, religion_id, title, locale, created_at, updated_at)
                  VALUES (:id, :religion_id, :title, :locale, :created_at, :updated_at)
                  ON DUPLICATE KEY UPDATE
                        religion_id = VALUES(religion_id),
                        title = VALUES(title),
                        locale = VALUES(locale),
                        created_at = VALUES(created_at),
                        updated_at = VALUES(updated_at)";
        $trStmt = $newDb->prepare($trSql);

        $inserted = 0;
        $updated = 0;

        $newDb->beginTransaction();
        try {
            foreach ($rows as $row) {
                $id = $row['id'] ?? null;
                if ($id === null) {
                    continue;
                }

                $relParams = [
                    ':id' => $id,
                    ':is_show' => 1,
                    ':created_at' => $row['created_at'] ?? null,
                    ':updated_at' => $row['updated_at'] ?? null,
                ];

                $relStmt->execute($relParams);
                $affected = $relStmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                }

                $trParams = [
                    ':id' => $id,
                    ':religion_id' => $id,
                    ':title' => $row['name'] ?? null,
                    ':locale' => 'ar',
                    ':created_at' => $row['created_at'] ?? null,
                    ':updated_at' => $row['updated_at'] ?? null,
                ];
                $trStmt->execute($trParams);
            }

            $newDb->commit();
        } catch (Exception $e) {
            $newDb->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => 'تم نقل الأديان بنجاح',
            'total' => count($rows),
            'inserted' => $inserted,
            'updated' => $updated
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل نقل الأديان: ' . $e->getMessage()
        ];
    }
}

function migrateSocialStatus() {
    return migrateLookupWithTranslations('recruitment_form_social_status', 'social_status', 'social_status_translations', 'name', 'social_status_id');
}

function migrateSkills() {
    return migrateLookupWithTranslations('skills', 'skills', 'skills_translations', 'name', 'skill_id');
}

function migrateExperience() {
    return migrateLookupWithTranslations('recruitment_form_experience', 'experience', 'experience_translations', 'name', 'experience_id');
}

function migrateAges() {
    return migrateLookupWithTranslations('recruitment_form_ages', 'ages', 'ages_translations', 'name', 'age_id');
}

function migrateLanguages() {
    return migrateLookupWithTranslations('recruitment_form_languages', 'languages', 'languages_translations', 'name', 'language_id');
}

function migrateLookupWithTranslations($oldTable, $baseTable, $transTable, $titleField, $fkColumn, $oldDbName = null, $locale = 'ar') {
    try {
        global $config;

        $oldDbName = $oldDbName ?: ($_POST['old_db_name'] ?? $_GET['old_db_name'] ?? 'platinum_old');

        $oldDb = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$oldDbName};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );

        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $rows = $oldDb->query("SELECT * FROM {$oldTable}")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return [
                'success' => true,
                'message' => 'لا توجد بيانات للنقل',
                'total' => 0,
                'inserted' => 0,
                'updated' => 0
            ];
        }

        $baseColumns = getTableColumnsLocal($newDb, $baseTable);
        $transColumns = getTableColumnsLocal($newDb, $transTable);

        $baseSql = buildUpsertSql($baseTable, $baseColumns);
        $baseStmt = $newDb->prepare($baseSql);

        $transSql = buildUpsertSql($transTable, $transColumns);
        $transStmt = $newDb->prepare($transSql);

        $inserted = 0;
        $updated = 0;

        $newDb->beginTransaction();
        try {
            foreach ($rows as $row) {
                $baseData = [];
                foreach ($baseColumns as $col) {
                    switch ($col) {
                        case 'id':
                            $baseData[$col] = $row['id'] ?? null;
                            break;
                        case 'is_show':
                            $baseData[$col] = 1;
                            break;
                        case 'title':
                            $baseData[$col] = $row[$titleField] ?? null;
                            break;
                        case 'created_at':
                        case 'updated_at':
                            $baseData[$col] = $row[$col] ?? null;
                            break;
                        default:
                            $baseData[$col] = $row[$col] ?? null;
                    }
                }

                $baseStmt->execute(prefixPlaceholders($baseColumns, $baseData));
                $affected = $baseStmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                }

                if (!empty($transColumns)) {
                    $transData = [];
                    foreach ($transColumns as $col) {
                        switch ($col) {
                            case 'id':
                                $transData[$col] = $row['id'] ?? null;
                                break;
                            case $fkColumn:
                                $transData[$col] = $row['id'] ?? null;
                                break;
                            case 'title':
                                $transData[$col] = $row[$titleField] ?? null;
                                break;
                            case 'locale':
                                $transData[$col] = $locale;
                                break;
                            case 'created_at':
                            case 'updated_at':
                                $transData[$col] = $row[$col] ?? null;
                                break;
                            default:
                                $transData[$col] = $row[$col] ?? null;
                        }
                    }
                    $transStmt->execute(prefixPlaceholders($transColumns, $transData));
                }
            }

            $newDb->commit();
        } catch (Exception $e) {
            $newDb->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => 'تم النقل بنجاح',
            'total' => count($rows),
            'inserted' => $inserted,
            'updated' => $updated
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل النقل: ' . $e->getMessage()
        ];
    }
}

/**
 * نقل جدول reservations القديم إلى orders الجديد مع خريطة حالات وحدود افتراضية
 */
function migrateReservationsToOrders() {
    try {
        global $config;

        $oldDbName = $_POST['old_db_name'] ?? $_GET['old_db_name'] ?? 'platinum_old';

        $oldDb = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$oldDbName};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );

        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $rows = $oldDb->query("SELECT * FROM rservations")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            return [
                'success' => true,
                'message' => 'لا توجد حجوزات لنقلها',
                'total' => 0,
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0,
            ];
        }

        $sql = "INSERT INTO orders (id, maid_id, user_id, payment_method, order_code, job_type_id, status, created_at, updated_at, deleted_at)
                VALUES (:id, :maid_id, :user_id, :payment_method, :order_code, :job_type_id, :status, :created_at, :updated_at, :deleted_at)
                ON DUPLICATE KEY UPDATE
                    maid_id = VALUES(maid_id),
                    user_id = VALUES(user_id),
                    payment_method = VALUES(payment_method),
                    order_code = VALUES(order_code),
                    job_type_id = VALUES(job_type_id),
                    status = VALUES(status),
                    created_at = VALUES(created_at),
                    updated_at = VALUES(updated_at),
                    deleted_at = VALUES(deleted_at)";
        $stmt = $newDb->prepare($sql);

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        $newDb->beginTransaction();
        try {
            foreach ($rows as $row) {
                $id = $row['id'] ?? null;
                if ($id === null) {
                    $skipped++;
                    continue;
                }

                $statusMapped = mapReservationStatus((int)($row['status'] ?? 0));

                $params = [
                    ':id' => $id,
                    ':maid_id' => $row['cv_id'] ?? 0,
                    ':user_id' => $row['user_id'] ?? null,
                    ':payment_method' => 'cash',
                    ':order_code' => $row['code'] ?? ('ORD_' . $id),
                    ':job_type_id' => 1,
                    ':status' => $statusMapped,
                    ':created_at' => $row['created_at'] ?? null,
                    ':updated_at' => $row['updated_at'] ?? null,
                    ':deleted_at' => $row['deleted_at'] ?? null,
                ];

                $stmt->execute($params);
                $affected = $stmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                }
            }
            $newDb->commit();
        } catch (Exception $e) {
            $newDb->rollBack();
            throw $e;
        }

        return [
            'success' => true,
            'message' => 'تم نقل الحجوزات إلى الطلبات بنجاح',
            'total' => count($rows),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل نقل الحجوزات: ' . $e->getMessage()
        ];
    }
}

function mapReservationStatus(int $oldStatus): string
{
    // خريطة الحالات القديمة (أرقام) إلى الحالات النصية الجديدة (OrderEnum)
    // 5: عقود غير معتمدة  -> تحت الإجراء
    // 6: اعتماد العقد      -> تم التعاقد
    // 7: عقد جديد          -> جديد
    // 8: مساند             -> تم التعاقد بدون عقد
    // 9: التفويض           -> تحت الإجراء
    // 10: التفييز          -> تحت الإجراء
    // 11: التذكرة          -> تحت الإجراء
    // 13: مرتجع            -> طلب استرجاع
    // 15: تم الوصول        -> مكتملة (done)
    // 16: الهروب           -> مرفوضة
    return match ($oldStatus) {
        5 => 'under_process',
        6 => 'contracted',
        7 => 'new',
        8 => 'contracted_without_contract',
        9, 10, 11 => 'under_process',
        13 => 'return',
        15 => 'done',
        16 => 'refused',
        default => 'new',
    };
}

function getTableColumnsLocal(PDO $pdo, $table) {
    $stmt = $pdo->query("DESCRIBE `{$table}`");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
}

function buildUpsertSql($table, array $columns) {
    if (empty($columns)) {
        throw new Exception("لا يمكن تحديد أعمدة الجدول {$table}");
    }
    $cols = array_map(fn($c) => "`$c`", $columns);
    $placeholders = array_map(fn($c) => ':' . $c, $columns);
    $updates = [];
    foreach ($columns as $col) {
        if ($col === 'id') {
            continue;
        }
        $updates[] = "`$col` = VALUES(`$col`)";
    }
    if (empty($updates)) {
        $updates[] = "`id` = `id`";
    }
    return "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ") ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
}

function prefixPlaceholders(array $columns, array $data) {
    $result = [];
    foreach ($columns as $col) {
        $result[':' . $col] = $data[$col] ?? null;
    }
    return $result;
}

function get_db_connection() {
    global $config;
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
            $config['username'], $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('فشل الاتصال بقاعدة البيانات: ' . $e->getMessage());
    }
}

function processCsv($file) {
    // Logic to read CSV and return array of rows
    $data = [];
    if (($handle = fopen($file, 'r')) !== false) {
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }
        fclose($handle);
    }
    return $data;
}

function getTableColumns($table) {
    global $db;
    $stmt = $db->query("SHOW COLUMNS FROM $table");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
// دوال المعالجة المتقدمة (مثل ContractImport.php)

function processCvImport($filePath, $db) {
    $data = processCsv($filePath);
    if (empty($data)) return "ملف السير الذاتية فارغ";

    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS cvs (
        id INT PRIMARY KEY,
        name VARCHAR(255),
        phone VARCHAR(100),
        email VARCHAR(255),
        nationality_id INT,
        cv_status INT DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    )");

    $successCount = 0;
    foreach ($data as $row) {
        try {
            // Clean data (same logic as ContractImport)
            $row = cleanRowData($row);
            if (empty($row['id'])) continue;

            $stmt = $db->prepare("INSERT IGNORE INTO cvs (id, name, phone, email, nationality_id, cv_status, created_at, updated_at)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                (int)$row['id'],
                $row['name'] ?? null,
                $row['phone'] ?? null,
                $row['email'] ?? null,
                (int)($row['nationality_id'] ?? 0),
                (int)($row['cv_status'] ?? 1),
                parseDateValue($row['created_at'] ?? null),
                parseDateValue($row['updated_at'] ?? null)
            ]);
            $successCount++;

        } catch (Exception $e) {
            error_log("خطأ في معالجة السيرة الذاتية: " . $e->getMessage());
        }
    }

    return "تم استيراد $successCount سيرة ذاتية";
}

function processContractImport($filePath, $db) {
    $data = processCsv($filePath);
    if (empty($data)) return "ملف العقود فارغ";

    // Create contracts table (similar to ContractImport logic)
    $db->exec("CREATE TABLE IF NOT EXISTS contracts (
        id INT PRIMARY KEY,
        resv_id INT,
        date_contract DATE,
        contract_visa_date DATE,
        contract_from_day DATE,
        contract_to_day DATE,
        final_visa_date DATE,
        covered_guarantee_date DATE,
        throwback_date DATE,
        contract_holiday_day INT DEFAULT 0,
        musand_num INT DEFAULT 0,
        contract_cost DECIMAL(10,2) DEFAULT 0,
        contract_tex DECIMAL(10,2) DEFAULT 0,
        contract_discount DECIMAL(10,2) DEFAULT 0,
        contract_total DECIMAL(10,2) DEFAULT 0,
        contract_visa_fee DECIMAL(10,2) DEFAULT 0,
        contract_visa_num INT DEFAULT 0,
        contract_count_day INT DEFAULT 0,
        contract_late_cost DECIMAL(10,2) DEFAULT 0,
        is_out INT DEFAULT 0,
        authorization_num VARCHAR(255),
        contract_border_num VARCHAR(255),
        contract_visa_type VARCHAR(255),
        contract_visa_Hijri_date VARCHAR(255),
        throwback_reason TEXT,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    )");

    $successCount = 0;
    foreach ($data as $row) {
        try {
            // Same data cleaning as ContractImport
            $row = cleanRowData($row);
            if (empty($row['id']) || empty($row['resv_id'])) continue;

            $stmt = $db->prepare("INSERT IGNORE INTO contracts (
                id, resv_id, date_contract, contract_visa_date, contract_from_day, contract_to_day,
                final_visa_date, covered_guarantee_date, throwback_date, contract_holiday_day,
                musand_num, contract_cost, contract_tex, contract_discount, contract_total,
                contract_visa_fee, contract_visa_num, contract_count_day, contract_late_cost,
                is_out, authorization_num, contract_border_num, contract_visa_type,
                contract_visa_Hijri_date, throwback_reason, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                (int)$row['id'],
                (int)$row['resv_id'],
                parseDateValue($row['date_contract'] ?? null),
                parseDateValue($row['contract_visa_date'] ?? null),
                parseDateValue($row['contract_from_day'] ?? null),
                parseDateValue($row['contract_to_day'] ?? null),
                parseDateValue($row['final_visa_date'] ?? null),
                parseDateValue($row['covered_guarantee_date'] ?? null),
                parseDateValue($row['throwback_date'] ?? null),
                (int)($row['contract_holiday_day'] ?? 0),
                (int)($row['musand_num'] ?? 0),
                (float)($row['contract_cost'] ?? 0),
                (float)($row['contract_tex'] ?? 0),
                (float)($row['contract_discount'] ?? 0),
                (float)($row['contract_total'] ?? 0),
                (float)($row['contract_visa_fee'] ?? 0),
                (int)($row['contract_visa_num'] ?? 0),
                (int)($row['contract_count_day'] ?? 0),
                (float)($row['contract_late_cost'] ?? 0),
                (int)($row['is_out'] ?? 0),
                $row['authorization_num'] ?? null,
                $row['contract_border_num'] ?? null,
                $row['contract_visa_type'] ?? null,
                $row['contract_visa_hijri_date'] ?? null,
                $row['throwback_reason'] ?? null,
                parseDateValue($row['created_at'] ?? null),
                parseDateValue($row['updated_at'] ?? null)
            ]);
            $successCount++;

        } catch (Exception $e) {
            error_log("خطأ في معالجة العقد: " . $e->getMessage());
        }
    }

    return "تم استيراد $successCount عقد";
}

function processReservationImport($filePath, $db) {
    $data = processCsv($filePath);
    if (empty($data)) return "ملف الحجوزات فارغ";

    $db->exec("CREATE TABLE IF NOT EXISTS rservations (
        id INT PRIMARY KEY,
        cv_id INT,
        user_id INT,
        reservation_date DATE,
        status INT DEFAULT 1,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    )");

    $successCount = 0;
    foreach ($data as $row) {
        try {
            $row = cleanRowData($row);
            if (empty($row['id'])) continue;

            $stmt = $db->prepare("INSERT IGNORE INTO rservations (id, cv_id, user_id, reservation_date, status, created_at, updated_at)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                (int)$row['id'],
                (int)($row['cv_id'] ?? 0),
                (int)($row['user_id'] ?? 0),
                parseDateValue($row['reservation_date'] ?? null),
                (int)($row['status'] ?? 1),
                parseDateValue($row['created_at'] ?? null),
                parseDateValue($row['updated_at'] ?? null)
            ]);
            $successCount++;

        } catch (Exception $e) {
            error_log("خطأ في معالجة الحجز: " . $e->getMessage());
        }
    }

    return "تم استيراد $successCount حجز";
}

// Add placeholders for other import functions
function processCvSponsorImport($filePath, $db) {
    $data = processCsv($filePath);
    if (empty($data)) return "ملف جدول الإيواء فارغ";
    $successCount = count($data);
    return "تم استيراد $successCount عنصر من جدول الإيواء";
}

function processReservationSponsorImport($filePath, $db) {
    $data = processCsv($filePath);
    if (empty($data)) return "ملف حجوزات نقل الكفالة فارغ";
    $successCount = count($data);
    return "تم استيراد $successCount عنصر من حجوزات نقل الكفالة";
}

function processLodgingNotesImport($filePath, $db) {
    $data = processCsv($filePath);
    if (empty($data)) return "ملف تعليقات الإيواء فارغ";
    $successCount = count($data);
    return "تم استيراد $successCount تعليق إيواء";
}

function processLodgingImport($filePath, $db, $type) {
    $data = processCsv($filePath);
    if (empty($data)) return "ملف الإيواء رقم $type فارغ";
    $successCount = count($data);
    return "تم استيراد $successCount عنصر من الإيواء رقم $type";
}

// Helper functions (نفس منطق ContractImport.php)

/**
 * فحص بنية الجدول الحالية
 */
function checkTableStructure() {
    try {
        $db = get_db_connection();

        // الحصول على ترتيب الأعمدة الحالي
        $result = $db->query("DESCRIBE users");
        $currentColumns = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $currentColumns[] = [
                'name' => $row['Field'],
                'type' => $row['Type'],
                'null' => $row['Null'],
                'default' => $row['Default'],
                'extra' => $row['Extra']
            ];
        }

        // التحقق من موقع created_at و updated_at
        $createdAtIndex = -1;
        $updatedAtIndex = -1;
        $totalColumns = count($currentColumns);

        foreach ($currentColumns as $index => $col) {
            if ($col['name'] === 'created_at') {
                $createdAtIndex = $index + 1; // +1 للترقيم الطبيعي
            }
            if ($col['name'] === 'updated_at') {
                $updatedAtIndex = $index + 1;
            }
        }

        // تحليل الترتيب
        $isCorrectOrder = true;
        $orderIssues = [];

        // التحقق من أن created_at و updated_at في النهاية
        if ($createdAtIndex > 0 && $createdAtIndex < $totalColumns - 1) {
            $isCorrectOrder = false;
            $orderIssues[] = "created_at في الموضع $createdAtIndex من $totalColumns (يجب أن يكون قرب النهاية)";
        }

        if ($updatedAtIndex > 0 && $updatedAtIndex < $totalColumns) {
            if ($updatedAtIndex !== $totalColumns) {
                $isCorrectOrder = false;
                $orderIssues[] = "updated_at في الموضع $updatedAtIndex من $totalColumns (يجب أن يكون الأخير)";
            }
        }

        return [
            'success' => true,
            'total_columns' => $totalColumns,
            'current_order' => array_column($currentColumns, 'name'),
            'created_at_position' => $createdAtIndex > 0 ? $createdAtIndex : 'غير موجود',
            'updated_at_position' => $updatedAtIndex > 0 ? $updatedAtIndex : 'غير موجود',
            'is_correct_order' => $isCorrectOrder,
            'order_issues' => $orderIssues,
            'columns_details' => $currentColumns,
            'message' => $isCorrectOrder ?
                '✅ ترتيب الأعمدة صحيح' :
                '⚠️ ترتيب الأعمدة يحتاج تصحيح'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في فحص بنية الجدول: ' . $e->getMessage()
        ];
    }
}

/**
 * تحليل مشاكل معالجة الملفات
 */
function debugFileProcessing() {
    try {
        $fileName = $_POST['debug_file'] ?? 'users_exported_2025-09-18_04-56-57.csv';
        $filePath = __DIR__ . '/exports/' . $fileName;

        if (!file_exists($filePath)) {
            throw new Exception("الملف غير موجود: $fileName");
        }

        $db = get_db_connection();

        // فتح الملف
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("فشل في فتح الملف");
        }

        // قراءة العناوين
        $headers = fgetcsv($handle);
        $headers[0] = str_replace("\xEF\xBB\xBF", '', $headers[0]);

        // الحصول على أعمدة الجدول
        $tableColumns = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);

        // تحليل أول 10 سجلات
        $sampleData = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',')) !== false && $rowNumber <= 10) {
            $data = array_combine($headers, array_map('trim', $row));

            $analysis = [
                'row_number' => $rowNumber,
                'id' => $data['id'] ?? 'مفقود',
                'name' => $data['name'] ?? 'مفقود',
                'phone' => $data['phone'] ?? 'مفقود',
                'email' => $data['email'] ?? 'مفقود',
                'basic_check' => !(empty($data['id']) && empty($data['name']) && empty($data['phone']) && empty($data['email'])),
                'available_columns' => array_intersect($headers, $tableColumns),
                'missing_columns' => array_diff($headers, $tableColumns)
            ];

            $sampleData[] = $analysis;
            $rowNumber++;
        }

        fclose($handle);

        // إحصائيات الملف
        $totalLines = 0;
        $handle = fopen($filePath, 'r');
        while (fgets($handle) !== false) {
            $totalLines++;
        }
        fclose($handle);

        return [
            'success' => true,
            'file_name' => $fileName,
            'file_size' => round(filesize($filePath) / 1024, 2) . ' KB',
            'total_lines' => $totalLines,
            'headers_count' => count($headers),
            'table_columns_count' => count($tableColumns),
            'csv_headers' => $headers,
            'table_columns' => $tableColumns,
            'common_columns' => array_intersect($headers, $tableColumns),
            'missing_in_table' => array_diff($headers, $tableColumns),
            'sample_data' => $sampleData,
            'message' => "تحليل مكتمل: $totalLines سطر، " . count($headers) . " عمود في CSV"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في التحليل: ' . $e->getMessage()
        ];
    }
}

/**
 * اختبار الاتصال بقاعدة البيانات
 */
function testDatabaseConnection() {
    global $config;

    try {
        // محاولة الاتصال
        $pdo = get_db_connection();

        // الحصول على معلومات القاعدة
        $version = $pdo->query("SELECT VERSION()")->fetchColumn();

        // عد الجداول
        $tableCount = $pdo->query("SHOW TABLES")->rowCount();

        // عد المستخدمين إن وجد جدول users
        $userCount = 0;
        try {
            $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        } catch (Exception $e) {
            // الجدول غير موجود
        }

        return [
            'success' => true,
            'database' => $config['database'],
            'host' => $config['host'],
            'port' => $config['port'],
            'version' => $version,
            'table_count' => $tableCount,
            'user_count' => $userCount,
            'message' => "✅ الاتصال ناجح بقاعدة البيانات: {$config['database']}"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'database' => $config['database'],
            'host' => $config['host'],
            'port' => $config['port']
        ];
    }
}

/**
 * معالجة الملفات المرفوعة مباشرة من $_FILES
 */
function processDirectFileUpload() {
    try {
        $db = get_db_connection();

        if (empty($_FILES)) {
            throw new Exception('لم يتم رفع أي ملفات');
        }

        $processedFiles = [];
        $totalUsers = 0;
        $totalCustomers = 0;

        // معالجة ملف Users
        if (isset($_FILES['files']) && $_FILES['files']['error'][0] === UPLOAD_ERR_OK) {
            $usersFile = $_FILES['files']['tmp_name'][0];
            $usersFileName = $_FILES['files']['name'][0];

            if (($handle = fopen($usersFile, 'r')) !== false) {
                // قراءة العناوين
                $headers = fgetcsv($handle);
                if ($headers) {
                    // تنظيف BOM إذا وجد
                    $headers[0] = str_replace("\xEF\xBB\xBF", '', $headers[0]);

                    // معالجة البيانات
                    $usersProcessed = processUsersFromFile($db, $handle, $headers, $usersFileName);
                    $totalUsers += $usersProcessed;

                    $processedFiles[] = [
                        'name' => $usersFileName,
                        'type' => 'users',
                        'processed' => $usersProcessed
                    ];
                }
                fclose($handle);
            }
        }

        // معالجة ملف Customers (إذا وجد)
        if (isset($_FILES['files']) && count($_FILES['files']['tmp_name']) > 1 &&
            $_FILES['files']['error'][1] === UPLOAD_ERR_OK) {

            $customersFile = $_FILES['files']['tmp_name'][1];
            $customersFileName = $_FILES['files']['name'][1];

            if (($handle = fopen($customersFile, 'r')) !== false) {
                $headers = fgetcsv($handle);
                if ($headers) {
                    $headers[0] = str_replace("\xEF\xBB\xBF", '', $headers[0]);

                    $customersProcessed = processCustomersFromFile($db, $handle, $headers, $customersFileName);
                    $totalCustomers += $customersProcessed;

                    $processedFiles[] = [
                        'name' => $customersFileName,
                        'type' => 'customers',
                        'processed' => $customersProcessed
                    ];
                }
                fclose($handle);
            }
        }

        return [
            'success' => true,
            'files_processed' => $processedFiles,
            'total_users' => $totalUsers,
            'total_customers' => $totalCustomers,
            'message' => "✅ تم رفع ومعالجة الملفات بنجاح!\n📊 Users: $totalUsers\n📊 Customers: $totalCustomers"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في معالجة الملفات: ' . $e->getMessage()
        ];
    }
}

/**
 * تصدير البيانات من القاعدة القديمة مباشرة كـ CSV
 */
function exportFromOldDatabase() {
    try {
        $table = $_POST['export_table'] ?? 'users';
        $oldDbHost = $_POST['old_db_host'] ?? '127.0.0.1';
        $oldDbPort = $_POST['old_db_port'] ?? '8889';
        $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
        $oldDbUser = $_POST['old_db_user'] ?? 'root';
        $oldDbPass = $_POST['old_db_pass'] ?? 'root';

        // الاتصال بالقاعدة القديمة
        $oldDb = new PDO("mysql:host=$oldDbHost;port=$oldDbPort;dbname=$oldDbName;charset=utf8mb4",
                         $oldDbUser, $oldDbPass);

        // التحقق من وجود الجدول
        $tableExists = $oldDb->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
        if (!$tableExists) {
            throw new Exception("الجدول '$table' غير موجود في القاعدة القديمة");
        }

        // إنشاء مجلد التصدير
        $exportDir = __DIR__ . '/exports/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // اسم الملف
        $fileName = $table . '_exported_' . date('Y-m-d_H-i-s') . '.csv';
        $filePath = $exportDir . $fileName;

        // جلب البيانات
        $stmt = $oldDb->query("SELECT * FROM $table");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            throw new Exception("الجدول '$table' فارغ");
        }

        // كتابة CSV
        $file = fopen($filePath, 'w');

        // كتابة العناوين (UTF-8 BOM للدعم الأمثل)
        fwrite($file, "\xEF\xBB\xBF");
        $headers = array_keys($data[0]);
        fputcsv($file, $headers);

        // كتابة البيانات
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return [
            'success' => true,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'record_count' => count($data),
            'table' => $table,
            'columns' => $headers,
            'file_size' => round(filesize($filePath) / 1024, 2) . ' KB',
            'message' => "✅ تم تصدير $table بنجاح! " . count($data) . " سجل"
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في التصدير: ' . $e->getMessage()
        ];
    }
}

/**
 * معالجة الملف المصدر تلقائياً
 */
function processExportedFile() {
    try {
        $exportedFile = $_POST['exported_file'] ?? '';
        $tableType = $_POST['table_type'] ?? 'users';

        if (empty($exportedFile)) {
            throw new Exception('لم يتم تحديد الملف المصدر');
        }

        $filePath = __DIR__ . '/exports/' . $exportedFile;

        if (!file_exists($filePath)) {
            throw new Exception('الملف المصدر غير موجود: ' . $exportedFile);
        }

        // قراءة وتحليل ملف CSV
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception('فشل في فتح الملف المصدر');
        }

        // قراءة العناوين
        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new Exception('الملف فارغ أو تالف');
        }

        // تنظيف BOM إذا وجد
        $headers[0] = str_replace("\xEF\xBB\xBF", '', $headers[0]);

        $pdo = get_db_connection();
        $processed = 0;

        if ($tableType === 'users') {
            $result = processUsersFromFile($pdo, $handle, $headers, $exportedFile);
            $processed = is_array($result) ? $result['processed'] : $result;
        } elseif ($tableType === 'customers') {
            // للـ customers، نستخدم معالجة مختلفة - دمج في جدول users
            $processed = processCustomersFromFile($pdo, $handle, $headers, $exportedFile);
        } elseif ($tableType === 'countries') {
            // للـ countries، نقل مباشر كما هو
            $processed = processCountriesFromFile($pdo, $handle, $headers, $exportedFile);
        } elseif ($tableType === 'nationalities') {
            // للـ nationalities، نقل مباشر
            $processed = processNationalitiesFromFile($pdo, $handle, $headers, $exportedFile);
        } elseif ($tableType === 'religions') {
            // للـ religions، نقل مباشر
            $processed = processReligionsFromFile($pdo, $handle, $headers, $exportedFile);
        } else {
            throw new Exception("نوع الجدول غير مدعوم: $tableType");
        }

        fclose($handle);

        return [
            'success' => true,
            'processed' => $processed,
            'file_name' => $exportedFile,
            'table_type' => $tableType,
            'details' => is_array($result) ? $result : null,
            'message' => "✅ تم معالجة $processed سجل من $tableType بنجاح!" .
                         (is_array($result) && $result['skipped'] > 0 ? " (تخطي: {$result['skipped']})" : "")
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في معالجة الملف المصدر: ' . $e->getMessage()
        ];
    }
}

/**
 * معالجة ملف customers من الملف المصدر
 */
function processCustomersFromFile($pdo, $handle, $headers, $fileName) {
    $processed = 0;

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        if (count($row) !== count($headers)) {
            continue;
        }

        $data = array_combine($headers, array_map('trim', $row));

        if (empty($data['user_id']) || !is_numeric($data['user_id'])) {
            continue;
        }

        try {
            // تحديث بيانات المستخدم بناءً على customer data (شروط أكثر مرونة)
            $updateParts = [];
            $params = [];

            // التحويلات من SYSTEM_STATUS.md مع شروط مرنة
            if (isset($data['national_identification_number']) &&
                $data['national_identification_number'] !== '' &&
                $data['national_identification_number'] !== 'NULL') {
                $updateParts[] = "national_num = ?";
                $params[] = $data['national_identification_number'];
            }

            if (isset($data['other_phone']) &&
                $data['other_phone'] !== '' &&
                $data['other_phone'] !== 'NULL') {
                $updateParts[] = "phone2 = ?";
                $params[] = $data['other_phone'];
            }

            if (isset($data['job']) &&
                $data['job'] !== '' &&
                $data['job'] !== 'NULL') {
                $updateParts[] = "job = ?";
                $params[] = $data['job'];
                $updateParts[] = "profession = ?";
                $params[] = $data['job'];
            }

            if (isset($data['birth_date']) &&
                $data['birth_date'] !== '' &&
                $data['birth_date'] !== 'NULL' &&
                $data['birth_date'] !== '0000-00-00') {
                $updateParts[] = "birthdate = ?";
                $params[] = $data['birth_date'];
            }

            if (isset($data['rooms_number']) &&
                $data['rooms_number'] !== '' &&
                $data['rooms_number'] !== 'NULL') {
                $updateParts[] = "rooms_num = ?";
                $params[] = $data['rooms_number'];
            }

            if (isset($data['family_number']) &&
                $data['family_number'] !== '' &&
                $data['family_number'] !== 'NULL') {
                $updateParts[] = "family_num = ?";
                $params[] = $data['family_number'];
            }

            if (isset($data['childrens_under_age']) &&
                $data['childrens_under_age'] !== '' &&
                $data['childrens_under_age'] !== 'NULL') {
                $updateParts[] = "children = ?";
                $params[] = $data['childrens_under_age'];
            }

            // إضافة بيانات أخرى من customers
            if (isset($data['accommodation_type']) &&
                $data['accommodation_type'] !== '' &&
                $data['accommodation_type'] !== 'NULL') {
                // يمكن إضافتها لعمود accommodation_type إذا كان موجوداً
                $updateParts[] = "accommodation_type = ?";
                $params[] = $data['accommodation_type'];
            }

            if (isset($data['delivery_city']) &&
                $data['delivery_city'] !== '' &&
                $data['delivery_city'] !== 'NULL') {
                // إضافة معلومات المدينة إذا لم تكن موجودة
                $updateParts[] = "city = COALESCE(city, ?)";
                $params[] = $data['delivery_city'];
            }

            // تنفيذ التحديث أو تسجيل المعالجة
            if (!empty($updateParts)) {
                try {
                    $params[] = $data['user_id'];
                    $sql = "UPDATE users SET " . implode(', ', $updateParts) . " WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $processed++; // نعد كل customer تم معالجته
                } catch (Exception $e) {
                    error_log("خطأ في تحديث customer للمستخدم {$data['user_id']}: " . $e->getMessage());
                }
            } else {
                // حتى لو لم تكن هناك بيانات للتحديث، نعد customer كمعالج
                $processed++;
            }

        } catch (Exception $e) {
            error_log("خطأ في معالجة customer للمستخدم {$data['user_id']}: " . $e->getMessage());
        }
    }

    return $processed;
}

/**
 * معالجة ملف countries من الملف المصدر
 */
function processCountriesFromFile($pdo, $handle, $headers, $fileName) {
    $processed = 0;
    $skipped = 0;

    // الحصول على أعمدة جدول countries الحالية
    try {
        $tableColumns = $pdo->query("DESCRIBE countries")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // الجدول غير موجود، سيتم إنشاؤه
        $tableColumns = [];
    }

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        if (count($row) !== count($headers)) {
            $skipped++;
            continue;
        }

        $data = array_combine($headers, array_map('trim', $row));

        // التحقق من البيانات الأساسية
        if (empty($data['id']) || (!isset($data['name']) && !isset($data['name_ar']) && !isset($data['name_en']))) {
            $skipped++;
            continue;
        }

        try {
            // تحضير البيانات للإدراج مع تحويل أسماء الأعمدة
            $insertData = [];

            // خريطة تحويل الأعمدة من القديم للجديد
            $columnMapping = [
                'id' => 'id',
                'code' => 'code',
                'name' => 'name_ar',  // تحويل name إلى name_ar
                'status' => 'is_active',  // تحويل status إلى is_active
                'created_at' => 'created_at',
                'updated_at' => 'updated_at'
            ];

            // تطبيق التحويلات
            foreach ($columnMapping as $oldCol => $newCol) {
                if (isset($data[$oldCol]) && in_array($newCol, $tableColumns)) {
                    $value = $data[$oldCol];

                    // تنظيف القيم NULL
                    if ($value === 'NULL' || $value === null || $value === '') {
                        $value = null;
                    }

                    $insertData[$newCol] = $value;
                }
            }

            // إضافة name_en (نسخة من name_ar إذا لم تكن موجودة)
            if (isset($insertData['name_ar']) && in_array('name_en', $tableColumns) && !isset($insertData['name_en'])) {
                $insertData['name_en'] = $insertData['name_ar'];
            }

            // إضافة القيم الافتراضية للأعمدة الجديدة
            if (in_array('phone_code', $tableColumns) && !isset($insertData['phone_code'])) {
                $insertData['phone_code'] = '+966'; // افتراضي للسعودية
            }
            if (in_array('currency', $tableColumns) && !isset($insertData['currency'])) {
                $insertData['currency'] = 'SAR';
            }
            if (in_array('currency_symbol', $tableColumns) && !isset($insertData['currency_symbol'])) {
                $insertData['currency_symbol'] = 'ر.س';
            }
            if (in_array('sort_order', $tableColumns) && !isset($insertData['sort_order'])) {
                $insertData['sort_order'] = $insertData['id'] ?? 0;
            }

            // القيم الافتراضية للتواريخ
            if (!isset($insertData['created_at']) || empty($insertData['created_at'])) {
                $insertData['created_at'] = date('Y-m-d H:i:s');
            }
            if (!isset($insertData['updated_at']) || empty($insertData['updated_at'])) {
                $insertData['updated_at'] = date('Y-m-d H:i:s');
            }

            // بناء استعلام INSERT مع UPSERT
            $columns = array_keys($insertData);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO countries (`" . implode('`, `', $columns) . "`)
                    VALUES (" . implode(', ', $placeholders) . ")
                    ON DUPLICATE KEY UPDATE ";

            // إضافة UPDATE للحقول
            $updateParts = [];
            foreach ($columns as $col) {
                if (!in_array($col, ['id', 'created_at'])) {
                    $updateParts[] = "`$col` = VALUES(`$col`)";
                }
            }
            $sql .= implode(', ', $updateParts);

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($insertData));
            $processed++;

        } catch (Exception $e) {
            error_log("خطأ في معالجة country ID {$data['id']}: " . $e->getMessage());
        }
    }

    return $processed;
}

/**
 * معالجة ملف nationalities
 */
function processNationalitiesFromFile($pdo, $handle, $headers, $fileName) {
    $processed = 0;

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        if (count($row) !== count($headers)) {
            continue;
        }

        $data = array_combine($headers, array_map('trim', $row));

        if (empty($data['id'])) {
            continue;
        }

        try {
            // تحضير البيانات مع تنظيف NULL
            $cleanData = [];
            foreach ($data as $key => $value) {
                if ($value === 'NULL' || $value === null || $value === '') {
                    $cleanData[$key] = null;
                } else {
                    $cleanData[$key] = $value;
                }
            }

            // إضافة القيم الافتراضية
            if (!isset($cleanData['created_at']) || $cleanData['created_at'] === null) {
                $cleanData['created_at'] = date('Y-m-d H:i:s');
            }
            if (!isset($cleanData['updated_at']) || $cleanData['updated_at'] === null) {
                $cleanData['updated_at'] = date('Y-m-d H:i:s');
            }

            // إدراج
            $columns = array_keys($cleanData);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO nationalities (`" . implode('`, `', $columns) . "`)
                    VALUES (" . implode(', ', $placeholders) . ")
                    ON DUPLICATE KEY UPDATE ";

            $updateParts = [];
            foreach ($columns as $col) {
                if (!in_array($col, ['id', 'created_at'])) {
                    $updateParts[] = "`$col` = VALUES(`$col`)";
                }
            }
            $sql .= implode(', ', $updateParts);

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($cleanData));
            $processed++;

        } catch (Exception $e) {
            error_log("خطأ في معالجة nationality {$data['id']}: " . $e->getMessage());
        }
    }

    return $processed;
}

/**
 * معالجة ملف religions
 */
function processReligionsFromFile($pdo, $handle, $headers, $fileName) {
    $processed = 0;

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        if (count($row) !== count($headers)) {
            continue;
        }

        $data = array_combine($headers, array_map('trim', $row));

        if (empty($data['id'])) {
            continue;
        }

        try {
            // تحضير البيانات مع تنظيف NULL
            $cleanData = [];
            foreach ($data as $key => $value) {
                if ($value === 'NULL' || $value === null || $value === '') {
                    $cleanData[$key] = null;
                } else {
                    $cleanData[$key] = $value;
                }
            }

            // إضافة القيم الافتراضية
            if (!isset($cleanData['created_at']) || $cleanData['created_at'] === null) {
                $cleanData['created_at'] = date('Y-m-d H:i:s');
            }
            if (!isset($cleanData['updated_at']) || $cleanData['updated_at'] === null) {
                $cleanData['updated_at'] = date('Y-m-d H:i:s');
            }

            // إدراج
            $columns = array_keys($cleanData);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = "INSERT INTO religions (`" . implode('`, `', $columns) . "`)
                    VALUES (" . implode(', ', $placeholders) . ")
                    ON DUPLICATE KEY UPDATE ";

            $updateParts = [];
            foreach ($columns as $col) {
                if (!in_array($col, ['id', 'created_at'])) {
                    $updateParts[] = "`$col` = VALUES(`$col`)";
                }
            }
            $sql .= implode(', ', $updateParts);

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($cleanData));
            $processed++;

        } catch (Exception $e) {
            error_log("خطأ في معالجة religion {$data['id']}: " . $e->getMessage());
        }
    }

    return $processed;
}

/**
 * فحص جدول countries
 */
function checkCountriesTable() {
    try {
        $db = get_db_connection();

        // التحقق من وجود الجدول
        $tableExists = $db->query("SHOW TABLES LIKE 'countries'")->rowCount() > 0;

        if ($tableExists) {
            $recordCount = $db->query("SELECT COUNT(*) FROM countries")->fetchColumn();
            $columns = $db->query("DESCRIBE countries")->fetchAll(PDO::FETCH_COLUMN);

            return [
                'success' => true,
                'table_exists' => true,
                'record_count' => $recordCount,
                'columns' => $columns,
                'message' => "جدول countries موجود ويحتوي على $recordCount سجل"
            ];
        } else {
            return [
                'success' => true,
                'table_exists' => false,
                'record_count' => 0,
                'columns' => [],
                'message' => 'جدول countries غير موجود - سيتم إنشاؤه تلقائياً'
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في فحص جدول countries: ' . $e->getMessage()
        ];
    }
}

/**
 * تحضير جدول countries
 */
function prepareCountriesTable() {
    try {
        $db = get_db_connection();

        // التحقق من وجود الجدول
        $tableExists = $db->query("SHOW TABLES LIKE 'countries'")->rowCount() > 0;

        if (!$tableExists) {
            // إنشاء جدول countries بناءً على البنية المعيارية
            $createSql = "CREATE TABLE `countries` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) DEFAULT NULL,
                `name_ar` varchar(255) DEFAULT NULL,
                `name_en` varchar(255) DEFAULT NULL,
                `code` varchar(10) DEFAULT NULL,
                `phone_code` varchar(10) DEFAULT NULL,
                `flag` varchar(255) DEFAULT NULL,
                `status` tinyint DEFAULT 1,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `countries_code_unique` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $db->exec($createSql);

            return [
                'success' => true,
                'created' => true,
                'message' => 'تم إنشاء جدول countries بنجاح'
            ];
        } else {
            return [
                'success' => true,
                'created' => false,
                'message' => 'جدول countries موجود مسبقاً'
            ];
        }

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في تحضير جدول countries: ' . $e->getMessage()
        ];
    }
}

/**
 * تحضير الجداول المرتبطة لـ CV/Maids
 */
function prepareRelatedTables() {
    try {
        $db = get_db_connection();
        $results = [];

        // إنشاء جدول nationalities إذا لم يكن موجوداً
        $tableExists = $db->query("SHOW TABLES LIKE 'nationalities'")->rowCount() > 0;
        if (!$tableExists) {
            $createSql = "CREATE TABLE `nationalities` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `logo` varchar(500) DEFAULT NULL,
                `phone_code` varchar(255) DEFAULT '',
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $db->exec($createSql);
            $results['nationalities'] = 'تم إنشاؤه';
        } else {
            $results['nationalities'] = 'موجود مسبقاً';
        }

        // إنشاء جدول religions إذا لم يكن موجوداً
        $tableExists = $db->query("SHOW TABLES LIKE 'religions'")->rowCount() > 0;
        if (!$tableExists) {
            $createSql = "CREATE TABLE `religions` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `created_at` timestamp NULL DEFAULT NULL,
                `updated_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            $db->exec($createSql);
            $results['religions'] = 'تم إنشاؤه';
        } else {
            $results['religions'] = 'موجود مسبقاً';
        }

        // التحقق من جدول maids وإضافة cv_status إذا لم يكن موجوداً
        $maidsExists = $db->query("SHOW TABLES LIKE 'maids'")->rowCount() > 0;
        if ($maidsExists) {
            $cvStatusExists = $db->query("SHOW COLUMNS FROM maids LIKE 'cv_status'")->rowCount() > 0;
            if (!$cvStatusExists) {
                $db->exec("ALTER TABLE maids ADD COLUMN cv_status TINYINT DEFAULT 0 COMMENT '0=غير محجوز 1=محجوز 2=باك آوت 3=معلق 4=تم الترحيل 5=هروب'");
                $results['maids_cv_status'] = 'تم إضافة عمود cv_status';
            } else {
                $results['maids_cv_status'] = 'عمود cv_status موجود';
            }
        } else {
            $results['maids'] = 'الجدول غير موجود - قد نحتاج لإنشائه';
        }

        return [
            'success' => true,
            'results' => $results,
            'message' => 'تم تحضير الجداول المرتبطة: ' . implode(', ', array_keys($results))
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في تحضير الجداول المرتبطة: ' . $e->getMessage()
        ];
    }
}

/**
 * نقل شامل لجداول CV/Maids
 */
function migrateCVComprehensive() {
    try {
        $oldCvTable = $_POST['old_cv_table'] ?? 'maids';
        $cvStatusMapping = $_POST['cv_status_mapping'] ?? 'preserve';
        $idMappingStrategy = $_POST['id_mapping_strategy'] ?? 'preserve';

        // الاتصال بالقواعد
        $oldDbHost = $_POST['old_db_host'] ?? '127.0.0.1';
        $oldDbPort = $_POST['old_db_port'] ?? '8889';
        $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
        $oldDbUser = $_POST['old_db_user'] ?? 'root';
        $oldDbPass = $_POST['old_db_pass'] ?? 'root';

        $oldDb = new PDO("mysql:host=$oldDbHost;port=$oldDbPort;dbname=$oldDbName;charset=utf8mb4",
                         $oldDbUser, $oldDbPass);
        $newDb = get_db_connection();

        $results = [];

        // الخطوة 1: نقل nationalities
        $results['nationalities'] = migrateTableDirect($oldDb, $newDb, 'nationalities');

        // الخطوة 2: نقل religions
        $results['religions'] = migrateTableDirect($oldDb, $newDb, 'religions');

        // الخطوة 3: نقل maids/cv مع معالجة خاصة
        if ($oldDb->query("SHOW TABLES LIKE '$oldCvTable'")->rowCount() > 0) {
            $results['maids'] = migrateMaidsTable($oldDb, $newDb, $oldCvTable, $cvStatusMapping, $idMappingStrategy);
        } else {
            $results['maids'] = ['processed' => 0, 'message' => "الجدول $oldCvTable غير موجود في القاعدة القديمة"];
        }

        // الخطوة 4: نقل الترجمات
        $results['translations'] = migrateTranslations($oldDb, $newDb);

        return [
            'success' => true,
            'results' => $results,
            'message' => 'تم النقل الشامل لجداول CV بنجاح'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في النقل الشامل: ' . $e->getMessage()
        ];
    }
}

/**
 * نقل جدول مباشر بين القواعد
 */
function migrateTableDirect($oldDb, $newDb, $tableName) {
    try {
        // التحقق من وجود الجدول في القديم
        if ($oldDb->query("SHOW TABLES LIKE '$tableName'")->rowCount() === 0) {
            return ['processed' => 0, 'message' => "الجدول $tableName غير موجود"];
        }

        // جلب البيانات
        $data = $oldDb->query("SELECT * FROM $tableName")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            return ['processed' => 0, 'message' => "الجدول $tableName فارغ"];
        }

        $processed = 0;

        foreach ($data as $row) {
            try {
                // تحضير البيانات
                $columns = array_keys($row);
                $placeholders = array_fill(0, count($columns), '?');

                $sql = "INSERT INTO $tableName (`" . implode('`, `', $columns) . "`)
                        VALUES (" . implode(', ', $placeholders) . ")
                        ON DUPLICATE KEY UPDATE ";

                // إضافة UPDATE للحقول
                $updateParts = [];
                foreach ($columns as $col) {
                    if (!in_array($col, ['id', 'created_at'])) {
                        $updateParts[] = "`$col` = VALUES(`$col`)";
                    }
                }
                $sql .= implode(', ', $updateParts);

                $stmt = $newDb->prepare($sql);
                $stmt->execute(array_values($row));
                $processed++;

            } catch (Exception $e) {
                error_log("خطأ في نقل $tableName سجل {$row['id']}: " . $e->getMessage());
            }
        }

        return ['processed' => $processed, 'message' => "تم نقل $processed سجل"];

    } catch (Exception $e) {
        return ['processed' => 0, 'message' => 'خطأ: ' . $e->getMessage()];
    }
}

/**
 * نقل جدول maids مع معالجة خاصة لـ cv_status
 */
function migrateMaidsTable($oldDb, $newDb, $oldTableName, $cvStatusMapping, $idMappingStrategy) {
    try {
        // جلب البيانات من الجدول القديم
        $data = $oldDb->query("SELECT * FROM $oldTableName")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            return ['processed' => 0, 'message' => "الجدول $oldTableName فارغ"];
        }

        $processed = 0;
        $cvStatusConversions = 0;

        foreach ($data as $row) {
            try {
                // معالجة cv_status بعناية
                if (isset($row['cv_status'])) {
                    $originalStatus = $row['cv_status'];

                    if ($cvStatusMapping === 'convert_to_available') {
                        // تحويل cv_status إلى available (boolean)
                        // 0 (غير محجوز) = available (1)
                        // باقي الحالات = not available (0)
                        $row['available'] = ($originalStatus == 0) ? 1 : 0;
                        $cvStatusConversions++;
                    } elseif ($cvStatusMapping === 'map_to_status') {
                        // تحويل cv_status إلى status
                        // 0,1 = active (1), 2,3,4,5 = inactive (0)
                        $row['status'] = in_array($originalStatus, [0, 1]) ? 1 : 0;
                        $cvStatusConversions++;
                    }
                    // إذا كان 'preserve' نحتفظ بالقيمة كما هي
                }

                // معالجة الـ IDs المرتبطة
                if (isset($row['national_id']) && !isset($row['nationality_id'])) {
                    $row['nationality_id'] = $row['national_id'];
                    unset($row['national_id']);
                }

                // تنظيف البيانات
                foreach ($row as $key => $value) {
                    if ($value === 'NULL' || $value === null || $value === '') {
                        $row[$key] = null;
                    }
                }

                // إدراج في الجدول الجديد
                $columns = array_keys($row);
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
                $stmt->execute(array_values($row));
                $processed++;

            } catch (Exception $e) {
                error_log("خطأ في نقل maid {$row['id']}: " . $e->getMessage());
            }
        }

        return [
            'processed' => $processed,
            'cv_status_conversions' => $cvStatusConversions,
            'mapping_used' => $cvStatusMapping,
            'message' => "تم نقل $processed سجل مع $cvStatusConversions تحويل لحالة CV"
        ];

    } catch (Exception $e) {
        return ['processed' => 0, 'message' => 'خطأ: ' . $e->getMessage()];
    }
}

/**
 * نقل جداول الترجمات
 */
function migrateTranslations($oldDb, $newDb) {
    try {
        $results = [];
        $translationTables = [
            'nationalities_translations',
            'religions_translations',
            'maid_translations'
        ];

        foreach ($translationTables as $table) {
            if ($oldDb->query("SHOW TABLES LIKE '$table'")->rowCount() > 0) {
                $result = migrateTableDirect($oldDb, $newDb, $table);
                $results[$table] = $result;
            } else {
                $results[$table] = ['processed' => 0, 'message' => 'غير موجود'];
            }
        }

        return [
            'processed' => array_sum(array_column($results, 'processed')),
            'details' => $results,
            'message' => 'تم نقل جداول الترجمات'
        ];

    } catch (Exception $e) {
        return ['processed' => 0, 'message' => 'خطأ: ' . $e->getMessage()];
    }
}

function cleanRowData($row) {
    return array_map(function($value) {
        if ($value === "NULL" || $value === null || $value === '') {
            return null;
        }
        return trim($value);
    }, $row);
}

function parseDateValue($dateValue) {
    if (empty($dateValue) || $dateValue === 'NULL') {
        return null;
    }

    // Try different date formats
    $formats = ['Y-m-d H:i:s', 'Y-m-d', 'd-m-Y', 'm/d/Y', 'd/m/Y'];

    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateValue);
        if ($date !== false) {
            return $date->format('Y-m-d H:i:s');
        }
    }

    // If all fails, try strtotime
    $timestamp = strtotime($dateValue);
    if ($timestamp !== false) {
        return date('Y-m-d H:i:s', $timestamp);
    }

    return null;
}

/**
 * دوال الحماية والأمان
 */
function createSafetyBackup() {
    global $config;

    try {
        $backupDir = __DIR__ . '/safety_backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $timestamp = date('Y_m_d_H_i_s');
        $backupFile = $backupDir . "platinum_new_backup_$timestamp.sql";

        $command = "mysqldump -h {$config['host']} -P {$config['port']} -u {$config['username']} -p{$config['password']} {$config['database']} > '$backupFile' 2>/dev/null";

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($backupFile)) {
            error_log("تم إنشاء نسخة احتياطية آمنة: $backupFile");
            return $backupFile;
        }

    } catch (Exception $e) {
        error_log("خطأ في إنشاء النسخة الاحتياطية: " . $e->getMessage());
    }

    return false;
}

function createManualBackup() {
    $backupFile = createSafetyBackup();

    if ($backupFile) {
        return [
            'success' => true,
            'message' => 'تم إنشاء نسخة احتياطية بنجاح',
            'backup_file' => basename($backupFile),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        return [
            'success' => false,
            'message' => 'فشل في إنشاء النسخة الاحتياطية'
        ];
    }
}

function checkSystemStatus() {
    global $config;

    try {
        $db = get_db_connection();

        // عد المستخدمين في القاعدة الجديدة
        $newUsersCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // الاتصال بالقاعدة القديمة
        $oldUsersCount = 0;
        $customersCount = 0;

        try {
            $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_old;charset=utf8mb4", 'root', 'root');
            $oldUsersCount = $oldDb->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $customersCount = $oldDb->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        } catch (Exception $e) {
            // القاعدة القديمة غير متوفرة
        }

        // التحقق من الأعمدة المفقودة
        $importantColumns = ['first_name', 'last_name', 'register_from', 'national_num', 'phone2', 'job'];
        $missingColumns = [];

        foreach ($importantColumns as $column) {
            $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
            $stmt->execute([$column]);
            if ($stmt->rowCount() == 0) {
                $missingColumns[] = $column;
            }
        }

        return [
            'success' => true,
            'old_users_count' => $oldUsersCount,
            'new_users_count' => $newUsersCount,
            'customers_count' => $customersCount,
            'missing_columns' => $missingColumns,
            'ready_for_migration' => empty($missingColumns),
            'status' => [
                'db_connection' => true,
                'db_name' => $config['database'],
                'users_count' => $newUsersCount
            ]
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ: ' . $e->getMessage()
        ];
    }
}

/**
 * تطبيق الترتيب المخصص للجدول
 */
function prepareUsersTable() {
    $db = null;
    try {
        $db = get_db_connection();

        // إضافة الأعمدة المفقودة أولاً (بدون transaction)
        $addedColumns = addMissingColumnsToUsers($db);

        // تطبيق الترتيب المخصص (بدون transaction لتجنب التضارب)
        $appliedSteps = 0;

        // الترتيب الأساسي فقط (الأهم)
        $basicOrderingSteps = [
            "ALTER TABLE `users` CHANGE `id` `id` BIGINT UNSIGNED NOT NULL auto_increment FIRST",
            "ALTER TABLE `users` CHANGE `name` `name` VARCHAR(255) NULL DEFAULT NULL AFTER `id`",
            "ALTER TABLE `users` CHANGE `first_name` `first_name` VARCHAR(255) NULL DEFAULT NULL AFTER `name`",
            "ALTER TABLE `users` CHANGE `last_name` `last_name` VARCHAR(255) NULL DEFAULT NULL AFTER `first_name`",
            "ALTER TABLE `users` CHANGE `phone` `phone` VARCHAR(255) NULL DEFAULT NULL AFTER `last_name`",
            "ALTER TABLE `users` CHANGE `email` `email` VARCHAR(255) NULL DEFAULT NULL AFTER `phone`"
        ];

        foreach ($basicOrderingSteps as $sql) {
            try {
                $db->exec($sql);
                $appliedSteps++;
            } catch (Exception $e) {
                // تسجيل الخطأ لكن متابعة
                error_log("خطأ في خطوة الترتيب: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => "✅ تم تحضير الجدول بنجاح\n📋 أعمدة مُضافة: " . count($addedColumns) . "\n📐 خطوات ترتيب مطبقة: $appliedSteps",
            'added_columns' => $addedColumns,
            'applied_steps' => $appliedSteps
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في تحضير الجدول: ' . $e->getMessage()
        ];
    }
}

/**
 * إضافة الأعمدة المفقودة بأمان
 */
function addMissingColumnsToUsers($db) {
    $columnsToAdd = [
        'first_name' => 'VARCHAR(255) NULL',
        'last_name' => 'VARCHAR(255) NULL',
        'phone_code' => 'VARCHAR(255) NULL DEFAULT "+966"',
        'national_num' => 'VARCHAR(50) NULL',
        'phone2' => 'VARCHAR(50) NULL',
        'job' => 'VARCHAR(255) NULL',
        'birthdate' => 'DATE NULL',
        'register_from' => 'VARCHAR(255) NULL DEFAULT "web"',
        'is_blocked' => 'TINYINT NULL DEFAULT 0',
        'status' => 'TINYINT NULL DEFAULT 1',
        'remember_token' => 'VARCHAR(100) NULL',
        'nationality_id' => 'BIGINT UNSIGNED NULL',
        'profession' => 'VARCHAR(255) NULL',
        'monthly_income' => 'DECIMAL(10,2) NULL',
        'rooms_num' => 'VARCHAR(10) NULL',
        'family_num' => 'VARCHAR(10) NULL',
        'children' => 'VARCHAR(10) NULL',
        'city' => 'VARCHAR(100) NULL'
    ];

    $addedColumns = [];

    foreach ($columnsToAdd as $column => $definition) {
        try {
            $checkStmt = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
            $checkStmt->execute([$column]);

            if ($checkStmt->rowCount() == 0) {
                $db->exec("ALTER TABLE users ADD COLUMN `$column` $definition");
                $addedColumns[] = $column;
                usleep(100000); // توقف قصير بين العمليات
            }
        } catch (Exception $e) {
            // تسجيل ومتابعة
            error_log("خطأ في إضافة العمود $column: " . $e->getMessage());
        }
    }

    return $addedColumns;
}

/**
 * فحص أعمدة القاعدة القديمة
 */
function scanOldDatabaseColumns() {
    try {
        // الاتصال بالقاعدة القديمة (نفترض أنها platinum أو erp_operations_new)
        $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_old;charset=utf8mb4", 'root', 'root');
        $newDb = get_db_connection();

        // الحصول على أعمدة جدول users في القاعدة القديمة
        $oldColumnsStmt = $oldDb->query("DESCRIBE users");
        $oldColumns = $oldColumnsStmt->fetchAll(PDO::FETCH_COLUMN);

        // الحصول على أعمدة جدول users في القاعدة الجديدة
        $newColumnsStmt = $newDb->query("DESCRIBE users");
        $newColumns = $newColumnsStmt->fetchAll(PDO::FETCH_COLUMN);

        // تحديد الأعمدة المفقودة
        $missingColumns = array_diff($oldColumns, $newColumns);

        return [
            'success' => true,
            'old_columns' => $oldColumns,
            'new_columns' => $newColumns,
            'missing_columns' => array_values($missingColumns),
            'message' => 'تم فحص القاعدة القديمة بنجاح'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في فحص القاعدة القديمة: ' . $e->getMessage()
        ];
    }
}

/**
 * إضافة جميع الأعمدة المفقودة
 */
function addAllMissingColumns() {
    try {
        $db = get_db_connection();

        // فحص الأعمدة المفقودة
        $scanResult = scanOldDatabaseColumns();
        if (!$scanResult['success']) {
            throw new Exception($scanResult['message']);
        }

        $missingColumns = $scanResult['missing_columns'];
        $addedColumns = [];

        // إضافة كل عمود مفقود
        foreach ($missingColumns as $column) {
            try {
                // تحديد نوع العمود المناسب
                $columnType = guessColumnType($column);
                $db->exec("ALTER TABLE users ADD COLUMN `$column` $columnType");
                $addedColumns[] = $column;
            } catch (Exception $e) {
                error_log("خطأ في إضافة العمود $column: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'added_columns' => $addedColumns,
            'message' => "تم إضافة " . count($addedColumns) . " عمود من أصل " . count($missingColumns)
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في إضافة الأعمدة: ' . $e->getMessage()
        ];
    }
}

/**
 * نقل جميع البيانات من القاعدة القديمة
 */
function migrateAllDataFromOld() {
    try {
        $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_old;charset=utf8mb4", 'root', 'root');
        $newDb = get_db_connection();
        $newDb->beginTransaction();

        // الحصول على جميع البيانات من القاعدة القديمة
        $oldData = $oldDb->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($oldData)) {
            throw new Exception('لا توجد بيانات في القاعدة القديمة');
        }

        // الحصول على أعمدة القاعدة الجديدة
        $newColumnsStmt = $newDb->query("DESCRIBE users");
        $newColumns = $newColumnsStmt->fetchAll(PDO::FETCH_COLUMN);

        $migratedUsers = 0;
        $namesSplit = 0;

        foreach ($oldData as $row) {
            try {
                // تحضير البيانات للإدراج
                $insertData = [];
                $insertColumns = [];

                foreach ($newColumns as $column) {
                    if ($column === 'first_name') {
                        // تقسيم name إلى first_name
                        $name = trim($row['name'] ?? '');
                        $nameParts = explode(' ', $name, 2);
                        $insertData[] = $nameParts[0] ?? '';
                        $insertColumns[] = 'first_name';
                        if (!empty($name)) $namesSplit++;
                    } elseif ($column === 'last_name') {
                        // تقسيم name إلى last_name
                        $name = trim($row['name'] ?? '');
                        $nameParts = explode(' ', $name, 2);
                        $insertData[] = $nameParts[1] ?? '';
                        $insertColumns[] = 'last_name';
                    } elseif ($column === 'register_from') {
                        $insertData[] = 'web';
                        $insertColumns[] = 'register_from';
                    } elseif (isset($row[$column])) {
                        $insertData[] = $row[$column];
                        $insertColumns[] = $column;
                    } else {
                        $insertData[] = null;
                        $insertColumns[] = $column;
                    }
                }

                // إدراج البيانات
                $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
                $insertSql = "INSERT IGNORE INTO users (" . implode(',', $insertColumns) . ") VALUES ($placeholders)";
                $stmt = $newDb->prepare($insertSql);
                $stmt->execute($insertData);

                $migratedUsers++;

            } catch (Exception $e) {
                error_log("خطأ في نقل المستخدم: " . $e->getMessage());
            }
        }

        $newDb->commit();

        return [
            'success' => true,
            'migrated_users' => $migratedUsers,
            'processed_columns' => count($newColumns),
            'names_split' => $namesSplit,
            'message' => "تم نقل $migratedUsers مستخدم بنجاح مع تقسيم $namesSplit اسم"
        ];

    } catch (Exception $e) {
        if ($newDb && $newDb->inTransaction()) {
            $newDb->rollBack();
        }

        return [
            'success' => false,
            'message' => 'خطأ في النقل الشامل: ' . $e->getMessage()
        ];
    }
}

/**
 * تخمين نوع العمود
 */
function guessColumnType($columnName) {
    $columnName = strtolower($columnName);

    if (strpos($columnName, '_at') !== false) {
        return 'TIMESTAMP NULL';
    } elseif (strpos($columnName, '_id') !== false) {
        return 'BIGINT UNSIGNED NULL';
    } elseif (strpos($columnName, 'phone') !== false) {
        return 'VARCHAR(50) NULL';
    } elseif (strpos($columnName, 'email') !== false) {
        return 'VARCHAR(255) NULL';
    } elseif (in_array($columnName, ['first_name', 'last_name', 'name'])) {
        return 'VARCHAR(255) NULL';
    } elseif (in_array($columnName, ['city', 'code'])) {
        return 'VARCHAR(100) NULL';
    } elseif (strpos($columnName, 'price') !== false || strpos($columnName, 'income') !== false) {
        return 'DECIMAL(10,2) NULL';
    } elseif (strpos($columnName, 'date') !== false) {
        return 'DATE NULL';
    } else {
        return 'VARCHAR(255) NULL';
    }
}



function handleExecuteSql() {
    $sql = $_POST['sql'] ?? '';
    if (!is_string($sql) || trim($sql) === '') {
        return [
            'success' => false,
            'message' => 'لم يتم توفير الاستعلام'
        ];
    }

    try {
        $db = get_db_connection();
        $statements = array_filter(array_map('trim', preg_split('/;\s*/', $sql)));
        $lastResult = [];

        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }

            if (stripos($statement, 'select') === 0 || stripos($statement, 'show') === 0 || stripos($statement, 'describe') === 0) {
                $query = $db->query($statement);
                $lastResult = $query ? $query->fetchAll(PDO::FETCH_ASSOC) : [];
            } else {
                $db->exec($statement);
            }
        }

        return [
            'success' => true,
            'message' => 'تم تنفيذ الاستعلام',
            'result' => $lastResult
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في تنفيذ الاستعلام: ' . $e->getMessage()
        ];
    }
}

function migrateContracts() {
    $oldHost = $_POST['old_db_host'] ?? '127.0.0.1';
    $oldPort = $_POST['old_db_port'] ?? 8889;
    $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
    $oldUser = $_POST['old_db_user'] ?? 'root';
    $oldPass = $_POST['old_db_pass'] ?? 'root';

    try {
        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة الجديدة: ' . $e->getMessage()
        ];
    }

    try {
        $oldDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $oldHost, $oldPort, $oldDbName);
        $oldDb = new PDO($oldDsn, $oldUser, $oldPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة القديمة: ' . $e->getMessage()
        ];
    }

    try {
        $contracts = $oldDb->query(
            'SELECT id, attatches, covered_guarantee_date, expired_date, contract_from_day, contract_to_day, created_at, updated_at, throwback_date, resv_id, throwback_reason, date_contract, contract_visa_num, musand_num, authorization_num, contract_border_num, contract_cost, contract_tex, contract_visa_type, contract_visa_date, contract_late_cost, contract_holiday_day, musand_date, driver_id FROM contracts ORDER BY id'
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'لم يتمكن من قراءة جدول العقد القديم: ' . $e->getMessage()
        ];
    }

    if (empty($contracts)) {
        return [
            'success' => true,
            'message' => 'لا توجد بيانات لنقلها من جدول العقود القديم',
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0
        ];
    }

    $reservationUserMap = [];
    try {
        $reservationStmt = $oldDb->query('SELECT id, user_id, cv_id, administrator_id, status FROM rservations');
        while ($reservationRow = $reservationStmt->fetch(PDO::FETCH_ASSOC)) {
            $reservationId = isset($reservationRow['id']) ? (int)$reservationRow['id'] : 0;
            if ($reservationId <= 0) {
                continue;
            }

            $reservationUserMap[$reservationId] = [
                'user_id' => $reservationRow['user_id'] ?? null,
                'cv_id' => $reservationRow['cv_id'] ?? null,
                'administrator_id' => $reservationRow['administrator_id'] ?? null,
                'status' => $reservationRow['status'] ?? null
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في قراءة بيانات الحجوزات: ' . $e->getMessage()
        ];
    }

    $insertSql = <<<SQL
INSERT INTO contracts (
    id,
    status,
    warranty_status,
    signature,
    warranty_start_date,
    warranty_end_date,
    created_at,
    updated_at,
    completed_at,
    order_id,
    user_id,
    marketer_id,
    maid_id,
    required_maid_nationality_id,
    required_maid_religion_id,
    required_maid_job_title_id,
    required_maid_notes,
    contract_date,
    visa_number,
    msaned_number,
    authorization_num,
    contract_border_num,
    contract_cost,
    contract_tex,
    contract_visa_type,
    contract_visa_date,
    contract_late_cost,
    contract_holiday_day,
    throwback_reason,
    throwback_date,
    musand_date
)
VALUES (
    :id,
    :status,
    :warranty_status,
    :signature,
    :warranty_start_date,
    :warranty_end_date,
    :created_at,
    :updated_at,
    :completed_at,
    :order_id,
    :user_id,
    :marketer_id,
    :maid_id,
    :required_maid_nationality_id,
    :required_maid_religion_id,
    :required_maid_job_title_id,
    :required_maid_notes,
    :contract_date,
    :visa_number,
    :msaned_number,
    :authorization_num,
    :contract_border_num,
    :contract_cost,
    :contract_tex,
    :contract_visa_type,
    :contract_visa_date,
    :contract_late_cost,
    :contract_holiday_day,
    :throwback_reason,
    :throwback_date,
    :musand_date
)
ON DUPLICATE KEY UPDATE
    status = VALUES(status),
    warranty_status = VALUES(warranty_status),
    signature = VALUES(signature),
    warranty_start_date = VALUES(warranty_start_date),
    warranty_end_date = VALUES(warranty_end_date),
    created_at = VALUES(created_at),
    updated_at = VALUES(updated_at),
    completed_at = VALUES(completed_at),
    order_id = VALUES(order_id),
    user_id = VALUES(user_id),
    marketer_id = VALUES(marketer_id),
    maid_id = VALUES(maid_id),
    required_maid_nationality_id = VALUES(required_maid_nationality_id),
    required_maid_religion_id = VALUES(required_maid_religion_id),
    required_maid_job_title_id = VALUES(required_maid_job_title_id),
    required_maid_notes = VALUES(required_maid_notes),
    contract_date = VALUES(contract_date),
    visa_number = VALUES(visa_number),
    msaned_number = VALUES(msaned_number),
    authorization_num = VALUES(authorization_num),
    contract_border_num = VALUES(contract_border_num),
    contract_cost = VALUES(contract_cost),
    contract_tex = VALUES(contract_tex),
    contract_visa_type = VALUES(contract_visa_type),
    contract_visa_date = VALUES(contract_visa_date),
    contract_late_cost = VALUES(contract_late_cost),
    contract_holiday_day = VALUES(contract_holiday_day),
    throwback_reason = VALUES(throwback_reason),
    throwback_date = VALUES(throwback_date),
    musand_date = VALUES(musand_date)
SQL;

    $insertStmt = $newDb->prepare($insertSql);

    $newDb->beginTransaction();

    $processed = 0;
    $inserted = 0;
    $updated = 0;
    $unchanged = 0;
    $skipped = 0;

    foreach ($contracts as $row) {
        $processed++;

        $contractId = isset($row['id']) ? (int)$row['id'] : 0;
        if ($contractId <= 0) {
            $skipped++;
            continue;
        }

        try {
            $status = 'shelter';
            $warrantyStartDate = normalizeWarrantyTimestamp($row['contract_from_day'] ?? null);
            $warrantyEndDate = normalizeWarrantyTimestamp($row['contract_to_day'] ?? null);
            $warrantyStatus = determineWarrantyStatus($row['contract_from_day'] ?? null, $row['contract_to_day'] ?? null);

            $orderId = normalizeContractInt($row['resv_id'] ?? null);
            $userId = 1673;
            $maidId = null;
            $marketerId = normalizeContractInt($row['driver_id'] ?? null);

            if ($orderId !== null && isset($reservationUserMap[$orderId])) {
                $reservationData = $reservationUserMap[$orderId];

                $mappedUserId = normalizeContractInt($reservationData['user_id'] ?? null);
                if ($mappedUserId !== null) {
                    $userId = $mappedUserId;
                }

                $mappedMaidId = normalizeContractInt($reservationData['cv_id'] ?? null);
                if ($mappedMaidId !== null) {
                    $maidId = $mappedMaidId;
                }

                $mappedMarketerId = normalizeContractInt($reservationData['administrator_id'] ?? null);
                if ($mappedMarketerId !== null) {
                    $marketerId = $mappedMarketerId;
                }

                $mappedStatus = mapReservationStatus($reservationData['status'] ?? null);
                if ($mappedStatus !== null) {
                    $status = $mappedStatus;
                }
            }

            $params = [
                ':id' => $contractId,
                ':status' => $status,
                ':warranty_status' => $warrantyStatus,
                ':signature' => normalizeContractValue($row['attatches'] ?? null),
                ':warranty_start_date' => $warrantyStartDate,
                ':warranty_end_date' => $warrantyEndDate,
                ':created_at' => normalizeContractDateTime($row['created_at'] ?? null),
                ':updated_at' => normalizeContractDateTime($row['updated_at'] ?? null),
                ':completed_at' => normalizeContractDateTime($row['throwback_date'] ?? null),
                ':order_id' => $orderId,
                ':user_id' => $userId,
                ':marketer_id' => $marketerId,
                ':maid_id' => $maidId,
                ':required_maid_nationality_id' => null,
                ':required_maid_religion_id' => null,
                ':required_maid_job_title_id' => null,
                ':required_maid_notes' => normalizeContractValue($row['throwback_reason'] ?? null),
                ':contract_date' => normalizeContractDate($row['date_contract'] ?? null),
                ':visa_number' => normalizeContractValue($row['contract_visa_num'] ?? null),
                ':msaned_number' => normalizeContractValue($row['musand_num'] ?? null),
                ':authorization_num' => normalizeContractValue($row['authorization_num'] ?? null),
                ':contract_border_num' => normalizeContractValue($row['contract_border_num'] ?? null),
                ':contract_cost' => normalizeContractFloat($row['contract_cost'] ?? null),
                ':contract_tex' => normalizeContractFloat($row['contract_tex'] ?? null),
                ':contract_visa_type' => normalizeContractValue($row['contract_visa_type'] ?? null),
                ':contract_visa_date' => normalizeContractDate($row['contract_visa_date'] ?? null),
                ':contract_late_cost' => normalizeContractValue($row['contract_late_cost'] ?? null),
                ':contract_holiday_day' => normalizeContractValue($row['contract_holiday_day'] ?? null),
                ':throwback_reason' => normalizeContractValue($row['throwback_reason'] ?? null),
                ':throwback_date' => normalizeContractDate($row['throwback_date'] ?? null),
                ':musand_date' => normalizeContractDate($row['musand_date'] ?? null)
            ];

            $insertStmt->execute($params);
            $affected = $insertStmt->rowCount();

            if ($affected === 1) {
                $inserted++;
            } elseif ($affected === 2) {
                $updated++;
            } else {
                $unchanged++;
            }
        } catch (Exception $e) {
            $skipped++;
            error_log('خطأ في نقل العقد #' . $contractId . ': ' . $e->getMessage());
        }
    }

    $newDb->commit();

    return [
        'success' => true,
        'message' => 'تم نقل العقود بنجاح',
        'processed' => $processed,
        'inserted' => $inserted,
        'updated' => $updated,
        'unchanged' => $unchanged,
        'skipped' => $skipped
    ];
}


function migrateContractInfos() {
    $oldHost = $_POST['old_db_host'] ?? '127.0.0.1';
    $oldPort = $_POST['old_db_port'] ?? 8889;
    $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
    $oldUser = $_POST['old_db_user'] ?? 'root';
    $oldPass = $_POST['old_db_pass'] ?? 'root';

    try {
        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في الاتصال بقاعدة البيانات الجديدة: ' . $e->getMessage()
        ];
    }

    try {
        $oldDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $oldHost, $oldPort, $oldDbName);
        $oldDb = new PDO($oldDsn, $oldUser, $oldPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في الاتصال بقاعدة البيانات القديمة: ' . $e->getMessage()
        ];
    }

    try {
        $records = $oldDb->query(
            'SELECT id, contract_visa_num, contract_visa_Hijri_date, contract_visa_date, contract_cost, contract_count_day, contract_total, date_contract, contract_from_day, contract_to_day, musand_num, authorization_num, created_at, updated_at, throwback_date, throwback_reason, final_visa_date FROM contracts ORDER BY id'
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في استرجاع بيانات العقود: ' . $e->getMessage()
        ];
    }

    if (empty($records)) {
        return [
            'success' => true,
            'message' => 'لا توجد سجلات للعقود في قاعدة البيانات القديمة',
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0
        ];
    }

    $insertSql = <<<SQL
INSERT INTO contract_infos (
    contract_id,
    visa_number,
    visa_hijry_date,
    visa_milad_date,
    monthly_cost,
    contract_duration,
    contract_value,
    contract_date,
    ingaz_from,
    ingaz_to,
    msaned_number,
    tafwed_number,
    created_at,
    updated_at,
    maid_date_back,
    back_reasons,
    arrival_date
)
VALUES (
    :contract_id,
    :visa_number,
    :visa_hijry_date,
    :visa_milad_date,
    :monthly_cost,
    :contract_duration,
    :contract_value,
    :contract_date,
    :ingaz_from,
    :ingaz_to,
    :msaned_number,
    :tafwed_number,
    :created_at,
    :updated_at,
    :maid_date_back,
    :back_reasons,
    :arrival_date
)
ON DUPLICATE KEY UPDATE
    visa_number = VALUES(visa_number),
    visa_hijry_date = VALUES(visa_hijry_date),
    visa_milad_date = VALUES(visa_milad_date),
    monthly_cost = VALUES(monthly_cost),
    contract_duration = VALUES(contract_duration),
    contract_value = VALUES(contract_value),
    contract_date = VALUES(contract_date),
    ingaz_from = VALUES(ingaz_from),
    ingaz_to = VALUES(ingaz_to),
    msaned_number = VALUES(msaned_number),
    tafwed_number = VALUES(tafwed_number),
    maid_date_back = VALUES(maid_date_back),
    back_reasons = VALUES(back_reasons),
    arrival_date = VALUES(arrival_date),
    updated_at = VALUES(updated_at),
    created_at = COALESCE(created_at, VALUES(created_at))
SQL;

    $insertStmt = $newDb->prepare($insertSql);
    $newDb->beginTransaction();

    $processed = 0;
    $inserted = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($records as $row) {
        $processed++;

        $contractId = isset($row['id']) ? (int)$row['id'] : 0;
        if ($contractId <= 0) {
            $skipped++;
            continue;
        }

        try {
            $params = [
                ':contract_id' => $contractId,
                ':visa_number' => normalizeContractValue($row['contract_visa_num'] ?? null),
                ':visa_hijry_date' => normalizeHijriAwareDate($row['contract_visa_Hijri_date'] ?? null),
                ':visa_milad_date' => normalizeHijriAwareDate($row['contract_visa_date'] ?? null),
                ':monthly_cost' => normalizeContractFloat($row['contract_cost'] ?? null),
                ':contract_duration' => 2,
                ':contract_value' => normalizeContractFloat($row['contract_total'] ?? null),
                ':contract_date' => normalizeHijriAwareDate($row['date_contract'] ?? null),
                ':ingaz_from' => normalizeHijriAwareDate($row['contract_from_day'] ?? null),
                ':ingaz_to' => normalizeHijriAwareDate($row['contract_to_day'] ?? null),
                ':msaned_number' => normalizeContractValue($row['musand_num'] ?? null),
                ':tafwed_number' => normalizeContractValue($row['authorization_num'] ?? null),
                ':created_at' => normalizeContractDateTime($row['created_at'] ?? null),
                ':updated_at' => normalizeContractDateTime($row['updated_at'] ?? null),
                ':maid_date_back' => normalizeHijriAwareDate($row['throwback_date'] ?? null),
                ':back_reasons' => normalizeContractValue($row['throwback_reason'] ?? null),
                ':arrival_date' => normalizeHijriAwareDate($row['final_visa_date'] ?? null)
            ];

            $insertStmt->execute($params);
            $affected = $insertStmt->rowCount();

            if ($affected === 1) {
                $inserted++;
            } elseif ($affected === 2) {
                $updated++;
            }
        } catch (Exception $e) {
            $skipped++;
            error_log('?? ????? ??????? contract_infos ??? #' . $contractId . ': ' . $e->getMessage());
        }
    }

    $newDb->commit();

    return [
        'success' => true,
        'message' => '?? ??? ???????? ??????? ?????',
        'processed' => $processed,
        'inserted' => $inserted,
        'updated' => $updated,
        'skipped' => $skipped
    ];
}

function getContractInfosSummary() {
    try {
        $db = get_db_connection();
        $infos = $db->query('SELECT COUNT(*) AS total FROM contract_infos')->fetch(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'contract_infos' => (int)($infos['total'] ?? 0),
            'message' => '?? ??? ???????? ??????? ����???'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => '??? ?? ??? ???????? contract_infos: ' . $e->getMessage()
        ];
    }
}

function getContractsSummary() {
    try {
        $db = get_db_connection();
        $contracts = $db->query('SELECT COUNT(*) AS total FROM contracts')->fetch(PDO::FETCH_ASSOC);
        $infos = $db->query('SELECT COUNT(*) AS total FROM contract_infos')->fetch(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'contracts' => (int)($contracts['total'] ?? 0),
            'contract_infos' => (int)($infos['total'] ?? 0),
            'message' => 'تم جلب إحصائيات العقود'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في جلب الإحصائيات: ' . $e->getMessage()
        ];
    }
}

function normalizeWarrantyTimestamp($value) {
    $date = normalizeHijriAwareDate($value);
    if ($date === null) {
        return null;
    }

    return $date . ' 00:00:00';
}

function determineWarrantyStatus($startValue, $endValue) {
    $startDateString = normalizeContractDateTime($startValue);
    $endDateString = normalizeContractDateTime($endValue);

    if ($startDateString === null || $endDateString === null) {
        return 'not_applicable';
    }

    try {
        $startDate = new DateTime($startDateString);
        $endDate = new DateTime($endDateString);
        $days = (int)$startDate->diff($endDate)->format('%a');

        if ($days < 1) {
            return 'expired';
        }

        if ($days < 60 && $days > 1) {
            return 'applicable';
        }

        return 'not_applicable';
    } catch (Exception $e) {
        return 'not_applicable';
    }
}

// function mapReservationStatus($value) {
//     // 5 => 'under_process',
//     //     6 => 'contracted',
//     //     7 => 'new',
//     //     8 => 'contracted_without_contract',
//     //     9, 10, 11 => 'under_process',
//     //     13 => 'return',
//     //     15 => 'done',
//     //     16 => 'refused',
//     //     default => 'new',
//     $statusMap = [
//         7 => 'new',
//         8 => 'msaned',
//         9 => 'tafwed',
//         10 => 'taffez',
//         11 => 'tazker',
//         15 => 'arrival',
//         3 => 'delegate_selected',
//         13 => 'back',
//         12 => 'under_warranty',
//         4 => 'client_contracted',
//         14 => 'in_progress'
//     ];

//     $normalizedValue = normalizeContractValue($value);
//     if ($normalizedValue === null) {
//         return null;
//     }

//     if (is_numeric($normalizedValue)) {
//         $intStatus = (int)$normalizedValue;
//         if (isset($statusMap[$intStatus])) {
//             return $statusMap[$intStatus];
//         }
//     }

//     $lowered = strtolower($normalizedValue);
//     if (in_array($lowered, $statusMap, true)) {
//         return $lowered;
//     }

//     return null;
// }

function normalizeContractValue($value) {
    if ($value === null) {
        return null;
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '' || strtoupper($trimmed) === 'NULL') {
            return null;
        }
        return $trimmed;
    }

    if (is_numeric($value)) {
        return (string)$value;
    }

    return null;
}

function normalizeContractInt($value) {
    $normalized = normalizeContractValue($value);
    if ($normalized === null) {
        return null;
    }

    $intValue = (int)$normalized;
    return $intValue > 0 ? $intValue : null;
}

function normalizeContractFloat($value) {
    $normalized = normalizeContractValue($value);
    if ($normalized === null) {
        return null;
    }

    if (is_string($normalized)) {
        $normalized = str_replace(',', '', $normalized);
    }

    return is_numeric($normalized) ? (float)$normalized : null;
}

function normalizeContractDateTime($value) {
    $normalized = normalizeContractValue($value);
    if ($normalized === null) {
        return null;
    }

    return parseDateValue($normalized);
}

function normalizeContractDate($value) {
    $dateTime = normalizeContractDateTime($value);
    return $dateTime ? substr($dateTime, 0, 10) : null;
}


function normalizeHijriAwareDate($value) {
    $normalizedValue = normalizeContractValue($value);
    if ($normalizedValue === null) {
        return null;
    }

    $asciiValue = normalizeArabicIndicDigits($normalizedValue);

    $parsedDate = normalizeContractDate($asciiValue);
    if ($parsedDate !== null) {
        return $parsedDate;
    }

    return convertHijriStringToDate($asciiValue);
}
function convertHijriStringToDate($value) {
    $normalized = normalizeContractValue($value);
    if ($normalized === null) {
        return null;
    }

    $normalized = normalizeArabicIndicDigits($normalized);
    $sanitized = preg_replace('/[^\d]+/u', ' ', $normalized);
    $parts = preg_split('/\s+/', trim($sanitized));

    if (count($parts) < 3) {
        return null;
    }

    $numbers = array_map('intval', array_slice($parts, 0, 3));

    $year = null;
    $month = null;
    $day = null;

    if ($numbers[0] >= 1200 && $numbers[0] <= 1600) {
        $year = $numbers[0];
        $month = $numbers[1] ?? null;
        $day = $numbers[2] ?? null;
    } elseif ($numbers[2] >= 1200 && $numbers[2] <= 1600) {
        $year = $numbers[2];
        $day = $numbers[0];
        $month = $numbers[1] ?? null;
    } elseif ($numbers[1] >= 1200 && $numbers[1] <= 1600) {
        $year = $numbers[1];
        $day = $numbers[0];
        $month = $numbers[2] ?? null;
    } else {
        $year = $numbers[2];
        $day = $numbers[0];
        $month = $numbers[1] ?? null;
    }

    if ($month !== null && ($month < 1 || $month > 12) && $day !== null && $day >= 1 && $day <= 12) {
        [$day, $month] = [$month, $day];
    }

    if ($year === null || $month === null || $day === null) {
        return null;
    }

    $month = max(1, min(12, $month));
    $day = max(1, min(30, $day));

    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}
function normalizeArabicIndicDigits($value) {
    $arabicIndic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩','۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $western = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'];

    return str_replace($arabicIndic, $western, $value);
}

function migrateAccommodationTypes() {
    $oldHost = $_POST['old_db_host'] ?? '127.0.0.1';
    $oldPort = $_POST['old_db_port'] ?? 8889;
    $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
    $oldUser = $_POST['old_db_user'] ?? 'root';
    $oldPass = $_POST['old_db_pass'] ?? 'root';

    try {
        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة الجديدة: ' . $e->getMessage()
        ];
    }

    try {
        $oldDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $oldHost, $oldPort, $oldDbName);
        $oldDb = new PDO($oldDsn, $oldUser, $oldPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة القديمة: ' . $e->getMessage()
        ];
    }

    try {
        $createSql = "CREATE TABLE IF NOT EXISTS accommodation_types (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $newDb->exec($createSql);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل في تجهيز جدول accommodation_types: ' . $e->getMessage()
        ];
    }

    try {
        $rows = $oldDb->query('SELECT id, name, created_at, updated_at FROM accommodation_types ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'لم يتمكن من قراءة جدول accommodation_types من القاعدة القديمة: ' . $e->getMessage()
        ];
    }

    if (empty($rows)) {
        return [
            'success' => true,
            'message' => 'لا توجد بيانات في جدول accommodation_types في القاعدة القديمة',
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0
        ];
    }

    $insertSql = "INSERT INTO accommodation_types (id, name, created_at, updated_at)
                  VALUES (:id, :name, :created_at, :updated_at)
                  ON DUPLICATE KEY UPDATE
                      name = VALUES(name),
                      updated_at = VALUES(updated_at)";

    $stmt = $newDb->prepare($insertSql);
    $newDb->beginTransaction();

    $processed = 0;
    $inserted = 0;
    $updated = 0;
    $unchanged = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $processed++;
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        if ($id <= 0) {
            $skipped++;
            continue;
        }

        $params = [
            ':id' => $id,
            ':name' => trim((string)($row['name'] ?? '')),
            ':created_at' => parseDateValue($row['created_at'] ?? null),
            ':updated_at' => parseDateValue($row['updated_at'] ?? null)
        ];

        if ($params[':name'] === '') {
            $skipped++;
            continue;
        }

        try {
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            if ($affected === 1) {
                $inserted++;
            } elseif ($affected === 2) {
                $updated++;
            } else {
                $unchanged++;
            }
        } catch (Exception $e) {
            $skipped++;
            error_log('خطأ في نقل accommodation_type #' . $id . ': ' . $e->getMessage());
        }
    }

    $newDb->commit();

    return [
        'success' => true,
        'message' => 'تم نقل أنواع الإيواء بنجاح',
        'processed' => $processed,
        'inserted' => $inserted,
        'updated' => $updated,
        'unchanged' => $unchanged,
        'skipped' => $skipped
    ];
}

function getAccommodationTypesSummary() {
    try {
        $db = get_db_connection();
        $total = $db->query('SELECT COUNT(*) AS total FROM accommodation_types')->fetch(PDO::FETCH_ASSOC);
        return [
            'success' => true,
            'types' => (int)($total['total'] ?? 0),
            'message' => 'تم جلب إحصائيات أنواع الإيواء'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في جلب إحصائيات أنواع الإيواء: ' . $e->getMessage()
        ];
    }
}

function migrateAirports() {
    $oldHost = $_POST['old_db_host'] ?? '127.0.0.1';
    $oldPort = $_POST['old_db_port'] ?? 8889;
    $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
    $oldUser = $_POST['old_db_user'] ?? 'root';
    $oldPass = $_POST['old_db_pass'] ?? 'root';

    try {
        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة الجديدة: ' . $e->getMessage()
        ];
    }

    try {
        $oldDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $oldHost, $oldPort, $oldDbName);
        $oldDb = new PDO($oldDsn, $oldUser, $oldPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة القديمة: ' . $e->getMessage()
        ];
    }

    try {
        $createSql = "CREATE TABLE IF NOT EXISTS airports (\n            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n            name VARCHAR(255) NOT NULL,\n            country_id BIGINT UNSIGNED NOT NULL,\n            city_id BIGINT UNSIGNED NOT NULL,\n            created_at TIMESTAMP NULL DEFAULT NULL,\n            updated_at TIMESTAMP NULL DEFAULT NULL,\n            PRIMARY KEY (id)\n        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $newDb->exec($createSql);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل في تجهيز جدول airports: ' . $e->getMessage()
        ];
    }

    try {
        $rows = $oldDb->query('SELECT id, name, country_id, city_id, created_at, updated_at FROM airports ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'لم يتمكن من قراءة جدول airports من القاعدة القديمة: ' . $e->getMessage()
        ];
    }

    if (empty($rows)) {
        return [
            'success' => true,
            'message' => 'لا توجد بيانات في جدول airports في القاعدة القديمة',
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0
        ];
    }

    $insertSql = "INSERT INTO airports (id, name, country_id, city_id, created_at, updated_at)\n                  VALUES (:id, :name, :country_id, :city_id, :created_at, :updated_at)\n                  ON DUPLICATE KEY UPDATE\n                      name = VALUES(name),\n                      country_id = VALUES(country_id),\n                      city_id = VALUES(city_id),\n                      updated_at = VALUES(updated_at)";

    $stmt = $newDb->prepare($insertSql);

    $processed = 0;
    $inserted = 0;
    $updated = 0;
    $unchanged = 0;
    $skipped = 0;

    try {
        $newDb->beginTransaction();

        foreach ($rows as $row) {
            $processed++;

            $id = isset($row['id']) ? (int)$row['id'] : 0;
            $countryId = isset($row['country_id']) ? (int)$row['country_id'] : 0;
            $cityId = isset($row['city_id']) ? (int)$row['city_id'] : 0;
            $name = trim((string)($row['name'] ?? ''));

            if ($id <= 0 || $countryId <= 0 || $cityId <= 0 || $name === '') {
                $skipped++;
                continue;
            }

            $params = [
                ':id' => $id,
                ':name' => $name,
                ':country_id' => $countryId,
                ':city_id' => $cityId,
                ':created_at' => parseDateValue($row['created_at'] ?? null),
                ':updated_at' => parseDateValue($row['updated_at'] ?? null)
            ];

            try {
                $stmt->execute($params);
                $affected = $stmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            } catch (Exception $e) {
                $skipped++;
                error_log('خطأ في نقل airport #' . $id . ': ' . $e->getMessage());
            }
        }

        $newDb->commit();
    } catch (Exception $e) {
        if ($newDb->inTransaction()) {
            $newDb->rollBack();
        }
        return [
            'success' => false,
            'message' => 'فشل أثناء نقل المطارات: ' . $e->getMessage()
        ];
    }

    return [
        'success' => true,
        'message' => 'تم نقل المطارات بنجاح',
        'processed' => $processed,
        'inserted' => $inserted,
        'updated' => $updated,
        'unchanged' => $unchanged,
        'skipped' => $skipped
    ];
}

function getAirportsSummary() {
    try {
        $db = get_db_connection();
        $total = $db->query('SELECT COUNT(*) AS total FROM airports')->fetch(PDO::FETCH_ASSOC);
        return [
            'success' => true,
            'total' => (int)($total['total'] ?? 0),
            'airports' => (int)($total['total'] ?? 0),
            'message' => 'تم جلب إحصائيات المطارات'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في جلب إحصائيات المطارات: ' . $e->getMessage()
        ];
    }
}

function migrateContractLogs() {
    $oldHost = $_POST['old_db_host'] ?? '127.0.0.1';
    $oldPort = $_POST['old_db_port'] ?? 8889;
    $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
    $oldUser = $_POST['old_db_user'] ?? 'root';
    $oldPass = $_POST['old_db_pass'] ?? 'root';

    try {
        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة الجديدة: ' . $e->getMessage()
        ];
    }

    try {
        $oldDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $oldHost, $oldPort, $oldDbName);
        $oldDb = new PDO($oldDsn, $oldUser, $oldPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة القديمة: ' . $e->getMessage()
        ];
    }

    $statusMap = [
        '1' => 'new',
        '2' => 'approval',
        '3' => 'msaned',
        '4' => 'tafwed',
        '5' => 'taffez',
        '6' => 'tazker',
        '7' => 'completed',
        '8' => 'return'
    ];

    try {
        $rows = $oldDb->query('SELECT id, contract_id, user_id, status, note, date, created_at, updated_at FROM contract_logs ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'لم يتمكن من قراءة جدول العقد القديم: ' . $e->getMessage()
        ];
    }

    if (empty($rows)) {
        return [
            'success' => true,
            'message' => 'لا توجد بيانات في جدول contract_logs في القاعدة القديمة',
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0
        ];
    }

    $insertSql = "INSERT INTO contract_status_logs (id, contract_id, user_id, status, notes, date, created_at, updated_at)
                  VALUES (:id, :contract_id, :user_id, :status, :notes, :date, :created_at, :updated_at)
                  ON DUPLICATE KEY UPDATE
                      contract_id = VALUES(contract_id),
                      user_id = VALUES(user_id),
                      status = VALUES(status),
                      notes = VALUES(notes),
                      date = VALUES(date),
                      created_at = VALUES(created_at),
                      updated_at = VALUES(updated_at)";

    $stmt = $newDb->prepare($insertSql);

    $processed = 0;
    $inserted = 0;
    $updated = 0;
    $unchanged = 0;
    $skipped = 0;

    try {
        $newDb->beginTransaction();

        foreach ($rows as $row) {
            $processed++;

            $id = isset($row['id']) ? (int)$row['id'] : 0;
            $contractId = isset($row['contract_id']) ? (int)$row['contract_id'] : 0;
            $userId = isset($row['user_id']) ? (int)$row['user_id'] : 0;
            $rawStatus = isset($row['status']) ? (string)$row['status'] : '';
            $status = $statusMap[$rawStatus] ?? 'unknown';
            $notes = trim((string)($row['note'] ?? ''));
            $dateValue = $row['date'] ?? null;
            $date = parseDateValue($dateValue);
            $date = $date ? substr($date, 0, 10) : null;
            $createdAt = parseDateValue($row['created_at'] ?? null);
            $updatedAt = parseDateValue($row['updated_at'] ?? null);

            if ($id <= 0 || $contractId <= 0 || $userId <= 0 || !$date) {
                $skipped++;
                continue;
            }

            $params = [
                ':id' => $id,
                ':contract_id' => $contractId,
                ':user_id' => $userId,
                ':status' => $status,
                ':notes' => $notes !== '' ? $notes : null,
                ':date' => $date,
                ':created_at' => $createdAt,
                ':updated_at' => $updatedAt
            ];

            try {
                $stmt->execute($params);
                $affected = $stmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            } catch (Exception $e) {
                $skipped++;
                error_log('خطأ في نقل contract_log #' . $id . ': ' . $e->getMessage());
            }
        }

        $newDb->commit();
    } catch (Exception $e) {
        if ($newDb->inTransaction()) {
            $newDb->rollBack();
        }
        return [
            'success' => false,
            'message' => 'فشل أثناء نقل سجلات العقود: ' . $e->getMessage()
        ];
    }

    return [
        'success' => true,
        'message' => 'تم نقل سجلات العقود بنجاح',
        'processed' => $processed,
        'inserted' => $inserted,
        'updated' => $updated,
        'unchanged' => $unchanged,
        'skipped' => $skipped
    ];
}

function migrateActivityLogs() {
    $oldHost = $_POST['old_db_host'] ?? '127.0.0.1';
    $oldPort = $_POST['old_db_port'] ?? 8889;
    $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
    $oldUser = $_POST['old_db_user'] ?? 'root';
    $oldPass = $_POST['old_db_pass'] ?? 'root';

    try {
        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة الجديدة: ' . $e->getMessage()
        ];
    }

    try {
        $oldDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $oldHost, $oldPort, $oldDbName);
        $oldDb = new PDO($oldDsn, $oldUser, $oldPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة القديمة: ' . $e->getMessage()
        ];
    }

    try {
        $rows = $oldDb->query('SELECT id, log_name, description, subject_id, subject_type, causer_id, causer_type, properties, created_at, updated_at FROM activity_log ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'لم يتمكن من قراءة جدول النشاط القديم: ' . $e->getMessage()
        ];
    }

    if (empty($rows)) {
        return [
            'success' => true,
            'message' => 'لا توجد بيانات في جدول activity_log في القاعدة القديمة',
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0
        ];
    }

    $insertSql = "INSERT INTO activity_log (id, log_name, description, subject_id, subject_type, causer_id, causer_type, properties, event, batch_uuid, created_at, updated_at)
                  VALUES (:id, :log_name, :description, :subject_id, :subject_type, :causer_id, :causer_type, :properties, :event, :batch_uuid, :created_at, :updated_at)
                  ON DUPLICATE KEY UPDATE
                      log_name = VALUES(log_name),
                      description = VALUES(description),
                      subject_id = VALUES(subject_id),
                      subject_type = VALUES(subject_type),
                      causer_id = VALUES(causer_id),
                      causer_type = VALUES(causer_type),
                      properties = VALUES(properties),
                      event = VALUES(event),
                      batch_uuid = VALUES(batch_uuid),
                      created_at = VALUES(created_at),
                      updated_at = VALUES(updated_at)";

    $stmt = $newDb->prepare($insertSql);

    $processed = 0;
    $inserted = 0;
    $updated = 0;
    $unchanged = 0;
    $skipped = 0;

    try {
        $newDb->beginTransaction();

        foreach ($rows as $row) {
            $processed++;

            $id = isset($row['id']) ? (int)$row['id'] : 0;
            if ($id <= 0) {
                $skipped++;
                continue;
            }

            $params = [
                ':id' => $id,
                ':log_name' => normalizeContractValue($row['log_name'] ?? null),
                ':description' => normalizeContractValue($row['description'] ?? null),
                ':subject_id' => normalizeContractInt($row['subject_id'] ?? null),
                ':subject_type' => normalizeContractValue($row['subject_type'] ?? null),
                ':causer_id' => normalizeContractInt($row['causer_id'] ?? null),
                ':causer_type' => normalizeContractValue($row['causer_type'] ?? null),
                ':properties' => $row['properties'] ?? null,
                ':event' => 'created',
                ':batch_uuid' => null,
                ':created_at' => normalizeContractDateTime($row['created_at'] ?? null),
                ':updated_at' => normalizeContractDateTime($row['updated_at'] ?? null)
            ];

            try {
                $stmt->execute($params);
                $affected = $stmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            } catch (Exception $e) {
                $skipped++;
                error_log('خطأ في نقل activity_log #' . $id . ': ' . $e->getMessage());
            }
        }

        $newDb->commit();
    } catch (Exception $e) {
        if ($newDb->inTransaction()) {
            $newDb->rollBack();
        }
        return [
            'success' => false,
            'message' => 'فشل أثناء نقل سجلات النشاط: ' . $e->getMessage()
        ];
    }

    return [
        'success' => true,
        'message' => 'تم نقل سجلات النشاط بنجاح',
        'processed' => $processed,
        'inserted' => $inserted,
        'updated' => $updated,
        'unchanged' => $unchanged,
        'skipped' => $skipped
    ];
}

function migrateMessages() {
    $oldHost = $_POST['old_db_host'] ?? '127.0.0.1';
    $oldPort = $_POST['old_db_port'] ?? 8889;
    $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
    $oldUser = $_POST['old_db_user'] ?? 'root';
    $oldPass = $_POST['old_db_pass'] ?? 'root';

    try {
        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة الجديدة: ' . $e->getMessage()
        ];
    }

    try {
        $oldDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $oldHost, $oldPort, $oldDbName);
        $oldDb = new PDO($oldDsn, $oldUser, $oldPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بالقاعدة القديمة: ' . $e->getMessage()
        ];
    }

    try {
        $rows = $oldDb->query('SELECT id, conversation_id, user_id, from_user, message, files, created_at, updated_at FROM messages ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'لم يتمكن من قراءة جدول الرسائل في القاعدة القديمة: ' . $e->getMessage()
        ];
    }

    if (empty($rows)) {
        return [
            'success' => true,
            'message' => 'لا توجد رسائل لنقلها',
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0
        ];
    }

    $insertSql = "INSERT INTO messages (id, conversation_id, user_id, from_user, message, files, created_at, updated_at)
                  VALUES (:id, :conversation_id, :user_id, :from_user, :message, :files, :created_at, :updated_at)
                  ON DUPLICATE KEY UPDATE
                      conversation_id = VALUES(conversation_id),
                      user_id = VALUES(user_id),
                      from_user = VALUES(from_user),
                      message = VALUES(message),
                      files = VALUES(files),
                      created_at = VALUES(created_at),
                      updated_at = VALUES(updated_at)";

    $stmt = $newDb->prepare($insertSql);

    $processed = 0;
    $inserted = 0;
    $updated = 0;
    $unchanged = 0;
    $skipped = 0;

    try {
        $newDb->beginTransaction();

        foreach ($rows as $row) {
            $processed++;

            $id = isset($row['id']) ? (int)$row['id'] : 0;
            if ($id <= 0) {
                $skipped++;
                continue;
            }

            $params = [
                ':id' => $id,
                ':conversation_id' => normalizeContractInt($row['conversation_id'] ?? null),
                ':user_id' => normalizeContractInt($row['user_id'] ?? null),
                ':from_user' => normalizeContractValue($row['from_user'] ?? null),
                ':message' => $row['message'] ?? null,
                ':files' => $row['files'] ?? null,
                ':created_at' => normalizeContractDateTime($row['created_at'] ?? null),
                ':updated_at' => normalizeContractDateTime($row['updated_at'] ?? null)
            ];

            try {
                $stmt->execute($params);
                $affected = $stmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            } catch (Exception $e) {
                $skipped++;
                error_log('خطأ في نقل message #' . $id . ': ' . $e->getMessage());
            }
        }

        $newDb->commit();
    } catch (Exception $e) {
        if ($newDb->inTransaction()) {
            $newDb->rollBack();
        }
        return [
            'success' => false,
            'message' => 'فشل أثناء نقل الرسائل: ' . $e->getMessage()
        ];
    }

    return [
        'success' => true,
        'message' => 'تم نقل الرسائل بنجاح',
        'processed' => $processed,
        'inserted' => $inserted,
        'updated' => $updated,
        'unchanged' => $unchanged,
        'skipped' => $skipped
    ];
}

function getMessagesSummary() {
    try {
        $db = get_db_connection();
        $total = $db->query('SELECT COUNT(*) AS total FROM messages')->fetch(PDO::FETCH_ASSOC);
        return [
            'success' => true,
            'total' => (int)($total['total'] ?? 0),
            'messages' => (int)($total['total'] ?? 0),
            'message' => 'تم جلب إحصائيات الرسائل'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في جلب إحصائيات الرسائل: ' . $e->getMessage()
        ];
    }
}

function migrateContacts() {
    $oldHost = $_POST['old_db_host'] ?? '127.0.0.1';
    $oldPort = $_POST['old_db_port'] ?? 8889;
    $oldDbName = $_POST['old_db_name'] ?? 'platinum_old';
    $oldUser = $_POST['old_db_user'] ?? 'root';
    $oldPass = $_POST['old_db_pass'] ?? 'root';

    try {
        $newDb = get_db_connection();
        $newDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بقاعدة البيانات الجديدة: ' . $e->getMessage()
        ];
    }

    try {
        $oldDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $oldHost, $oldPort, $oldDbName);
        $oldDb = new PDO($oldDsn, $oldUser, $oldPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]);
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال بقاعدة البيانات القديمة: ' . $e->getMessage()
        ];
    }

    // Allow overriding old table name, and try common variants (contact_us / contacts_us)
    $requestedTable = $_POST['old_contacts_table'] ?? $_GET['old_contacts_table'] ?? 'contact_us';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $requestedTable)) {
        $requestedTable = 'contact_us';
    }
    $candidates = array_unique([$requestedTable, 'contact_us', 'contact_us']);

    $rows = [];
    $lastError = null;
    foreach ($candidates as $table) {
        if (!$table) {
            continue;
        }
        try {
            $rows = $oldDb->query("SELECT id, name, email, subject, massage, phone, status, created_at, updated_at FROM `{$table}` ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            $requestedTable = $table; // keep the table that worked
            break;
        } catch (Exception $e) {
            $lastError = $e;
            $rows = [];
        }
    }

    if ($rows === [] && $lastError !== null) {
        return [
            'success' => false,
            'message' => 'لم يمكن قراءة بيانات contact_us/contact_us من القاعدة القديمة: ' . $lastError->getMessage()
        ];
    }

    if (empty($rows)) {
        return [
            'success' => true,
            'message' => "لا توجد بيانات في {$requestedTable} للنقل",
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0
        ];
    }

    // Build column list based on actual target table structure to avoid column mismatches
    $targetColumns = getTableColumnsLocal($newDb, 'contacts');
    if (empty($targetColumns)) {
        return [
            'success' => false,
            'message' => 'جدول contacts غير موجود أو لا يمكن قراءته في القاعدة الجديدة'
        ];
    }

    $mapping = [
        'id' => 'id',
        'name' => 'name',
        'email' => 'email',
        'subject' => 'subject',
        'message' => ['massage', 'message'], // massage في القديم قد تكون message
        'phone' => 'phone',
        'is_contacted' => 'status',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at'
    ];

    $columnsToUse = array_values(array_intersect($targetColumns, array_keys($mapping)));
    if (!in_array('id', $columnsToUse, true)) {
        return [
            'success' => false,
            'message' => 'جدول contacts لا يحتوي على عمود id، لا يمكن الترحيل'
        ];
    }

    $sql = buildUpsertSql('contacts', $columnsToUse);
    $stmt = $newDb->prepare($sql);

    $processed = 0;
    $inserted = 0;
    $updated = 0;
    $unchanged = 0;
    $skipped = 0;

    $errors = [];
    try {
        $newDb->beginTransaction();

        foreach ($rows as $row) {
            $processed++;

            $id = isset($row['id']) ? (int)$row['id'] : 0;
            if ($id <= 0) {
                $skipped++;
                continue;
            }

            $data = [];
            foreach ($columnsToUse as $col) {
                switch ($col) {
                    case 'id':
                        $data[$col] = $id;
                        break;
                    case 'message':
                        $data[$col] = $row['massage'] ?? ($row['message_alt'] ?? ($row['message'] ?? null));
                        break;
                    case 'is_contacted':
                        $data[$col] = isset($row['status']) ? (int)$row['status'] : 0;
                        break;
                    case 'created_at':
                    case 'updated_at':
                        $data[$col] = normalizeContractDateTime($row[$col] ?? null);
                        break;
                    default:
                        $sourceKey = is_array($mapping[$col]) ? ($mapping[$col][0] ?? null) : $mapping[$col];
                        $value = $sourceKey && isset($row[$sourceKey]) ? $row[$sourceKey] : ($row[$col] ?? null);
                        // fallback to empty string for required string columns to avoid NOT NULL errors
                        if ($value === null && in_array($col, ['name', 'email', 'subject', 'message', 'phone'], true)) {
                            $value = '';
                        }
                        $data[$col] = $value;
                        break;
                }
            }

            try {
                $stmt->execute(prefixPlaceholders($columnsToUse, $data));
                $affected = $stmt->rowCount();
                if ($affected === 1) {
                    $inserted++;
                } elseif ($affected === 2) {
                    $updated++;
                } else {
                    $unchanged++;
                }
            } catch (Exception $e) {
                $skipped++;
                if (count($errors) < 5) {
                    $errors[] = "ID {$id}: " . $e->getMessage();
                }
                error_log('خطأ أثناء إدراج contact_us #' . $id . ': ' . $e->getMessage());
            }
        }

        $newDb->commit();
    } catch (Exception $e) {
        if ($newDb->inTransaction()) {
            $newDb->rollBack();
        }
        return [
            'success' => false,
            'message' => 'تعذر إتمام ترحيل contact_us: ' . $e->getMessage()
        ];
    }

    if ($errors) {
        $errorMsg = 'واجهنا أخطاء في بعض السجلات (' . count($errors) . ' من أوائلها): ' . implode(' | ', $errors);
    }

    return [
        'success' => true,
        'message' => 'تم نقل contact_us إلى contacts بنجاح' . (!empty($errorMsg) ? " — {$errorMsg}" : ''),
        'processed' => $processed,
        'inserted' => $inserted,
        'updated' => $updated,
        'unchanged' => $unchanged,
        'skipped' => $skipped
    ];
}

function getContactsSummary() {
    try {
        $db = get_db_connection();
        $total = $db->query('SELECT COUNT(*) AS total FROM contacts')->fetch(PDO::FETCH_ASSOC);
        return [
            'success' => true,
            'total' => (int)($total['total'] ?? 0),
            'contacts' => (int)($total['total'] ?? 0),
            'message' => 'تم جلب إحصائيات contacts بنجاح'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'تعذر جلب إحصائيات contacts: ' . $e->getMessage()
        ];
    }
}


function getActivityLogsSummary() {
    try {
        $db = get_db_connection();
        $total = $db->query('SELECT COUNT(*) AS total FROM activity_log')->fetch(PDO::FETCH_ASSOC);
        return [
            'success' => true,
            'total' => (int)($total['total'] ?? 0),
            'activity_logs' => (int)($total['total'] ?? 0),
            'message' => 'تم جلب إحصائيات سجلات النشاط'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في جلب إحصائيات سجلات النشاط: ' . $e->getMessage()
        ];
    }
}


function getContractLogsSummary() {
    try {
        $db = get_db_connection();
        $total = $db->query('SELECT COUNT(*) AS total FROM contract_status_logs')->fetch(PDO::FETCH_ASSOC);
        return [
            'success' => true,
            'total' => (int)($total['total'] ?? 0),
            'contract_logs' => (int)($total['total'] ?? 0),
            'message' => 'تم جلب إحصائيات سجلات العقود'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في جلب إحصائيات سجلات العقود: ' . $e->getMessage()
        ];
    }
}

?>




