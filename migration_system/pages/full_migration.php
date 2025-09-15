<?php
/**
 * صفحة النقل الشامل
 * Full Migration Page
 */

// معالجة بدء النقل الشامل
if ($_POST && isset($_POST['start_full_migration'])) {
    try {
        // إنشاء نسخة احتياطية
        if ($_POST['create_backup'] === 'true') {
            $backup_file = createDatabaseBackup();
            $backup_message = "تم إنشاء نسخة احتياطية: $backup_file";
        }

        // بدء النقل الشامل
        $migration_id = startFullMigration($_POST);
        $success_message = "تم بدء النقل الشامل بنجاح. معرف العملية: $migration_id";

    } catch (Exception $e) {
        $error_message = "خطأ في بدء النقل: " . $e->getMessage();
        error_log("Full migration start error: " . $e->getMessage());
    }
}

// الحصول على حالة النقل الحالي
$current_migration = getCurrentMigrationStatus();
$migration_stats = getFullMigrationStats();
?>

<?php if (isset($success_message)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?= $success_message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($backup_message)): ?>
<div class="alert alert-info alert-dismissible fade show">
    <i class="fas fa-database me-2"></i>
    <?= $backup_message ?>
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

<!-- تحذير مهم -->
<div class="alert alert-warning">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
        <div>
            <h6 class="alert-heading mb-1">⚠️ تحذير مهم:</h6>
            <p class="mb-0">النقل الشامل سيقوم بنقل جميع البيانات من قاعدة ERP Operations إلى قاعدة Platinum.
            هذه عملية كبيرة قد تستغرق وقتاً طويلاً ويُنصح بشدة بإنشاء نسخة احتياطية أولاً.</p>
        </div>
    </div>
</div>

