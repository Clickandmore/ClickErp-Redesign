<?php
/**
 * لوحة التحكم الرئيسية
 * Main Dashboard
 */

// الحصول على الإحصائيات
$stats = getMigrationStatistics();
$recent_batches = getRecentBatches(5);

// إحصائيات الجداول
$table_stats = [];
try {
    $table_stats = $db->fetchAll("
        SELECT
            source_table,
            target_table,
            COUNT(*) as batches_count,
            SUM(successful_records) as total_migrated,
            SUM(failed_records) as total_failed,
            MAX(created_at) as last_migration
        FROM migration_batches
        WHERE batch_name != 'DATABASE_SETUP'
        GROUP BY source_table, target_table
        ORDER BY total_migrated DESC
    ");
} catch (Exception $e) {
    error_log("Error getting table stats: " . $e->getMessage());
}

// أخطاء شائعة
$common_errors = [];
try {
    $common_errors = $db->fetchAll("
        SELECT
            error_type,
            COUNT(*) as error_count,
            error_message
        FROM migration_errors me
        JOIN migration_batches mb ON mb.id = me.batch_id
        WHERE DATE(me.created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY error_type, error_message
        ORDER BY error_count DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    error_log("Error getting common errors: " . $e->getMessage());
}
?>

<!-- بطاقات الإحصائيات -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">إجمالي الدفعات</h6>
                        <h3 class="mb-0"><?= number_format($stats['total_batches']) ?></h3>
                    </div>
                    <div class="text-white-50">
                        <i class="fas fa-layer-group fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white" style="background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">السجلات المعالجة</h6>
                        <h3 class="mb-0"><?= number_format($stats['total_processed']) ?></h3>
                    </div>
                    <div class="text-white-50">
                        <i class="fas fa-database fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white" style="background: linear-gradient(45deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">معدل النجاح</h6>
                        <h3 class="mb-0"><?= $stats['success_rate'] ?>%</h3>
                    </div>
                    <div class="text-white-50">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stat-card text-white" style="background: linear-gradient(45deg, #fc466b 0%, #3f5efb 100%);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">دفعات نشطة</h6>
                        <h3 class="mb-0"><?= number_format($stats['active_batches']) ?></h3>
                    </div>
                    <div class="text-white-50">
                        <i class="fas fa-sync-alt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- آخر دفعات النقل -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    آخر دفعات النقل
                </h5>
                <a href="?page=batches" class="btn btn-sm btn-outline-primary">عرض الكل</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_batches)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>لا توجد دفعات نقل حتى الآن</p>
                        <a href="?page=upload" class="btn btn-primary">ابدأ نقل جديد</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>اسم الدفعة</th>
                                    <th>من → إلى</th>
                                    <th>الحالة</th>
                                    <th>السجلات</th>
                                    <th>التاريخ</th>
                                    <th>المدة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_batches as $batch): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($batch['batch_name']) ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($batch['source_table']) ?> →
                                            <?= htmlspecialchars($batch['target_table']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="batch-status <?= getBatchStatusClass($batch['status']) ?>">
                                            <?= formatBatchStatus($batch['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-success">
                                                <i class="fas fa-check"></i> <?= number_format($batch['successful_records']) ?>
                                            </span>
                                            <?php if ($batch['failed_records'] > 0): ?>
                                            <span class="text-danger">
                                                <i class="fas fa-times"></i> <?= number_format($batch['failed_records']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?= formatDate($batch['created_at'], 'd/m/Y H:i') ?></small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php
                                            if ($batch['duration_minutes']) {
                                                echo $batch['duration_minutes'] . ' دقيقة';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- الأخطاء الشائعة -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                    الأخطاء الشائعة
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($common_errors)): ?>
                    <div class="text-center text-success">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <p class="mb-0">لا توجد أخطاء حديثة!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($common_errors as $error): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-danger"><?= htmlspecialchars($error['error_type']) ?></h6>
                                <p class="mb-1 small text-muted">
                                    <?= htmlspecialchars(substr($error['error_message'], 0, 50)) ?>...
                                </p>
                            </div>
                            <span class="badge bg-danger"><?= $error['error_count'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="text-center mt-3">
                        <a href="?page=reports" class="btn btn-sm btn-outline-warning">عرض التفاصيل</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- إحصائيات الجداول -->
<?php if (!empty($table_stats)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    إحصائيات الجداول
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>الجدول المصدر</th>
                                <th>الجدول المستهدف</th>
                                <th>عدد الدفعات</th>
                                <th>السجلات المنقولة</th>
                                <th>السجلات الفاشلة</th>
                                <th>آخر نقل</th>
                                <th>العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_stats as $stat): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($stat['source_table']) ?></code></td>
                                <td><code><?= htmlspecialchars($stat['target_table']) ?></code></td>
                                <td>
                                    <span class="badge bg-info"><?= $stat['batches_count'] ?></span>
                                </td>
                                <td>
                                    <span class="text-success">
                                        <i class="fas fa-arrow-up"></i>
                                        <?= number_format($stat['total_migrated']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($stat['total_failed'] > 0): ?>
                                        <span class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?= number_format($stat['total_failed']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= formatDate($stat['last_migration'], 'd/m/Y') ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?page=upload&table=<?= urlencode($stat['source_table']) ?>"
                                           class="btn btn-outline-primary" title="نقل جديد">
                                            <i class="fas fa-plus"></i>
                                        </a>
                                        <a href="?page=mapping&source=<?= urlencode($stat['source_table']) ?>&target=<?= urlencode($stat['target_table']) ?>"
                                           class="btn btn-outline-info" title="عرض التطابق">
                                            <i class="fas fa-link"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- رسائل تحفيزية -->
<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-lightbulb fa-2x me-3"></i>
                <div>
                    <h6 class="alert-heading mb-1">نصائح للاستخدام الأمثل:</h6>
                    <ul class="mb-0">
                        <li>تأكد من تطابق أعمدة ملف CSV مع متطلبات الجدول المستهدف</li>
                        <li>استخدم دفعات صغيرة (أقل من 1000 سجل) للحصول على أداء أفضل</li>
                        <li>راجع الأخطاء بانتظام وقم بتصحيح البيانات قبل إعادة المحاولة</li>
                        <li>احتفظ بنسخة احتياطية قبل البدء في عمليات النقل الكبيرة</li>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
</div>

<script>
// تحديث الإحصائيات كل دقيقة
setInterval(function() {
    // فقط إذا كان هناك دفعات نشطة
    <?php if ($stats['active_batches'] > 0): ?>
    fetch('api/get_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // تحديث الإحصائيات دون إعادة تحميل الصفحة
                document.querySelector('.stat-card h3').textContent =
                    new Intl.NumberFormat('ar').format(data.stats.total_batches);
            }
        })
        .catch(error => console.log('تعذر تحديث الإحصائيات:', error));
    <?php endif; ?>
}, 60000);
</script>
