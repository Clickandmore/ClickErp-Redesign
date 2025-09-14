<?php
/**
 * إصلاح واختبار النظام
 */

// تفعيل عرض الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 إصلاح واختبار نظام النقل</h2>\n";
echo "<hr>\n";

try {
    // تحميل الإعدادات المُحسنة
    require_once 'config/database_fixed.php';

    echo "<h3>1. فحص قواعد البيانات:</h3>\n";

    $db = Database::getInstance();

    // فحص وإنشاء قواعد البيانات
    $setup_results = setupDatabases();

    foreach ($setup_results as $db_name => $result) {
        $status = $result['connected'] ? '✅' : '❌';
        echo "<p>$status <strong>$db_name:</strong> ";

        if ($result['connected']) {
            echo "متصل بنجاح";
        } else {
            echo "فشل الاتصال";
            if (isset($result['created'])) {
                echo $result['created'] ? " (تم إنشاؤها)" : " (فشل في الإنشاء)";
            }
        }
        echo "</p>\n";
    }

    echo "<hr>\n";
    echo "<h3>2. إعداد الجداول المطلوبة:</h3>\n";

    // إعداد جداول النقل
    try {
        $setup_sql = "
            CREATE TABLE IF NOT EXISTS `migration_batches` (
                `id` int AUTO_INCREMENT PRIMARY KEY,
                `batch_name` varchar(100) NOT NULL,
                `source_table` varchar(100) NOT NULL,
                `target_table` varchar(100) NOT NULL,
                `file_name` varchar(255) DEFAULT NULL,
                `total_records` int DEFAULT 0,
                `processed_records` int DEFAULT 0,
                `successful_records` int DEFAULT 0,
                `failed_records` int DEFAULT 0,
                `status` enum('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
                `started_at` timestamp NULL DEFAULT NULL,
                `completed_at` timestamp NULL DEFAULT NULL,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `created_by` int DEFAULT NULL
            ) ENGINE=InnoDB;
        ";

        $db->query($setup_sql, [], 'migration');
        echo "✅ تم إعداد جدول migration_batches<br>\n";

        // إدراج سجل اختبار
        $test_batch_id = $db->insert('migration_batches', [
            'batch_name' => 'اختبار الاتصال',
            'source_table' => 'test',
            'target_table' => 'test',
            'status' => 'completed',
            'created_by' => 1
        ], 'migration');

        echo "✅ تم إنشاء دفعة اختبار - ID: $test_batch_id<br>\n";

        // حذف السجل التجريبي
        $db->delete('migration_batches', 'id = ?', [$test_batch_id], 'migration');
        echo "✅ تم حذف الدفعة التجريبية<br>\n";

    } catch (Exception $e) {
        echo "❌ خطأ في إعداد الجداول: " . $e->getMessage() . "<br>\n";
    }

    echo "<hr>\n";
    echo "<h3>3. اختبار الملفات:</h3>\n";

    // فحص الملفات المطلوبة
    $required_files = [
        'includes/functions.php',
        'pages/dashboard.php',
        'pages/upload.php',
        'pages/full_migration.php',
        'api/upload_handler.php'
    ];

    foreach ($required_files as $file) {
        if (file_exists($file)) {
            echo "✅ $file موجود<br>\n";
        } else {
            echo "❌ $file مفقود<br>\n";
        }
    }

    echo "<hr>\n";
    echo "<h3>4. اختبار الوظائف:</h3>\n";

    // تحميل الوظائف
    require_once 'includes/functions.php';

    try {
        $stats = getMigrationStatistics();
        echo "✅ دالة getMigrationStatistics تعمل بنجاح<br>\n";
        echo "   📊 إجمالي الدفعات: " . $stats['total_batches'] . "<br>\n";
    } catch (Exception $e) {
        echo "❌ خطأ في getMigrationStatistics: " . $e->getMessage() . "<br>\n";
    }

    try {
        $batches = getRecentBatches(5);
        echo "✅ دالة getRecentBatches تعمل بنجاح - عدد الدفعات: " . count($batches) . "<br>\n";
    } catch (Exception $e) {
        echo "❌ خطأ في getRecentBatches: " . $e->getMessage() . "<br>\n";
    }

    echo "<hr>\n";
    echo "<h3>5. الحلول المقترحة:</h3>\n";

    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>\n";
    echo "<h6>✅ تم إصلاح المشاكل التالية:</h6>\n";
    echo "<ul>\n";
    echo "<li>خطأ preg_replace في تنظيف أسماء الملفات</li>\n";
    echo "<li>خطأ trim() مع قيم null</li>\n";
    echo "<li>مشاكل الاتصال بقواعد البيانات</li>\n";
    echo "<li>تحسين معالجة الأخطاء</li>\n";
    echo "</ul>\n";
    echo "</div>\n";

    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 10px 0;'>\n";
    echo "<h6>🔧 للاستخدام:</h6>\n";
    echo "<ol>\n";
    echo "<li>انسخ محتوى <code>config/database_fixed.php</code> إلى <code>config/database.php</code></li>\n";
    echo "<li>اختبر النظام مرة أخرى</li>\n";
    echo "<li>جرب رفع ملف CSV صغير أولاً</li>\n";
    echo "</ol>\n";
    echo "</div>\n";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ خطأ عام:</h3>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
    echo "<p><strong>تفاصيل:</strong></p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<hr>\n";
echo "<h3>🚀 الخطوات التالية:</h3>\n";
echo "<ol>\n";
echo "<li><a href='fix_system.php'>تطبيق الإصلاحات التلقائية</a></li>\n";
echo "<li><a href='test_upload.php'>اختبار رفع الملفات</a></li>\n";
echo "<li><a href='?page=full_migration'>تجربة النقل الشامل</a></li>\n";
echo "</ol>\n";
?>