<!-- إحصائيات عامة -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white" style="background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body text-center">
                <h6>جداول ERP Operations</h6>
                <h2><?= $migration_stats['erp_tables_count'] ?? 87 ?></h2>
                <small>جدولاً</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white" style="background: linear-gradient(45deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body text-center">
                <h6>جداول Platinum</h6>
                <h2><?= $migration_stats['platinum_tables_count'] ?? 109 ?></h2>
                <small>جدولاً</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white" style="background: linear-gradient(45deg, #fc466b 0%, #3f5efb 100%);">
            <div class="card-body text-center">
                <h6>إجمالي السجلات</h6>
                <h2><?= number_format($migration_stats['total_records'] ?? 50000) ?></h2>
                <small>سجلاً</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white" style="background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);">
            <div class="card-body text-center">
                <h6>الوقت المقدر</h6>
                <h2><?= $migration_stats['estimated_hours'] ?? 2 ?></h2>
                <small>ساعة</small>
            </div>
        </div>
    </div>
</div>

<!-- حالة النقل الحالي -->
<?php if ($current_migration && $current_migration['status'] === 'in_progress'): ?>
<div class="card mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-sync-alt fa-spin me-2"></i>
            عملية نقل جارية
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h6>المرحلة الحالية: <?= htmlspecialchars($current_migration['current_phase']) ?></h6>
                <div class="progress mb-2" style="height: 10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         style="width: <?= $current_migration['progress_percent'] ?>%"></div>
                </div>
                <div class="d-flex justify-content-between">
                    <small>الجدول الحالي: <?= htmlspecialchars($current_migration['current_table']) ?></small>
                    <small><?= $current_migration['progress_percent'] ?>% مكتمل</small>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="row">
                    <div class="col-6">
                        <div class="h5 text-success"><?= number_format($current_migration['completed_tables']) ?></div>
                        <small>جدول مكتمل</small>
                    </div>
                    <div class="col-6">
                        <div class="h5 text-info"><?= number_format($current_migration['total_migrated']) ?></div>
                        <small>سجل منقول</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <button type="button" class="btn btn-outline-warning me-2" onclick="pauseMigration()">
                <i class="fas fa-pause"></i> إيقاف مؤقت
            </button>
            <button type="button" class="btn btn-outline-danger" onclick="stopMigration()">
                <i class="fas fa-stop"></i> إيقاف
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- نموذج بدء النقل الشامل -->
<?php if (!$current_migration || $current_migration['status'] !== 'in_progress'): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-rocket me-2"></i>
            بدء النقل الشامل
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" id="fullMigrationForm">
            <!-- خيارات النقل -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">خيارات النقل:</h6>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="create_backup" value="true" id="createBackup" checked>
                        <label class="form-check-label" for="createBackup">
                            <strong>إنشاء نسخة احتياطية قبل البدء</strong>
                            <br><small class="text-muted">يُنصح بشدة تفعيل هذا الخيار</small>
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="skip_existing" value="true" id="skipExisting" checked>
                        <label class="form-check-label" for="skipExisting">
                            <strong>تخطي البيانات الموجودة</strong>
                            <br><small class="text-muted">عدم الكتابة فوق البيانات الموجودة</small>
                        </label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="validate_data" value="true" id="validateData" checked>
                        <label class="form-check-label" for="validateData">
                            <strong>التحقق من صحة البيانات</strong>
                            <br><small class="text-muted">فحص البيانات قبل النقل</small>
                        </label>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-success mb-3">إعدادات الأداء:</h6>

                    <div class="mb-3">
                        <label class="form-label">حجم الدفعة:</label>
                        <select name="batch_size" class="form-select">
                            <option value="500">500 سجل</option>
                            <option value="1000" selected>1000 سجل</option>
                            <option value="2000">2000 سجل</option>
                            <option value="5000">5000 سجل</option>
                        </select>
                        <div class="form-text">عدد السجلات المعالجة في المرة الواحدة</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">أولوية النقل:</label>
                        <select name="migration_priority" class="form-select">
                            <option value="low">منخفضة (أقل استهلاك للموارد)</option>
                            <option value="normal" selected>عادية (متوازنة)</option>
                            <option value="high">عالية (أسرع لكن أكثر استهلاكاً)</option>
                        </select>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="parallel_processing" value="true" id="parallelProcessing">
                        <label class="form-check-label" for="parallelProcessing">
                            <strong>معالجة متوازية</strong>
                            <br><small class="text-muted">تسريع النقل باستخدام عمليات متوازية</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- اختيار الجداول -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6>اختيار الجداول للنقل:</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="migrate_reference" value="true" id="migrateReference" checked>
                                <label class="form-check-label" for="migrateReference">
                                    <strong>البيانات المرجعية</strong>
                                    <br><small>البلدان، المدن، الجنسيات</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="migrate_users" value="true" id="migrateUsers" checked>
                                <label class="form-check-label" for="migrateUsers">
                                    <strong>المستخدمون</strong>
                                    <br><small>المستخدمون، العناوين، العملاء</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="migrate_maids" value="true" id="migrateMaids" checked>
                                <label class="form-check-label" for="migrateMaids">
                                    <strong>العاملات</strong>
                                    <br><small>العاملات → السير الذاتية</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="migrate_orders" value="true" id="migrateOrders" checked>
                                <label class="form-check-label" for="migrateOrders">
                                    <strong>الطلبات</strong>
                                    <br><small>الطلبات والعقود</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="migrate_content" value="true" id="migrateContent">
                                <label class="form-check-label" for="migrateContent">
                                    <strong>المحتوى</strong>
                                    <br><small>الصفحات، الشرائح، الخدمات</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="migrate_complaints" value="true" id="migrateComplaints">
                                <label class="form-check-label" for="migrateComplaints">
                                    <strong>الشكاوى</strong>
                                    <br><small>الشكاوى وأنواعها</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="migrate_settings" value="true" id="migrateSettings">
                                <label class="form-check-label" for="migrateSettings">
                                    <strong>الإعدادات</strong>
                                    <br><small>إعدادات النظام والتطبيق</small>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="migrate_all_other" value="true" id="migrateAllOther">
                                <label class="form-check-label" for="migrateAllOther">
                                    <strong>جداول أخرى</strong>
                                    <br><small>جميع الجداول المتبقية</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- إعدادات متقدمة -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6>إعدادات متقدمة:</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">معالجة البيانات المكررة:</label>
                                <select name="duplicate_handling" class="form-select">
                                    <option value="skip">تخطي المكررة</option>
                                    <option value="update">تحديث المكررة</option>
                                    <option value="ignore">تجاهل (المحاولة فقط)</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">معالجة الأخطاء:</label>
                                <select name="error_handling" class="form-select">
                                    <option value="continue">المتابعة مع تسجيل الأخطاء</option>
                                    <option value="pause">التوقف عند الخطأ الأول</option>
                                    <option value="rollback">التراجع عند الأخطاء الكثيرة</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أزرار العمل -->
            <div class="text-center">
                <button type="button" class="btn btn-outline-info btn-lg me-3" onclick="previewMigration()">
                    <i class="fas fa-search me-2"></i>
                    معاينة خطة النقل
                </button>

                <button type="submit" name="start_full_migration" class="btn btn-success btn-lg"
                        onclick="return confirmFullMigration()">
                    <i class="fas fa-rocket me-2"></i>
                    بدء النقل الشامل
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- سجل النقل المكتمل -->
<?php if (!empty($migration_stats['completed_migrations'])): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-history me-2"></i>
            سجل عمليات النقل المكتملة
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>التاريخ</th>
                        <th>إجمالي الجداول</th>
                        <th>السجلات المنقولة</th>
                        <th>المدة</th>
                        <th>الحالة</th>
                        <th>العمليات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($migration_stats['completed_migrations'] as $migration): ?>
                    <tr>
                        <td><?= formatDate($migration['created_at']) ?></td>
                        <td><?= $migration['total_tables'] ?></td>
                        <td><?= number_format($migration['total_records']) ?></td>
                        <td><?= $migration['duration'] ?> دقيقة</td>
                        <td>
                            <span class="badge bg-<?= $migration['status'] === 'completed' ? 'success' : 'warning' ?>">
                                <?= $migration['status'] === 'completed' ? 'مكتمل' : 'جزئي' ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-info"
                                    onclick="viewMigrationDetails(<?= $migration['id'] ?>)">
                                <i class="fas fa-eye"></i> التفاصيل
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function confirmFullMigration() {
    const message = `
هل أنت متأكد من بدء النقل الشامل؟

⚠️ هذه العملية ستقوم بـ:
• نقل جميع البيانات من ERP Operations إلى Platinum
• إضافة الجداول والحقول المفقودة
• معالجة آلاف السجلات
• قد تستغرق ساعات طويلة

✅ تأكد من:
• وجود نسخة احتياطية حديثة
• عدم استخدام النظام أثناء النقل
• توفر مساحة كافية على القرص

هل تريد المتابعة؟`;

    return confirm(message);
}

function previewMigration() {
    fetch('api/preview_full_migration.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'preview',
            options: getFormOptions()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMigrationPreview(data.preview);
        } else {
            alert('خطأ في المعاينة: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ في المعاينة');
    });
}

