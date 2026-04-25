<?php
/**
 * نظام نقل البيانات - الواجهة الرئيسية
 * Data Migration System - Main Interface
 */

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// التحقق من الجلسة (يمكن إضافة نظام مصادقة لاحقاً)
if (!isset($_SESSION['migration_user'])) {
    $_SESSION['migration_user'] = [
        'id' => 1,
        'name' => 'Migration Admin',
        'role' => 'admin'
    ];
}

$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'upload', 'batches', 'mapping', 'settings', 'reports', 'full_migration'];

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

// إحصائيات النقل
$migration_stats = getMigrationStatistics();
$recent_batches = getRecentBatches(10);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام نقل البيانات - Data Migration System</title>

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .stat-card {
            background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .upload-area.dragover {
            border-color: #28a745;
            background-color: #d4edda;
        }
        .progress-wrapper {
            display: none;
            margin-top: 20px;
        }
        .batch-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-database me-2"></i>
                        نظام النقل
                    </h4>

                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'dashboard' ? 'active' : '' ?>"
                               href="?page=dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'upload' ? 'active' : '' ?>"
                               href="?page=upload">
                                <i class="fas fa-upload me-2"></i>
                                رفع الملفات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'batches' ? 'active' : '' ?>"
                               href="?page=batches">
                                <i class="fas fa-list me-2"></i>
                                دفعات النقل
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'mapping' ? 'active' : '' ?>"
                               href="?page=mapping">
                                <i class="fas fa-link me-2"></i>
                                ربط المعرفات
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'reports' ? 'active' : '' ?>"
                               href="?page=reports">
                                <i class="fas fa-chart-bar me-2"></i>
                                التقارير
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'full_migration' ? 'active' : '' ?>"
                               href="?page=full_migration">
                                <i class="fas fa-rocket me-2"></i>
                                النقل الشامل
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page == 'settings' ? 'active' : '' ?>"
                               href="?page=settings">
                                <i class="fas fa-cog me-2"></i>
                                الإعدادات
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- إحصائيات سريعة -->
                <div class="p-3 border-top mt-auto">
                    <small class="text-light opacity-75">إحصائيات سريعة</small>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between">
                            <span class="small">المعالج:</span>
                            <span class="small"><?= number_format($migration_stats['total_processed']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small">الناجح:</span>
                            <span class="small"><?= number_format($migration_stats['total_successful']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small">الفاشل:</span>
                            <span class="small"><?= number_format($migration_stats['total_failed']) ?></span>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 col-lg-10 px-4">
                <div class="py-3">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>
                            <?php
                            $page_titles = [
                                'dashboard' => 'لوحة التحكم',
                                'upload' => 'رفع الملفات',
                                'batches' => 'دفعات النقل',
                                'mapping' => 'ربط المعرفات',
                                'reports' => 'التقارير',
                                'full_migration' => 'النقل الشامل',
                                'settings' => 'الإعدادات'
                            ];
                            echo $page_titles[$page] ?? 'نظام النقل';
                            ?>
                        </h2>
                        <div class="text-muted">
                            <i class="fas fa-user me-1"></i>
                            <?= $_SESSION['migration_user']['name'] ?>
                        </div>
                    </div>

                    <!-- تضمين محتوى الصفحة -->
                    <?php
                    $page_file = "pages/{$page}.php";
                    if (file_exists($page_file)) {
                        include $page_file;
                    } else {
                        echo '<div class="alert alert-warning">الصفحة غير موجودة</div>';
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/app.js"></script>

    <script>
        // تحديث الإحصائيات كل 30 ثانية
        setInterval(function() {
            if (window.location.href.indexOf('page=dashboard') > -1 ||
                window.location.href.indexOf('page=') == -1) {
                location.reload();
            }
        }, 30000);

        // تأكيد العمليات الهامة
        $('.confirm-action').on('click', function(e) {
            if (!confirm('هل أنت متأكد من هذا الإجراء؟')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
