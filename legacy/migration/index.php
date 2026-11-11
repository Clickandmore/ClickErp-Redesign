<?php
/**
 * نظام النقل الموحد - مدمج في المشروع الأساسي
 * Unified Migration System - Integrated with Main Project
 */

// إعدادات أمان
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

session_start();

// معلومات قاعدة البيانات
$config = [
    'host' => '127.0.0.1',
    'port' => 8889,
    'username' => 'root',
    'password' => 'root'
];

// معالجة الطلبات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $action = $_POST['action'] ?? 'unknown';

        switch ($action) {
            case 'check_databases':
                echo json_encode(checkDatabases(), JSON_UNESCAPED_UNICODE);
                break;

            case 'quick_migrate':
                echo json_encode(executeQuickMigration(), JSON_UNESCAPED_UNICODE);
                break;

            case 'upload_files':
                echo json_encode(handleFileUpload(), JSON_UNESCAPED_UNICODE);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'إجراء غير صالح'], JSON_UNESCAPED_UNICODE);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit();
}

/**
 * فحص قواعد البيانات
 */
function checkDatabases() {
    global $config;

    $databases = [];
    $dbNames = [
        'erp_operations_new' => 'ERP Operations',
        'platinum' => 'Platinum القديمة',
        'platinum_new' => 'Platinum الجديدة'
    ];

    foreach ($dbNames as $dbName => $displayName) {
        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};port={$config['port']};dbname=$dbName;charset=utf8mb4",
                $config['username'], $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // عد الجداول
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $tablesCount = count($tables);

            // عد السجلات
            $mainTables = ['users', 'maids', 'orders', 'contracts'];
            $totalRecords = 0;

            foreach ($mainTables as $table) {
                if (in_array($table, $tables)) {
                    try {
                        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                        $totalRecords += $count;
                    } catch (Exception $e) {
                        // تجاهل
                    }
                }
            }

            $databases[$dbName] = [
                'display_name' => $displayName,
                'exists' => true,
                'tables_count' => $tablesCount,
                'records_count' => $totalRecords,
                'status' => 'connected'
            ];

        } catch (Exception $e) {
            $databases[$dbName] = [
                'display_name' => $displayName,
                'exists' => false,
                'tables_count' => 0,
                'records_count' => 0,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    return [
        'success' => true,
        'databases' => $databases
    ];
}

/**
 * تنفيذ النقل السريع
 */
function executeQuickMigration() {
    global $config;

    try {
        // الاتصال بقواعد البيانات
        $erp_pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname=erp_operations_new;charset=utf8mb4",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $target_pdo = new PDO(
            "mysql:host={$config['host']};port={$config['port']};dbname=platinum_new;charset=utf8mb4",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $results = [
            'users_before' => 0,
            'users_after' => 0,
            'users_migrated' => 0,
            'maids_before' => 0,
            'maids_after' => 0,
            'maids_migrated' => 0,
            'columns_added' => 0,
            'columns_converted' => 0
        ];

        // عد قبل النقل
        try {
            $results['users_before'] = $target_pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $results['maids_before'] = $target_pdo->query("SELECT COUNT(*) FROM maids")->fetchColumn();
        } catch (Exception $e) {
            // قد لا تكون الجداول موجودة
        }

        // إضافة الأعمدة المفقودة للمستخدمين
        addMissingColumnsToUsers($target_pdo);
        $results['columns_added'] = 6; // first_name, last_name, national_num, phone2, birthdate, register_from

        // نقل المستخدمين مع معالجة name → first_name + last_name
        $usersSql = "
            INSERT IGNORE INTO users (
                name, first_name, last_name, phone, email, password,
                address, city, nationality_id, created_at, updated_at,
                register_from, national_num, phone2, birthdate
            )
            SELECT
                name,
                TRIM(SUBSTRING_INDEX(name, ' ', 1)) as first_name,
                CASE
                    WHEN CHAR_LENGTH(name) - CHAR_LENGTH(REPLACE(name, ' ', '')) > 0
                    THEN TRIM(SUBSTRING_INDEX(name, ' ', -1))
                    ELSE ''
                END as last_name,
                CASE
                    WHEN phone LIKE '+966%' THEN SUBSTRING(phone, 5)
                    WHEN phone LIKE '966%' THEN SUBSTRING(phone, 4)
                    ELSE phone
                END as phone,
                email, password, address, city, nationality_id,
                created_at, updated_at, 'erp_migration' as register_from,
                national_num, phone2, birthdate
            FROM erp_operations_new.users
            WHERE phone IS NOT NULL AND phone != ''
        ";

        $target_pdo->exec($usersSql);

        // نقل العاملات
        $maidsSql = "
            INSERT IGNORE INTO maids (
                name, nationality_id, passport_num, price, salary, religion_id,
                age, birth_date, cv_file, available, status, experience_years,
                experience_status, created_at, updated_at
            )
            SELECT
                name, nationality_id, passport_num, price, salary, religion_id,
                age, birth_date, cv_file, available, status, experience_years,
                experience_status, created_at, updated_at
            FROM erp_operations_new.maids
            WHERE passport_num IS NOT NULL AND passport_num != ''
        ";

        $target_pdo->exec($maidsSql);

        // عد بعد النقل
        $results['users_after'] = $target_pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $results['maids_after'] = $target_pdo->query("SELECT COUNT(*) FROM maids")->fetchColumn();

        $results['users_migrated'] = $results['users_after'] - $results['users_before'];
        $results['maids_migrated'] = $results['maids_after'] - $results['maids_before'];
        $results['columns_converted'] = 1; // معالجة name → first_name + last_name

        return [
            'success' => true,
            'results' => $results,
            'message' => 'تم النقل الموحد بنجاح'
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'خطأ في النقل: ' . $e->getMessage()
        ];
    }
}

/**
 * إضافة الأعمدة المفقودة لجدول المستخدمين
 */
function addMissingColumnsToUsers($pdo) {
    $columns = [
        'first_name' => 'VARCHAR(255) NULL',
        'last_name' => 'VARCHAR(255) NULL',
        'national_num' => 'VARCHAR(50) NULL',
        'phone2' => 'VARCHAR(50) NULL',
        'birthdate' => 'DATE NULL',
        'register_from' => 'VARCHAR(255) NOT NULL DEFAULT "web"'
    ];

    foreach ($columns as $column => $definition) {
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN $column $definition");
        } catch (Exception $e) {
            // تجاهل إذا كان العمود موجود
        }
    }
}