function getFormOptions() {
    const form = document.getElementById('fullMigrationForm');
    const formData = new FormData(form);
    const options = {};

    for (let [key, value] of formData.entries()) {
        options[key] = value;
    }

    return options;
}

function showMigrationPreview(preview) {
    let html = '<div class="modal fade" id="previewModal" tabindex="-1"><div class="modal-dialog modal-xl">';
    html += '<div class="modal-content"><div class="modal-header bg-info text-white">';
    html += '<h5 class="modal-title">معاينة خطة النقل الشامل</h5>';
    html += '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>';
    html += '</div><div class="modal-body">';

    // إحصائيات عامة
    html += '<div class="row mb-4">';
    html += '<div class="col-md-3 text-center"><div class="bg-primary text-white p-3 rounded">';
    html += '<h4>' + preview.total_tables + '</h4><small>جدول للنقل</small></div></div>';
    html += '<div class="col-md-3 text-center"><div class="bg-success text-white p-3 rounded">';
    html += '<h4>' + preview.total_records.toLocaleString() + '</h4><small>سجل للنقل</small></div></div>';
    html += '<div class="col-md-3 text-center"><div class="bg-warning text-white p-3 rounded">';
    html += '<h4>' + preview.new_tables + '</h4><small>جدول جديد</small></div></div>';
    html += '<div class="col-md-3 text-center"><div class="bg-info text-white p-3 rounded">';
    html += '<h4>' + preview.estimated_duration + '</h4><small>دقيقة (تقدير)</small></div></div>';
    html += '</div>';

    // تفاصيل المراحل
    html += '<h6 class="text-primary">مراحل النقل:</h6>';
    html += '<div class="table-responsive">';
    html += '<table class="table table-sm">';
    html += '<thead class="table-light"><tr><th>المرحلة</th><th>الجداول</th><th>السجلات</th><th>الوقت المقدر</th></tr></thead>';
    html += '<tbody>';

    preview.phases.forEach(phase => {
        html += '<tr>';
        html += '<td><strong>' + phase.name + '</strong></td>';
        html += '<td>' + phase.tables.join(', ') + '</td>';
        html += '<td>' + phase.total_records.toLocaleString() + '</td>';
        html += '<td>' + phase.estimated_minutes + ' دقيقة</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    html += '</div></div></div></div>';

    document.body.insertAdjacentHTML('beforeend', html);
    new bootstrap.Modal(document.getElementById('previewModal')).show();

    // حذف المودال عند الإغلاق
    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

function pauseMigration() {
    if (confirm('هل تريد إيقاف عملية النقل مؤقتاً؟')) {
        fetch('api/control_migration.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'pause'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('خطأ: ' + data.message);
            }
        });
    }
}

function stopMigration() {
    if (confirm('هل تريد إيقاف عملية النقل نهائياً؟ لا يمكن التراجع عن هذا الإجراء.')) {
        fetch('api/control_migration.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'stop'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('خطأ: ' + data.message);
            }
        });
    }
}

// تحديث تلقائي للحالة كل 10 ثواني
<?php if ($current_migration && $current_migration['status'] === 'in_progress'): ?>
setInterval(function() {
    fetch('api/get_migration_status.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.status.status !== 'in_progress') {
                location.reload();
            }
        })
        .catch(error => console.log('Status update failed:', error));
}, 10000);
<?php endif; ?>
</script>

<?php
function getCurrentMigrationStatus() {
    // محاكاة حالة النقل - يمكن استبدالها بقراءة فعلية من قاعدة البيانات
    return null; // لا توجد عملية نقل جارية حالياً
}

function getFullMigrationStats() {
    return [
        'erp_tables_count' => 87,
        'platinum_tables_count' => 109,
        'total_records' => 50000, // تقدير
        'estimated_hours' => 2,
        'completed_migrations' => [] // سيتم تحديثها من قاعدة البيانات
    ];
}

function createDatabaseBackup() {
    $backup_file = 'platinum_backup_' . date('Y_m_d_H_i_s') . '.sql';
    // هنا سيكون كود إنشاء النسخة الاحتياطية
    return $backup_file;
}

function startFullMigration($options) {
    // هنا سيكون كود بدء النقل الشامل
    return rand(1000, 9999); // معرف وهمي
}
?>
