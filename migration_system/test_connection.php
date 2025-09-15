<?php
/**
 * اختبار اتصال قواعد البيانات
 */

// تفعيل عرض الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/database.php';

    $db = Database::getInstance();

    echo "<h2>اختبار اتصال قواعد البيانات</h2>\n";
    echo "<hr>\n";

    // اختبار قاعدة migration
    try {
        $result = $db->fetch("SELECT COUNT(*) as count FROM migration_batches", [], 'migration');
        echo "✅ <strong>قاعدة migration:</strong> متصلة بنجاح - عدد الدفعات: " . $result['count'] . "<br>\n";
    } catch (Exception $e) {
        echo "❌ <strong>قاعدة migration:</strong> خطأ - " . $e->getMessage() . "<br>\n";
    }

    // اختبار قاعدة erp_operations
    try {
        $result = $db->fetch("SELECT COUNT(*) as count FROM users", [], 'erp_operations');
        echo "✅ <strong>قاعدة erp_operations:</strong> متصلة بنجاح - عدد المستخدمين: " . $result['count'] . "<br>\n";
    } catch (Exception $e) {
        echo "❌ <strong>قاعدة erp_operations:</strong> خطأ - " . $e->getMessage() . "<br>\n";
    }

    // اختبار قاعدة platinum
    try {
        $result = $db->fetch("SELECT COUNT(*) as count FROM users", [], 'platinum');
        echo "✅ <strong>قاعدة platinum:</strong> متصلة بنجاح - عدد المستخدمين: " . $result['count'] . "<br>\n";
    } catch (Exception $e) {
        echo "❌ <strong>قاعدة platinum:</strong> خطأ - " . $e->getMessage() . "<br>\n";
    }

    echo "<hr>\n";
    echo "<h3>معلومات إضافية:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>إصدار PHP:</strong> " . PHP_VERSION . "</li>\n";
    echo "<li><strong>إصدار MySQL:</strong> ";

    try {
        $mysql_version = $db->fetch("SELECT VERSION() as version", [], 'migration');
        echo $mysql_version['version'];
    } catch (Exception $e) {
        echo "غير متاح";
    }
    echo "</li>\n";

    echo "<li><strong>المنطقة الزمنية:</strong> " . date_default_timezone_get() . "</li>\n";
    echo "<li><strong>الترميز:</strong> " . mb_internal_encoding() . "</li>\n";
    echo "</ul>\n";

    // اختبار إنشاء دفعة جديدة
    echo "<hr>\n";
    echo "<h3>اختبار وظائف النظام:</h3>\n";

    try {
        $batch_id = createMigrationBatch(
            'اختبار الاتصال - ' . date('Y-m-d H:i:s'),
            'test_table',
            'test_target',
            'test_file.csv',
            1
        );
        echo "✅ <strong>إنشاء الدفعات:</strong> يعمل بنجاح - ID: $batch_id<br>\n";

        // حذف الدفعة التجريبية
        $db->delete('migration_batches', 'id = ?', [$batch_id], 'migration');
        echo "✅ <strong>حذف الدفعات:</strong> يعمل بنجاح<br>\n";

    } catch (Exception $e) {
        echo "❌ <strong>وظائف النظام:</strong> خطأ - " . $e->getMessage() . "<br>\n";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>خطأ في تحميل النظام:</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
}
?>
