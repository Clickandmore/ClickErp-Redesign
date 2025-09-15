<?php
/**
 * API للحصول على حالة دفعة النقل
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

    $batch = $db->fetch("
        SELECT
            id, batch_name, source_table, target_table, status,
            total_records, processed_records, successful_records, failed_records,
            created_at, started_at, completed_at, notes,
            TIMESTAMPDIFF(MINUTE, started_at, COALESCE(completed_at, NOW())) as duration_minutes
        FROM migration_batches
        WHERE id = ?
    ", [$batch_id]);

    if (!$batch) {
        sendJsonResponse(['success' => false, 'message' => 'الدفعة غير موجودة']);
    }

    // الحصول على آخر الأخطاء إذا وجدت
    $recent_errors = [];
    if ($batch['failed_records'] > 0) {
        $recent_errors = $db->fetchAll("
            SELECT error_type, error_message, row_number
            FROM migration_errors
            WHERE batch_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ", [$batch_id]);
    }

    sendJsonResponse([
        'success' => true,
        'batch' => $batch,
        'recent_errors' => $recent_errors
    ]);

} catch (Exception $e) {
    error_log("Get batch status error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'خطأ في الخادم'], 500);
}
?>
