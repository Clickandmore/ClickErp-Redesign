<?php
/**
 * تحليل السجلات المفقودة
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // الاتصال بقاعدة البيانات
    $db = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root');

    // عد المستخدمين الحاليين
    $currentCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // الحصول على أكبر وأصغر ID
    $minMax = $db->query("SELECT MIN(id) as min_id, MAX(id) as max_id FROM users")->fetch(PDO::FETCH_ASSOC);

    // البحث عن الـ IDs المفقودة
    $expectedCount = $minMax['max_id'] - $minMax['min_id'] + 1;
    $missingIds = [];

    if ($expectedCount > $currentCount) {
        // البحث عن الفجوات
        $result = $db->query("SELECT id FROM users ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

        for ($i = $minMax['min_id']; $i <= $minMax['max_id']; $i++) {
            if (!in_array($i, $result)) {
                $missingIds[] = $i;
            }
        }
    }

    // الحصول على آخر 10 سجلات
    $lastRecords = $db->query("SELECT id, name, phone, email, created_at FROM users ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // الحصول على السجلات بدون بيانات أساسية
    $incompleteRecords = $db->query("
        SELECT id, name, phone, email
        FROM users
        WHERE (name IS NULL OR name = '')
           AND (phone IS NULL OR phone = '')
           AND (email IS NULL OR email = '')
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // الحصول على إحصائيات الأعمدة الجديدة
    $columnStats = [];
    $importantColumns = ['first_name', 'last_name', 'national_num', 'phone2', 'job', 'user_type', 'token', 'register_from'];

    foreach ($importantColumns as $col) {
        $checkStmt = $db->prepare("SHOW COLUMNS FROM users LIKE ?");
        $checkStmt->execute([$col]);

        if ($checkStmt->rowCount() > 0) {
            $filledCount = $db->query("SELECT COUNT(*) FROM users WHERE `$col` IS NOT NULL AND `$col` != ''")->fetchColumn();
            $columnStats[$col] = [
                'exists' => true,
                'filled_count' => $filledCount,
                'filled_percentage' => round(($filledCount / $currentCount) * 100, 2)
            ];
        } else {
            $columnStats[$col] = [
                'exists' => false,
                'filled_count' => 0,
                'filled_percentage' => 0
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'current_count' => $currentCount,
        'min_id' => $minMax['min_id'],
        'max_id' => $minMax['max_id'],
        'expected_count' => $expectedCount,
        'missing_ids' => array_slice($missingIds, 0, 20), // أول 20 فقط
        'missing_count' => count($missingIds),
        'last_records' => $lastRecords,
        'incomplete_records' => $incompleteRecords,
        'column_stats' => $columnStats
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
