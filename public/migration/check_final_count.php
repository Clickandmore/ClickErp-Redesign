<?php
/**
 * التحقق النهائي من الأعداد
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // الاتصال بقاعدة البيانات الجديدة
    $newDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root');

    // عد المستخدمين
    $userCount = $newDb->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // الحصول على إحصائيات مفصلة
    $stats = [
        'total_users' => $userCount,
        'users_with_names' => $newDb->query("SELECT COUNT(*) FROM users WHERE name IS NOT NULL AND name != ''")->fetchColumn(),
        'users_with_phones' => $newDb->query("SELECT COUNT(*) FROM users WHERE phone IS NOT NULL AND phone != ''")->fetchColumn(),
        'users_with_emails' => $newDb->query("SELECT COUNT(*) FROM users WHERE email IS NOT NULL AND email != '' AND email != 'NULL'")->fetchColumn(),
        'users_with_phone2' => $newDb->query("SELECT COUNT(*) FROM users WHERE phone2 IS NOT NULL AND phone2 != ''")->fetchColumn(),
        'users_with_national_num' => $newDb->query("SELECT COUNT(*) FROM users WHERE national_num IS NOT NULL AND national_num != ''")->fetchColumn(),
        'users_blocked' => $newDb->query("SELECT COUNT(*) FROM users WHERE is_blocked = 1")->fetchColumn(),
        'users_active' => $newDb->query("SELECT COUNT(*) FROM users WHERE status = 1")->fetchColumn(),
    ];

    // آخر 10 مستخدمين
    $lastUsers = $newDb->query("SELECT id, name, phone, email, phone2, national_num, created_at FROM users ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    // أول 10 مستخدمين
    $firstUsers = $newDb->query("SELECT id, name, phone, email, phone2, national_num, created_at FROM users ORDER BY id ASC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'database' => 'platinum_new',
        'stats' => $stats,
        'last_users' => $lastUsers,
        'first_users' => $firstUsers,
        'message' => "إجمالي المستخدمين: $userCount"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
