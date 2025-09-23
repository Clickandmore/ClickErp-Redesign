<?php
/**
 * صفحة الإعدادات
 * Settings Page
 */

// معالجة حفظ الإعدادات
if ($_POST && isset($_POST['save_settings'])) {
    try {
        foreach ($_POST as $key => $value) {
            if ($key !== 'save_settings') {
                $db->query("
                    INSERT INTO migration_settings (setting_key, setting_value)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ", [$key, $value]);
            }
        }
        $success_message = "تم حفظ الإعدادات بنجاح";
        logActivity('settings_updated', 'تم تحديث إعدادات النظام');
    } catch (Exception $e) {
        $error_message = "خطأ في حفظ الإعدادات: " . $e->getMessage();
        error_log("Settings save error: " . $e->getMessage());
    }
}

// الحصول على الإعدادات الحالية
$settings = [];
try {
    $settings_rows = $db->fetchAll("SELECT setting_key, setting_value, setting_type, description FROM migration_settings");
    foreach ($settings_rows as $setting) {
        $settings[$setting['setting_key']] = $setting;
    }
} catch (Exception $e) {
    error_log("Error loading settings: " . $e->getMessage());
    $error_message = "خطأ في تحميل الإعدادات";
}

// فحص حالة النظام
$system_status = [];
try {
    $system_status = [
        'database_connection' => checkDatabaseConnections(),
        'file_permissions' => checkFilePermissions(),
        'php_extensions' => checkPhpExtensions(),
        'disk_space' => getDiskSpace(),
        'memory_usage' => getMemoryUsage()
    ];
} catch (Exception $e) {
    error_log("Error checking system status: " . $e->getMessage());
}
?>

<?php if (isset($success_message)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?= $success_message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i>
    <?= $error_message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <!-- حالة النظام -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-heartbeat me-2"></i>
                    حالة النظام
                </h5>
            </div>
            <div class="card-body">
                <!-- اتصال قاعدة البيانات -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>اتصال قاعدة البيانات:</span>
                    <span class="badge bg-<?= $system_status['database_connection']['status'] === 'success' ? 'success' : 'danger' ?>">
                        <?= $system_status['database_connection']['status'] === 'success' ? 'متصل' : 'غير متصل' ?>
                    </span>
                </div>

                <!-- صلاحيات الملفات -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>صلاحيات الملفات:</span>
                    <span class="badge bg-<?= $system_status['file_permissions']['writable'] ? 'success' : 'danger' ?>">
                        <?= $system_status['file_permissions']['writable'] ? 'صحيحة' : 'خطأ' ?>
                    </span>
                </div>

                <!-- مساحة القرص -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>مساحة القرص:</span>
                    <span class="small"><?= $system_status['disk_space']['free'] ?> متاح</span>
                </div>

                <!-- استخدام الذاكرة -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span>استخدام الذاكرة:</span>
                    <span class="small"><?= $system_status['memory_usage'] ?></span>
                </div>

                <!-- امتدادات PHP -->
                <div class="mb-3">
                    <span class="d-block mb-2">امتدادات PHP المطلوبة:</span>
                    <?php foreach ($system_status['php_extensions'] as $ext => $available): ?>
                    <span class="badge bg-<?= $available ? 'success' : 'danger' ?> me-1">
                        <?= $ext ?>
                    </span>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="btn btn-outline-primary btn-sm" onclick="checkSystemStatus()">
                    <i class="fas fa-sync-alt"></i> إعادة فحص
                </button>
            </div>
        </div>
    </div>

    <!-- إعدادات النظام -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    إعدادات النظام
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row">
                        <!-- إعدادات المعالجة -->
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">إعدادات المعالجة:</h6>

                            <div class="mb-3">
                                <label class="form-label">حجم الدفعة:</label>
                                <input type="number" name="batch_size" class="form-control"
                                       value="<?= htmlspecialchars($settings['batch_size']['setting_value'] ?? '500') ?>"
                                       min="100" max="5000" required>
                                <div class="form-text">عدد السجلات المعالجة في المرة الواحدة</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">الحد الأقصى لحجم الملف (بايت):</label>
                                <input type="number" name="max_file_size" class="form-control"
                                       value="<?= htmlspecialchars($settings['max_file_size']['setting_value'] ?? '10485760') ?>"
                                       min="1048576" required>
                                <div class="form-text">الحد الأقصى لحجم ملف CSV (افتراضي: 10MB)</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">أنواع الملفات المدعومة:</label>
                                <input type="text" name="allowed_extensions" class="form-control"
                                       value="<?= htmlspecialchars($settings['allowed_extensions']['setting_value'] ?? '["csv","xlsx"]') ?>"
                                       required>
                                <div class="form-text">JSON array للأنواع المدعومة</div>
                            </div>
                        </div>

                        <!-- إعدادات الأمان والنسخ الاحتياطي -->
                        <div class="col-md-6">
                            <h6 class="text-success mb-3">إعدادات الأمان:</h6>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="backup_before_import"
                                           value="true" id="backupCheck"
                                           <?= ($settings['backup_before_import']['setting_value'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="backupCheck">
                                        إنشاء نسخة احتياطية قبل الاستيراد
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="validate_before_import"
                                           value="true" id="validateCheck"
                                           <?= ($settings['validate_before_import']['setting_value'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="validateCheck">
                                        التحقق من البيانات قبل الاستيراد
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="auto_rollback_on_error"
                                           value="true" id="rollbackCheck"
                                           <?= ($settings['auto_rollback_on_error']['setting_value'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="rollbackCheck">
                                        التراجع التلقائي في حالة الخطأ
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- إعدادات إضافية -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-info mb-3">إعدادات إضافية:</h6>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">مدة الاحتفاظ بالبيانات المؤقتة (أيام):</label>
                                <input type="number" name="temp_data_retention_days" class="form-control"
                                       value="<?= htmlspecialchars($settings['temp_data_retention_days']['setting_value'] ?? '7') ?>"
                                       min="1" max="30" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">مهلة انتظار المعالجة (ثانية):</label>
                                <input type="number" name="processing_timeout" class="form-control"
                                       value="<?= htmlspecialchars($settings['processing_timeout']['setting_value'] ?? '300') ?>"
                                       min="60" max="3600" required>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="resetDefaults()">
                            استعادة الافتراضي
                        </button>
                        <button type="submit" name="save_settings" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            حفظ الإعدادات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- أدوات صيانة النظام -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-tools me-2"></i>
                    أدوات صيانة النظام
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-trash fa-2x text-warning mb-3"></i>
                                <h6>تنظيف البيانات المؤقتة</h6>
                                <p class="small text-muted">حذف البيانات المؤقتة القديمة</p>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="cleanupTempData()">
                                    تنظيف الآن
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-2x text-info mb-3"></i>
                                <h6>تحسين قاعدة البيانات</h6>
                                <p class="small text-muted">تحسين جداول قاعدة البيانات</p>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="optimizeDatabase()">
                                    تحسين الآن
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-download fa-2x text-success mb-3"></i>
                                <h6>تصدير السجلات</h6>
                                <p class="small text-muted">تصدير سجلات النشاط والأخطاء</p>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportLogs()">
                                    تصدير الآن
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- إحصائيات الصيانة -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">إحصائيات الصيانة:</h6>
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="h5 mb-0" id="tempDataCount">-</div>
                                    <small>بيانات مؤقتة</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="h5 mb-0" id="logFilesCount">-</div>
                                    <small>ملفات السجلات</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="h5 mb-0" id="uploadedFilesCount">-</div>
                                    <small>ملفات مرفوعة</small>
                                </div>
                                <div class="col-md-3">
                                    <div class="h5 mb-0" id="databaseSize">-</div>
                                    <small>حجم قاعدة البيانات</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحميل إحصائيات الصيانة
    loadMaintenanceStats();

    // التحقق من النموذج
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});

function checkSystemStatus() {
    showLoading('.card-body');

    fetch('api/system_status.php')
        .then(response => response.json())
        .then(data => {
            hideLoading('.card-body');
            if (data.success) {
                location.reload();
            } else {
                showNotification('خطأ في فحص النظام: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading('.card-body');
            console.error('Error:', error);
            showNotification('حدث خطأ في فحص النظام', 'error');
        });
}

function resetDefaults() {
    if (confirm('هل تريد استعادة الإعدادات الافتراضية؟')) {
        fetch('api/reset_settings.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showNotification('خطأ في استعادة الإعدادات: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('حدث خطأ في استعادة الإعدادات', 'error');
        });
    }
}

function cleanupTempData() {
    if (confirm('هل تريد حذف جميع البيانات المؤقتة القديمة؟')) {
        fetch('api/cleanup.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'cleanup_temp_data'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('تم تنظيف البيانات بنجاح. تم حذف ' + data.deleted_count + ' عنصر.', 'success');
                loadMaintenanceStats();
            } else {
                showNotification('خطأ في التنظيف: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('حدث خطأ في التنظيف', 'error');
        });
    }
}

function optimizeDatabase() {
    if (confirm('هل تريد تحسين جداول قاعدة البيانات؟ قد يستغرق هذا وقتاً.')) {
        showLoading('.card');

        fetch('api/optimize_db.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        })
        .then(response => response.json())
        .then(data => {
            hideLoading('.card');
            if (data.success) {
                showNotification('تم تحسين قاعدة البيانات بنجاح', 'success');
                loadMaintenanceStats();
            } else {
                showNotification('خطأ في تحسين قاعدة البيانات: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading('.card');
            console.error('Error:', error);
            showNotification('حدث خطأ في تحسين قاعدة البيانات', 'error');
        });
    }
}

function exportLogs() {
    fetch('api/export_logs.php')
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'system_logs_' + new Date().toISOString().split('T')[0] + '.zip';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            showNotification('تم بدء تحميل ملف السجلات', 'success');
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('حدث خطأ في تصدير السجلات', 'error');
        });
}

function loadMaintenanceStats() {
    fetch('api/maintenance_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('tempDataCount').textContent = data.stats.temp_data_count;
                document.getElementById('logFilesCount').textContent = data.stats.log_files_count;
                document.getElementById('uploadedFilesCount').textContent = data.stats.uploaded_files_count;
                document.getElementById('databaseSize').textContent = data.stats.database_size;
            }
        })
        .catch(error => {
            console.error('Error loading maintenance stats:', error);
        });
}
</script>

<?php
function checkDatabaseConnections() {
    global $db;
    try {
        $db->fetch("SELECT 1");
        return ['status' => 'success', 'message' => 'اتصال ناجح'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function checkFilePermissions() {
    $dirs = ['uploads', 'logs', 'templates'];
    $writable = true;

    foreach ($dirs as $dir) {
        $path = __DIR__ . "/../{$dir}";
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        if (!is_writable($path)) {
            $writable = false;
            break;
        }
    }

    return ['writable' => $writable];
}

function checkPhpExtensions() {
    $required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'fileinfo'];
    $extensions = [];

    foreach ($required as $ext) {
        $extensions[$ext] = extension_loaded($ext);
    }

    return $extensions;
}

function getDiskSpace() {
    $free = disk_free_space(__DIR__);
    $total = disk_total_space(__DIR__);

    return [
        'free' => formatBytes($free),
        'total' => formatBytes($total),
        'used_percent' => round((($total - $free) / $total) * 100, 1)
    ];
}

function getMemoryUsage() {
    $memory = memory_get_usage(true);
    $peak = memory_get_peak_usage(true);

    return formatBytes($memory) . ' / ' . formatBytes($peak) . ' (peak)';
}

function formatBytes($size, $precision = 2) {
    if ($size == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}
?>
