<?php
/**
 * صفحة دفعات النقل
 * Migration Batches Page
 */

$page_num = max(1, intval($_GET['page_num'] ?? 1));
$per_page = 20;
$offset = ($page_num - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$table_filter = $_GET['table'] ?? '';
$highlight_batch = $_GET['highlight'] ?? '';

// بناء استعلام البحث
$where_conditions = ["batch_name != 'DATABASE_SETUP'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(batch_name LIKE ? OR source_table LIKE ? OR target_table LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($table_filter)) {
    $where_conditions[] = "(source_table = ? OR target_table = ?)";
    $params[] = $table_filter;
    $params[] = $table_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// الحصول على إجمالي العدد
$total_batches = $db->fetch("
    SELECT COUNT(*) as total FROM migration_batches WHERE {$where_clause}
", $params)['total'] ?? 0;

// الحصول على الدفعات
$batches = $db->fetchAll("
    SELECT
        id, batch_name, source_table, target_table, file_name, status,
        total_records, processed_records, successful_records, failed_records,
        created_at, started_at, completed_at, notes,
        TIMESTAMPDIFF(MINUTE, started_at, COALESCE(completed_at, NOW())) as duration_minutes
    FROM migration_batches
    WHERE {$where_clause}
    ORDER BY created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
", $params);

// الحصول على إحصائيات الحالات
$status_stats = $db->fetchAll("
    SELECT status, COUNT(*) as count
    FROM migration_batches
    WHERE batch_name != 'DATABASE_SETUP'
    GROUP BY status
");

$total_pages = ceil($total_batches / $per_page);
?>

<!-- شريط البحث والفلترة -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="batches">

            <div class="col-md-4">
                <label class="form-label">البحث:</label>
                <input type="text" name="search" class="form-control"
                       placeholder="اسم الدفعة أو الجدول..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">الحالة:</label>
                <select name="status" class="form-select">
                    <option value="">جميع الحالات</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>في الانتظار</option>
                    <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>قيد المعالجة</option>
                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>مكتمل</option>
                    <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>فاشل</option>
                    <option value="paused" <?= $status_filter === 'paused' ? 'selected' : '' ?>>متوقف</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">الجدول:</label>
                <select name="table" class="form-select">
                    <option value="">جميع الجداول</option>
                    <option value="users" <?= $table_filter === 'users' ? 'selected' : '' ?>>المستخدمون</option>
                    <option value="maids" <?= $table_filter === 'maids' ? 'selected' : '' ?>>العاملات</option>
                    <option value="orders" <?= $table_filter === 'orders' ? 'selected' : '' ?>>الطلبات</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> بحث
                </button>
                <a href="?page=batches" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> مسح
                </a>
            </div>
        </form>
    </div>
</div>

<!-- إحصائيات سريعة -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-secondary fs-6 me-2"><?= $total_batches ?></span>
                        <span>إجمالي الدفعات</span>
                    </div>
                    <?php foreach ($status_stats as $stat): ?>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-<?= getStatusColor($stat['status']) ?> fs-6 me-2">
                            <?= $stat['count'] ?>
                        </span>
                        <span><?= formatBatchStatus($stat['status']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- جدول الدفعات -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>
            دفعات النقل
        </h5>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" onclick="refreshBatches()">
                <i class="fas fa-sync-alt"></i> تحديث
            </button>
            <a href="?page=upload" class="btn btn-primary">
                <i class="fas fa-plus"></i> دفعة جديدة
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($batches)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">لا توجد دفعات نقل</h5>
                <p class="text-muted">لم يتم العثور على دفعات تطابق معايير البحث</p>
                <a href="?page=upload" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    إنشاء دفعة جديدة
                </a>
            </div>
        <?php else: ?>

                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="20%">اسم الدفعة</th>
                            <th width="15%">المسار</th>
                            <th width="10%">الحالة</th>
                            <th width="15%">التقدم</th>
                            <th width="15%">التاريخ</th>
                            <th width="10%">المدة</th>
                            <th width="10%">العمليات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($batches as $batch): ?>
                        <tr class="<?= $highlight_batch == $batch['id'] ? 'table-warning' : '' ?>"
                            id="batch-<?= $batch['id'] ?>">
                            <td>
                                <span class="fw-bold"><?= $batch['id'] ?></span>
                            </td>
                            <td>
                                <div>
                                    <span class="fw-bold"><?= htmlspecialchars($batch['batch_name']) ?></span>
                                    <?php if ($batch['file_name']): ?>
                                    <br><small class="text-muted">
                                        <i class="fas fa-file"></i>
                                        <?= htmlspecialchars($batch['file_name']) ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div><code class="text-primary"><?= $batch['source_table'] ?></code></div>
                                    <div class="text-muted">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <div><code class="text-success"><?= $batch['target_table'] ?></code></div>
                                </div>
                            </td>
                            <td>
                                <span class="batch-status <?= getBatchStatusClass($batch['status']) ?>">
                                    <?= formatBatchStatus($batch['status']) ?>
                                </span>
                                <?php if ($batch['status'] === 'processing'): ?>
                                <div class="spinner-border spinner-border-sm text-primary mt-1" role="status">
                                    <span class="visually-hidden">جاري المعالجة...</span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($batch['total_records'] > 0): ?>
                                <div class="progress mb-1" style="height: 5px;">
                                    <?php
                                    $progress = ($batch['processed_records'] / $batch['total_records']) * 100;
                                    $progress_class = $batch['status'] === 'completed' ? 'bg-success' :
                                                     ($batch['status'] === 'failed' ? 'bg-danger' : 'bg-primary');
                                    ?>
                                    <div class="progress-bar <?= $progress_class ?>"
                                         style="width: <?= $progress ?>%"></div>
                                </div>
                                <div class="small">
                                    <span class="text-success">
                                        <i class="fas fa-check"></i> <?= number_format($batch['successful_records']) ?>
                                    </span>
                                    <?php if ($batch['failed_records'] > 0): ?>
                                    <span class="text-danger ms-2">
                                        <i class="fas fa-times"></i> <?= number_format($batch['failed_records']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <div class="text-muted">من <?= number_format($batch['total_records']) ?></div>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small">
                                    <div>
                                        <i class="fas fa-calendar"></i>
                                        <?= formatDate($batch['created_at'], 'd/m/Y') ?>
                                    </div>
                                    <div class="text-muted">
                                        <i class="fas fa-clock"></i>
                                        <?= formatDate($batch['created_at'], 'H:i') ?>
                                    </div>
                                    <?php if ($batch['completed_at']): ?>
                                    <div class="text-success small">
                                        <i class="fas fa-check-circle"></i>
                                        <?= formatDate($batch['completed_at'], 'd/m H:i') ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($batch['duration_minutes']): ?>
                                <span class="small">
                                    <?php
                                    if ($batch['duration_minutes'] < 1) {
                                        echo '< دقيقة';
                                    } elseif ($batch['duration_minutes'] < 60) {
                                        echo $batch['duration_minutes'] . ' دقيقة';
                                    } else {
                                        echo floor($batch['duration_minutes'] / 60) . ' ساعة ' .
                                             ($batch['duration_minutes'] % 60) . ' دقيقة';
                                    }
                                    ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-info"
                                            onclick="viewBatchDetails(<?= $batch['id'] ?>)"
                                            title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    <?php if ($batch['failed_records'] > 0): ?>
                                    <button type="button" class="btn btn-outline-warning"
                                            onclick="viewBatchErrors(<?= $batch['id'] ?>)"
                                            title="عرض الأخطاء">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($batch['status'] === 'processing'): ?>
                                    <button type="button" class="btn btn-outline-secondary"
                                            onclick="pauseBatch(<?= $batch['id'] ?>)"
                                            title="إيقاف مؤقت">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                    <?php endif; ?>

                                    <?php if (in_array($batch['status'], ['failed', 'paused'])): ?>
                                    <button type="button" class="btn btn-outline-success"
                                            onclick="retryBatch(<?= $batch['id'] ?>)"
                                            title="إعادة المحاولة">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <?php endif; ?>

                                    <?php if (!in_array($batch['status'], ['processing'])): ?>
                                    <button type="button" class="btn btn-outline-danger confirm-action"
                                            onclick="deleteBatch(<?= $batch['id'] ?>)"
                                            title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>


            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li class="page-item <?= $page_num <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=batches&page_num=<?= $page_num - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&table=<?= urlencode($table_filter) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>

                        <?php for ($i = max(1, $page_num - 2); $i <= min($total_pages, $page_num + 2); $i++): ?>
                        <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                            <a class="page-link" href="?page=batches&page_num=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&table=<?= urlencode($table_filter) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=batches&page_num=<?= $page_num + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&table=<?= urlencode($table_filter) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="text-center mt-2">
                    <small class="text-muted">
                        عرض <?= min($offset + 1, $total_batches) ?> إلى <?= min($offset + $per_page, $total_batches) ?>
                        من إجمالي <?= number_format($total_batches) ?> دفعة
                    </small>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal تفاصيل الدفعة -->
<div class="modal fade" id="batchDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل الدفعة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="batchDetailsContent">
                <!-- سيتم تحميل المحتوى عبر AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Modal أخطاء الدفعة -->
<div class="modal fade" id="batchErrorsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">أخطاء الدفعة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="batchErrorsContent">
                <!-- سيتم تحميل المحتوى عبر AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function getStatusColor(status) {
    const colors = {
        'pending': 'secondary',
        'processing': 'primary',
        'completed': 'success',
        'failed': 'danger',
        'paused': 'warning'
    };
    return colors[status] || 'secondary';
}

function refreshBatches() {
    location.reload();
}

function viewBatchDetails(batchId) {
    fetch(`api/get_batch_details.php?id=${batchId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('batchDetailsContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('batchDetailsModal')).show();
            } else {
                alert('خطأ في تحميل التفاصيل: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في تحميل التفاصيل');
        });
}

function viewBatchErrors(batchId) {
    fetch(`api/get_batch_errors.php?id=${batchId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('batchErrorsContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('batchErrorsModal')).show();
            } else {
                alert('خطأ في تحميل الأخطاء: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في تحميل الأخطاء');
        });
}

function pauseBatch(batchId) {
    if (confirm('هل تريد إيقاف هذه الدفعة مؤقتاً؟')) {
        fetch('api/batch_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'pause', batch_id: batchId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshBatches();
            } else {
                alert('خطأ: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في إيقاف الدفعة');
        });
    }
}

function retryBatch(batchId) {
    if (confirm('هل تريد إعادة محاولة معالجة هذه الدفعة؟')) {
        fetch('api/batch_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'retry', batch_id: batchId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshBatches();
            } else {
                alert('خطأ: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في إعادة المحاولة');
        });
    }
}

function deleteBatch(batchId) {
    if (confirm('هل تريد حذف هذه الدفعة نهائياً؟ لا يمكن التراجع عن هذا الإجراء.')) {
        fetch('api/batch_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', batch_id: batchId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById(`batch-${batchId}`).remove();
            } else {
                alert('خطأ: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في حذف الدفعة');
        });
    }
}

// تحديث تلقائي للدفعات النشطة
setInterval(function() {
    const processingBatches = document.querySelectorAll('[id^="batch-"] .spinner-border');
    if (processingBatches.length > 0) {
        refreshBatches();
    }
}, 10000); // كل 10 ثواني
</script>

<?php
function getStatusColor($status) {
    $colors = [
        'pending' => 'secondary',
        'processing' => 'primary',
        'completed' => 'success',
        'failed' => 'danger',
        'paused' => 'warning'
    ];
    return $colors[$status] ?? 'secondary';
}
?>
