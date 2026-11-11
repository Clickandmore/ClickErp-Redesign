<?php
/**
 * اختبار بسيط لحل مشكلة Transaction
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_POST['action'] ?? 'test';

    switch ($action) {
        case 'test_connection':
            $db = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root');
            $result = $db->query("SELECT COUNT(*) as count FROM users")->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'الاتصال يعمل بنجاح',
                'users_count' => $result['count']
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'add_simple_column':
            $db = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root');

            try {
                $db->exec("ALTER TABLE users ADD COLUMN test_column VARCHAR(255) NULL");
                echo json_encode([
                    'success' => true,
                    'message' => 'تم إضافة عمود اختبار بنجاح'
                ], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'خطأ في إضافة العمود: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'show_columns':
            $db = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root');
            $columns = $db->query("DESCRIBE users")->fetchAll();

            echo json_encode([
                'success' => true,
                'columns' => $columns,
                'total_columns' => count($columns)
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            echo json_encode([
                'success' => true,
                'message' => 'النظام يعمل بشكل طبيعي'
            ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