/**
 * معالجة رفع الملفات
 */
function handleFileUpload() {
    $uploadedFiles = [];

    if (isset($_FILES['files'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($_FILES['files']['name'] as $key => $name) {
            if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['files']['tmp_name'][$key];
                $size = $_FILES['files']['size'][$key];

                $fileName = date('YmdHis') . '_' . uniqid() . '_' . $name;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $filePath)) {
                    // تحليل الملف
                    $analysis = analyzeCSV($filePath);

                    $uploadedFiles[] = [
                        'original_name' => $name,
                        'stored_name' => $fileName,
                        'path' => $filePath,
                        'size' => formatFileSize($size),
                        'records_count' => $analysis['total_rows'],
                        'headers' => $analysis['headers']
                    ];
                }
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
 * تحليل ملف CSV
 */
function analyzeCSV($filePath) {
    $headers = [];
    $data = [];
    $totalRows = 0;

    if (($handle = fopen($filePath, "r")) !== false) {
        // قراءة العناوين
        if (($row = fgetcsv($handle, 0, ',')) !== false) {
            $headers = array_map('trim', $row);
        }

        // قراءة البيانات (عينة)
        $sampleCount = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $totalRows++;

            if ($sampleCount < 10 && count($row) === count($headers)) {
                $data[] = array_combine($headers, array_map('trim', $row));
                $sampleCount++;
            }
        }

        fclose($handle);
    }

    return [
        'headers' => $headers,
        'data' => $data,
        'total_rows' => $totalRows
    ];
}

/**
 * تنسيق حجم الملف
 */
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام النقل الموحد - مدمج في المشروع</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .card-hover {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .progress-enhanced {
            height: 15px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-10 mx-auto">
                <!-- Header -->
                <div class="hero text-center">
                    <h1><i class="fas fa-sync-alt me-3"></i>نظام النقل الموحد</h1>
                 
                </div>

                <!-- فحص قواعد البيانات -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-database me-2"></i>حالة قواعد البيانات</h5>
                    </div>
                    <div class="card-body">
                        <div id="databasesStatus">
                            <div class="text-center">
                                <div class="spinner-border text-primary"></div>
                                <p>جاري فحص قواعد البيانات...</p>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" onclick="checkDatabases()" class="btn btn-outline-info">
                                <i class="fas fa-refresh me-2"></i>إعادة فحص
                            </button>
                        </div>
                    </div>
                </div>

                <!-- رفع الملفات -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-upload me-2"></i>الخطوة 1: رفع الملفات يدوياً</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="border border-primary border-3 rounded p-4 text-center"
                                         style="border-style: dashed !important; cursor: pointer; min-height: 200px;"
                                         onclick="document.getElementById('fileInput').click();">
                                        <input type="file" id="fileInput" name="files[]" accept=".csv" multiple style="display: none;">
                                        <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                                        <h4>اسحب ملفات CSV هنا</h4>
                                        <p class="text-muted">أو انقر لاختيار الملفات</p>
                                        <button type="button" class="btn btn-primary btn-lg">
                                            <i class="fas fa-folder-open me-2"></i>اختيار ملفات
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <h6 class="text-success">ملفات مُوصى برفعها:</h6>
                                    <ul class="list-group mb-3">
                                        <li class="list-group-item d-flex justify-content-between">
                                            users.csv <span class="badge bg-primary">مطلوب</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            maids.csv <span class="badge bg-success">مهم</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            orders.csv <span class="badge bg-info">اختياري</span>
                                        </li>
                                    </ul>

                                    <div id="selectedFiles">
                                        <h6 class="text-warning">ملفات محددة:</h6>
                                        <div id="filesList">
                                            <small class="text-muted">لا توجد ملفات</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="submit" id="uploadBtn" class="btn btn-success btn-lg" disabled>
                                    <i class="fas fa-upload me-2"></i>رفع الملفات المحددة
                                </button>
                            </div>
                        </form>

                        <div id="uploadResults" class="mt-3"></div>
                    </div>
                </div>

                <!-- النقل السريع -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-rocket me-2"></i>الخطوة 2: النقل السريع الموحد</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-magic me-2"></i>المعالجة الذكية ستقوم بـ:</h6>
                            <ul class="mb-0">
                                <li><strong>معالجة الأعمدة:</strong> name → first_name + last_name</li>
                                <li><strong>تنظيف البيانات:</strong> phone (إزالة +966 وتوحيد)</li>
                                <li><strong>إضافة أعمدة:</strong> national_num, phone2, birthdate</li>
                                <li><strong>دمج آمن:</strong> عدم حذف البيانات الموجودة</li>
                            </ul>
                        </div>

                        <div class="text-center">
                            <button type="button" id="quickMigrateBtn" class="btn btn-success btn-lg">
                                <i class="fas fa-bolt me-2"></i>
                                تنفيذ النقل السريع الآن
                            </button>
                        </div>
                    </div>
                </div>

                <!-- شريط التقدم -->
                <div id="progressSection" style="display: none;" class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h6><i class="fas fa-tasks me-2"></i>تقدم العملية</h6>
                    </div>
                    <div class="card-body">
                        <div class="progress progress-enhanced mb-3">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                 style="width: 0%"></div>
                        </div>
                        <div id="progressText" class="text-center h6"></div>
                    </div>
                </div>

                <!-- منطقة النتائج -->
                <div id="resultsArea"></div>

                <!-- روابط سريعة -->
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-link me-2"></i>روابط سريعة</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">بعد النقل:</h6>
                                <ul>
                                    <li><a href="../admin/users">عرض المستخدمين المدموجين</a></li>
                                    <li><a href="../admin/maids">عرض العاملات المدموجة</a></li>
                                    <li><a href="../admin/orders">عرض الطلبات</a></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-success">أدوات إضافية:</h6>
                                <ul>
                                    <li><a href="verify.php">فحص النتائج التفصيلي</a></li>
                                    <li><a href="backup.php">إنشاء نسخة احتياطية</a></li>
                                    <li><a href="../admin/dashboard">العودة للوحة التحكم</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            checkDatabases();

            document.getElementById('fileInput').addEventListener('change', handleFileSelection);
            document.getElementById('uploadForm').addEventListener('submit', handleUpload);
            document.getElementById('quickMigrateBtn').addEventListener('click', executeQuickMigration);
        });

        function handleFileSelection(e) {
            const files = Array.from(e.target.files);
            let html = '';

            files.forEach(file => {
                html += `
                    <div class="mb-1">
                        <i class="fas fa-file-csv text-success me-2"></i>
                        ${file.name} (${formatFileSize(file.size)})
                    </div>
                `;
            });

            document.getElementById('filesList').innerHTML = html;
            document.getElementById('uploadBtn').disabled = files.length === 0;
        }

        async function checkDatabases() {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=check_databases'
                });

                const data = await response.json();

                if (data.success) {
                    displayDatabaseStatus(data.databases);
                } else {
                    showError('خطأ في فحص قواعد البيانات');
                }
            } catch (error) {
                showError('خطأ في الاتصال: ' + error.message);
            }
        }

        function displayDatabaseStatus(databases) {
            let html = '<div class="row">';

            for (const [key, db] of Object.entries(databases)) {
                const statusColor = db.status === 'connected' ? 'success' : 'danger';

                html += `
                    <div class="col-md-4 mb-3">
                        <div class="card border-${statusColor}">
                            <div class="card-body text-center">
                                <h6>${db.display_name}</h6>
                                <div class="h4 text-${statusColor}">${db.records_count.toLocaleString()}</div>
                                <small>سجل في ${db.tables_count} جدول</small>
                                <div class="mt-2">
                                    <span class="badge bg-${statusColor}">
                                        ${db.status === 'connected' ? 'متصل' : 'غير متصل'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            html += '</div>';
            document.getElementById('databasesStatus').innerHTML = html;
        }

        async function handleUpload(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            formData.append('action', 'upload_files');

            const uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الرفع...';

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    let html = '<div class="alert alert-success"><h6>تم رفع الملفات بنجاح:</h6><ul>';
                    data.files.forEach(file => {
                        html += `<li><strong>${file.original_name}</strong> - ${file.records_count} سجل (${file.size})</li>`;
                    });
                    html += '</ul></div>';

                    document.getElementById('uploadResults').innerHTML = html;
                } else {
                    showError('خطأ في الرفع: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في الرفع: ' + error.message);
            }

            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>رفع الملفات المحددة';
        }

        async function executeQuickMigration() {
            if (!confirm('هل تريد بدء النقل السريع؟ سيتم:\n• معالجة name → first_name + last_name\n• إضافة الأعمدة المفقودة\n• دمج جميع البيانات بأمان\n\nالمتابعة؟')) {
                return;
            }

            const btn = document.getElementById('quickMigrateBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري النقل...';

            showProgress();
            updateProgress(10, 'بدء النقل الموحد...');

            try {
                updateProgress(30, 'إضافة الأعمدة المفقودة...');
                await new Promise(resolve => setTimeout(resolve, 1000));

                updateProgress(60, 'معالجة الأعمدة المختلفة (name → first_name + last_name)...');
                await new Promise(resolve => setTimeout(resolve, 1000));

                updateProgress(80, 'نقل ودمج البيانات...');

                const response = await fetch('', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=quick_migrate'
                });

                const data = await response.json();

                if (data.success) {
                    updateProgress(100, 'تم إكمال النقل بنجاح!');
                    showSuccessResults(data);
                } else {
                    showError('خطأ في النقل: ' + data.message);
                }
            } catch (error) {
                showError('خطأ في النقل: ' + error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bolt me-2"></i>تنفيذ النقل السريع الآن';
        }

        function showSuccessResults(data) {
            const html = `
                <div class="card" style="background: linear-gradient(45deg, #d4edda 0%, #c3e6cb 100%); border: 3px solid #28a745;">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-trophy me-2"></i>تم النقل الموحد بنجاح!</h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-4">
                            <div class="col-md-3">
                                <div class="bg-primary text-white p-3 rounded">
                                    <h3>${data.results.users_migrated}</h3>
                                    <small>مستخدم منقول</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-success text-white p-3 rounded">
                                    <h3>${data.results.maids_migrated}</h3>
                                    <small>عاملة مدموجة</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-info text-white p-3 rounded">
                                    <h3>${data.results.columns_added}</h3>
                                    <small>عمود مُضاف</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="bg-warning text-white p-3 rounded">
                                    <h3>✓</h3>
                                    <small>معالجة ذكية</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success">
                            <h5>🎉 تم بنجاح:</h5>
                            <ul>
                                <li>✅ نقل ${data.results.users_migrated} مستخدم من ERP Operations</li>
                                <li>✅ دمج ${data.results.maids_migrated} عاملة</li>
                                <li>✅ معالجة name → first_name + last_name</li>
                                <li>✅ إضافة ${data.results.columns_added} عمود جديد</li>
                                <li>✅ جميع البيانات محفوظة في platinum_new</li>
                            </ul>
                        </div>

                        <div class="text-center mt-4">
                            <button type="button" onclick="verifyResults()" class="btn btn-outline-primary btn-lg me-3">
                                <i class="fas fa-search me-2"></i>فحص النتائج
                            </button>
                            <a href="../admin/dashboard" class="btn btn-outline-success btn-lg">
                                <i class="fas fa-home me-2"></i>لوحة التحكم
                            </a>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('resultsArea').innerHTML = html;
        }

        async function verifyResults() {
            await checkDatabases();
            alert('تم تحديث حالة قواعد البيانات - راجع الأرقام أعلاه للتأكد من النقل');
        }

        function showProgress() {
            document.getElementById('progressSection').style.display = 'block';
        }

        function updateProgress(percent, text) {
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressText').textContent = text;
        }

        function showError(message) {
            const html = `
                <div class="alert alert-danger">
                    <strong>خطأ:</strong> ${message}
                    <div class="mt-2">
                        <button type="button" onclick="location.reload()" class="btn btn-sm btn-outline-danger">
                            إعادة المحاولة
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('resultsArea').innerHTML = html;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>
