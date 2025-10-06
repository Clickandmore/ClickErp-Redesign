<?php
/**
 * صفحة ربط المعرفات
 * ID Mapping Page
 */

$source_table = $_GET['source'] ?? '';
$target_table = $_GET['target'] ?? '';
$page_num = max(1, intval($_GET['page_num'] ?? 1));
$per_page = 50;
$offset = ($page_num - 1) * $per_page;

// الحصول على إحصائيات التطابق
$mapping_stats = [];
try {
    $mapping_stats = $db->fetchAll("
        SELECT
            source_table,
            target_table,
            COUNT(*) as total_mappings,
            MAX(created_at) as last_mapping
        FROM id_mapping
        GROUP BY source_table, target_table
        ORDER BY total_mappings DESC
    ");
} catch (Exception $e) {
    error_log("Error getting mapping stats: " . $e->getMessage());
}

// بناء الاستعلام
$where_conditions = ['1=1'];
$params = [];

if (!empty($source_table)) {
    $where_conditions[] = "source_table = ?";
    $params[] = $source_table;
}

if (!empty($target_table)) {
    $where_conditions[] = "target_table = ?";
    $params[] = $target_table;
}

$where_clause = implode(' AND ', $where_conditions);

// الحصول على إجمالي العدد
$total_mappings = $db->fetch("
    SELECT COUNT(*) as total FROM id_mapping WHERE {$where_clause}
", $params)['total'] ?? 0;

// الحصول على التطابقات
$mappings = [];
if ($total_mappings > 0) {
    $mappings = $db->fetchAll("
        SELECT
            id, source_table, target_table, source_id, target_id,
            additional_data, created_at
        FROM id_mapping
        WHERE {$where_clause}
        ORDER BY created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ", $params);
}

$total_pages = ceil($total_mappings / $per_page);
?>

<div class="row">
    <!-- إحصائيات التطابق -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-link me-2"></i>
                    إحصائيات ربط المعرفات
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($mapping_stats)): ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <p>لا توجد تطابقات حتى الآن</p>
                        <small>سيتم إنشاء التطابقات تلقائياً عند نقل البيانات</small>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($mapping_stats as $stat): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h6 class="card-title">
                                        <code class="text-primary"><?= htmlspecialchars($stat['source_table']) ?></code>
                                        <br>
                                        <i class="fas fa-arrow-down text-muted my-2"></i>
                                        <br>
                                        <code class="text-success"><?= htmlspecialchars($stat['target_table']) ?></code>
                                    </h6>
                                    <div class="display-6 text-primary">
                                        <?= number_format($stat['total_mappings']) ?>
                                    </div>
                                    <small class="text-muted">تطابق</small>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            آخر تحديث: <?= formatDate($stat['last_mapping'], 'd/m/Y') ?>
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="?page=mapping&source=<?= urlencode($stat['source_table']) ?>&target=<?= urlencode($stat['target_table']) ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            عرض التطابقات
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- فلترة التطابقات -->
<?php if (!empty($mapping_stats)): ?>
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="page" value="mapping">

                    <div class="col-md-4">
                        <label class="form-label">الجدول المصدر:</label>
                        <select name="source" class="form-select">
                            <option value="">جميع الجداول المصدر</option>
                            <?php
                            $unique_sources = array_unique(array_column($mapping_stats, 'source_table'));
                            foreach ($unique_sources as $source):
                            ?>
                            <option value="<?= htmlspecialchars($source) ?>"
                                    <?= $source_table === $source ? 'selected' : '' ?>>
                                <?= htmlspecialchars($source) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">الجدول المستهدف:</label>
                        <select name="target" class="form-select">
                            <option value="">جميع الجداول المستهدفة</option>
                            <?php
                            $unique_targets = array_unique(array_column($mapping_stats, 'target_table'));
                            foreach ($unique_targets as $target):
                            ?>
                            <option value="<?= htmlspecialchars($target) ?>"
                                    <?= $target_table === $target ? 'selected' : '' ?>>
                                <?= htmlspecialchars($target) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> فلترة
                        </button>
                        <a href="?page=mapping" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> مسح
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- جدول التطابقات -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    تفاصيل التطابقات
                    <?php if (!empty($source_table) || !empty($target_table)): ?>
                    <small class="text-muted">
                        (<?= htmlspecialchars($source_table) ?> → <?= htmlspecialchars($target_table) ?>)
                    </small>
                    <?php endif; ?>
                </h5>

                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary" onclick="exportMappings()">
                        <i class="fas fa-download"></i> تصدير
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="refreshMappings()">
                        <i class="fas fa-sync-alt"></i> تحديث
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (empty($mappings)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">لا توجد تطابقات</h5>
                        <p class="text-muted">
                            <?php if (!empty($source_table) || !empty($target_table)): ?>
                                لا توجد تطابقات للفلاتر المحددة
                            <?php else: ?>
                                لم يتم إنشاء أي تطابقات بعد
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">الجدول المصدر</th>
                                    <th width="10%">المعرف المصدر</th>
                                    <th width="15%">الجدول المستهدف</th>
                                    <th width="10%">المعرف المستهدف</th>
                                    <th width="25%">بيانات إضافية</th>
                                    <th width="15%">تاريخ الإنشاء</th>
                                    <th width="5%">عمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mappings as $mapping): ?>
                                <tr>
                                    <td><?= $mapping['id'] ?></td>
                                    <td>
                                        <code class="text-primary"><?= htmlspecialchars($mapping['source_table']) ?></code>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $mapping['source_id'] ?></span>
                                    </td>
                                    <td>
                                        <code class="text-success"><?= htmlspecialchars($mapping['target_table']) ?></code>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?= $mapping['target_id'] ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($mapping['additional_data'])): ?>
                                            <?php
                                            $additional = json_decode($mapping['additional_data'], true);
                                            if ($additional):
                                            ?>
                                            <div class="small">
                                                <?php foreach ($additional as $key => $value): ?>
                                                    <div><strong><?= htmlspecialchars($key) ?>:</strong> <?= htmlspecialchars(substr($value, 0, 30)) ?><?= strlen($value) > 30 ? '...' : '' ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?= formatDate($mapping['created_at'], 'd/m/Y') ?>
                                            <br>
                                            <span class="text-muted"><?= formatDate($mapping['created_at'], 'H:i') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-info btn-sm"
                                                    onclick="viewMappingDetails(<?= $mapping['id'] ?>)"
                                                    title="التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm confirm-action"
                                                    onclick="deleteMapping(<?= $mapping['id'] ?>)"
                                                    title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                                    <a class="page-link" href="?page=mapping&page_num=<?= $page_num - 1 ?>&source=<?= urlencode($source_table) ?>&target=<?= urlencode($target_table) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>

                                <?php for ($i = max(1, $page_num - 2); $i <= min($total_pages, $page_num + 2); $i++): ?>
                                <li class="page-item <?= $i == $page_num ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=mapping&page_num=<?= $i ?>&source=<?= urlencode($source_table) ?>&target=<?= urlencode($target_table) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $page_num >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=mapping&page_num=<?= $page_num + 1 ?>&source=<?= urlencode($source_table) ?>&target=<?= urlencode($target_table) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>

                        <div class="text-center mt-2">
                            <small class="text-muted">
                                عرض <?= min($offset + 1, $total_mappings) ?> إلى <?= min($offset + $per_page, $total_mappings) ?>
                                من إجمالي <?= number_format($total_mappings) ?> تطابق
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function exportMappings() {
    const params = new URLSearchParams({
        source: '<?= $source_table ?>',
        target: '<?= $target_table ?>'
    });

    window.open(`api/export_mappings.php?${params.toString()}`, '_blank');
}

function refreshMappings() {
    location.reload();
}

function viewMappingDetails(mappingId) {
    fetch(`api/get_mapping_details.php?id=${mappingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMappingModal(data.mapping);
            } else {
                alert('خطأ في تحميل التفاصيل: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في تحميل التفاصيل');
        });
}

function deleteMapping(mappingId) {
    if (confirm('هل تريد حذف هذا التطابق؟ قد يؤثر ذلك على عمليات النقل المستقبلية.')) {
        fetch('api/delete_mapping.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: mappingId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('خطأ في الحذف: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في الحذف');
        });
    }
}

function showMappingModal(mapping) {
    const modalHTML = `
        <div class="modal fade" id="mappingModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تفاصيل التطابق #${mapping.id}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-sm">
                            <tr><th>الجدول المصدر:</th><td><code>${mapping.source_table}</code></td></tr>
                            <tr><th>المعرف المصدر:</th><td><span class="badge bg-secondary">${mapping.source_id}</span></td></tr>
                            <tr><th>الجدول المستهدف:</th><td><code>${mapping.target_table}</code></td></tr>
                            <tr><th>المعرف المستهدف:</th><td><span class="badge bg-success">${mapping.target_id}</span></td></tr>
                            <tr><th>تاريخ الإنشاء:</th><td>${mapping.created_at}</td></tr>
                        </table>
                        ${mapping.additional_data ? `<h6>البيانات الإضافية:</h6><pre class="small">${JSON.stringify(JSON.parse(mapping.additional_data), null, 2)}</pre>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modal = new bootstrap.Modal(document.getElementById('mappingModal'));
    modal.show();

    // حذف المودال عند الإغلاق
    document.getElementById('mappingModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}
</script>
