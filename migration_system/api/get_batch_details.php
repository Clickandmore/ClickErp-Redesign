<?php
/**
 * API للحصول على تفاصيل دفعة النقل
 */

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['migration_user'])) {
    sendJsonResponse(['success' => false, 'message' => 'غير مصرح بالوصول'], 401);
}

$batch_id = intval($_GET['id'] ?? 0);

if (!$batch_id) {
    sendJsonResponse(['success' => false, 'message' => 'معرف الدفعة مطلوب']);
}

try {
    global $db;

    // الحصول على تفاصيل الدفعة
    $batch = $db->fetch("
        SELECT
            mb.*,
            TIMESTAMPDIFF(MINUTE, started_at, COALESCE(completed_at, NOW())) as duration_minutes,
            (SELECT name FROM admins WHERE id = mb.created_by) as created_by_name
        FROM migration_batches mb
        WHERE mb.id = ?
    ", [$batch_id]);

    if (!$batch) {
        sendJsonResponse(['success' => false, 'message' => 'الدفعة غير موجودة']);
    }

    // الحصول على تفاصيل الأخطاء
    $errors_summary = $db->fetchAll("
        SELECT
            error_type,
            COUNT(*) as error_count,
            error_message
        FROM migration_errors
        WHERE batch_id = ?
        GROUP BY error_type, error_message
        ORDER BY error_count DESC
        LIMIT 10
    ", [$batch_id]);

    // الحصول على عينة من البيانات المعالجة
    $processed_sample = $db->fetchAll("
        SELECT
            row_number,
            JSON_EXTRACT(processed_data, '$.name') as name,
            JSON_EXTRACT(processed_data, '$.email') as email,
            validation_status
        FROM temp_import_data
        WHERE batch_id = ? AND is_processed = 1
        ORDER BY row_number
        LIMIT 10
    ", [$batch_id]);

    // إنشاء HTML للمحتوى
    ob_start();
    ?>

    <div class="row">
        <div class="col-md-6">
            <h6 class="text-primary">معلومات الدفعة:</h6>
            <table class="table table-sm">
                <tr>
                    <td><strong>المعرف:</strong></td>
                    <td><?= $batch['id'] ?></td>
                </tr>
                <tr>
                    <td><strong>الاسم:</strong></td>
                    <td><?= htmlspecialchars($batch['batch_name']) ?></td>
                </tr>
                <tr>
                    <td><strong>الجدول المصدر:</strong></td>
                    <td><code><?= htmlspecialchars($batch['source_table']) ?></code></td>
                </tr>
                <tr>
                    <td><strong>الجدول المستهدف:</strong></td>
                    <td><code><?= htmlspecialchars($batch['target_table']) ?></code></td>
                </tr>
                <tr>
                    <td><strong>اسم الملف:</strong></td>
                    <td><?= htmlspecialchars($batch['file_name']) ?></td>
                </tr>
                <tr>
                    <td><strong>الحالة:</strong></td>
                    <td>
                        <span class="batch-status <?= getBatchStatusClass($batch['status']) ?>">
                            <?= formatBatchStatus($batch['status']) ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>تاريخ الإنشاء:</strong></td>
                    <td><?= formatDate($batch['created_at']) ?></td>
                </tr>
                <?php if ($batch['started_at']): ?>
                <tr>
                    <td><strong>تاريخ البدء:</strong></td>
                    <td><?= formatDate($batch['started_at']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($batch['completed_at']): ?>
                <tr>
                    <td><strong>تاريخ الانتهاء:</strong></td>
                    <td><?= formatDate($batch['completed_at']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($batch['duration_minutes']): ?>
                <tr>
                    <td><strong>المدة:</strong></td>
                    <td><?= $batch['duration_minutes'] ?> دقيقة</td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="col-md-6">
            <h6 class="text-success">إحصائيات المعالجة:</h6>
            <div class="row text-center mb-3">
                <div class="col-3">
                    <div class="bg-light p-2 rounded">
                        <div class="h4 text-primary mb-0"><?= number_format($batch['total_records']) ?></div>
                        <small>إجمالي</small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="bg-light p-2 rounded">
                        <div class="h4 text-info mb-0"><?= number_format($batch['processed_records']) ?></div>
                        <small>معالج</small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="bg-light p-2 rounded">
                        <div class="h4 text-success mb-0"><?= number_format($batch['successful_records']) ?></div>
                        <small>ناجح</small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="bg-light p-2 rounded">
                        <div class="h4 text-danger mb-0"><?= number_format($batch['failed_records']) ?></div>
                        <small>فاشل</small>
                    </div>
                </div>
            </div>

            <?php if ($batch['total_records'] > 0): ?>
            <div class="progress mb-3" style="height: 10px;">
                <?php $progress = ($batch['processed_records'] / $batch['total_records']) * 100; ?>
                <div class="progress-bar bg-success" style="width: <?= ($batch['successful_records'] / $batch['total_records']) * 100 ?>%"></div>
                <div class="progress-bar bg-danger" style="width: <?= ($batch['failed_records'] / $batch['total_records']) * 100 ?>%"></div>
            </div>
            <div class="text-center">
                <small>معدل النجاح: <?= $batch['processed_records'] > 0 ? round(($batch['successful_records'] / $batch['processed_records']) * 100, 1) : 0 ?>%</small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($batch['notes'])): ?>
    <div class="mt-3">
        <h6 class="text-info">ملاحظات:</h6>
        <div class="alert alert-info">
            <?= nl2br(htmlspecialchars($batch['notes'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors_summary)): ?>
    <div class="mt-4">
        <h6 class="text-warning">ملخص الأخطاء:</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead class="table-light">
                    <tr>
                        <th>نوع الخطأ</th>
                        <th>العدد</th>
                        <th>الرسالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($errors_summary as $error): ?>
                    <tr>
                        <td><span class="badge bg-danger"><?= htmlspecialchars($error['error_type']) ?></span></td>
                        <td><?= $error['error_count'] ?></td>
                        <td class="small"><?= htmlspecialchars(substr($error['error_message'], 0, 100)) ?>...</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($processed_sample)): ?>
    <div class="mt-4">
        <h6 class="text-success">عينة من البيانات المعالجة:</h6>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead class="table-light">
                    <tr>
                        <th>الصف</th>
                        <th>الاسم</th>
                        <th>البريد الإلكتروني</th>
                        <th>حالة التحقق</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processed_sample as $record): ?>
                    <tr>
                        <td><?= $record['row_number'] ?></td>
                        <td><?= htmlspecialchars(trim($record['name'] ?? '', '"')) ?></td>
                        <td><?= htmlspecialchars(trim($record['email'] ?? '', '"')) ?></td>
                        <td>
                            <span class="badge bg-<?= $record['validation_status'] === 'valid' ? 'success' : 'warning' ?>">
                                <?= $record['validation_status'] === 'valid' ? 'صحيح' : $record['validation_status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php
    $html = ob_get_clean();

    sendJsonResponse([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    error_log("Get batch details error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'خطأ في الخادم'], 500);
}
?>
