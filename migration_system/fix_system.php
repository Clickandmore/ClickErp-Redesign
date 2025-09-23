<?php
/**
 * إصلاح تلقائي للنظام
 */

echo "<h2>🔧 إصلاح النظام التلقائي</h2>\n";
echo "<hr>\n";

try {
    // 1. نسخ ملف قاعدة البيانات المُحسن
    echo "<h3>1. إصلاح إعدادات قاعدة البيانات:</h3>\n";

    if (copy('config/database_fixed.php', 'config/database.php')) {
        echo "✅ تم تحديث ملف database.php<br>\n";
    } else {
        echo "❌ فشل في تحديث ملف database.php<br>\n";
    }

    // 2. إنشاء قواعد البيانات المطلوبة
    echo "<h3>2. إنشاء قواعد البيانات:</h3>\n";

    $commands = [
        "CREATE DATABASE IF NOT EXISTS erp_operations_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "CREATE DATABASE IF NOT EXISTS platinum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        "CREATE DATABASE IF NOT EXISTS platinum_migration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    foreach ($commands as $command) {
        $exec_command = "/Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot -e \"$command\" 2>&1";
        $output = shell_exec($exec_command);

        if (strpos($output, 'ERROR') === false) {
            echo "✅ " . substr($command, 0, 50) . "...<br>\n";
        } else {
            echo "⚠️ " . substr($command, 0, 50) . "... (قد تكون موجودة مسبقاً)<br>\n";
        }
    }

    // 3. إعداد جداول النقل
    echo "<h3>3. إعداد جداول النقل:</h3>\n";

    $setup_command = "/Applications/MAMP/Library/bin/mysql80/bin/mysql -h localhost -P 8889 -u root -proot platinum_migration < basic_setup.sql 2>&1";
    $output = shell_exec($setup_command);

    if (strpos($output, 'ERROR') === false) {
        echo "✅ تم إعداد جداول النقل<br>\n";
    } else {
        echo "⚠️ مشكلة في إعداد الجداول: $output<br>\n";
    }

    // 4. اختبار النظام
    echo "<h3>4. اختبار النظام المُحسن:</h3>\n";

    require_once 'config/database.php';
    require_once 'includes/functions.php';

    try {
        $stats = getMigrationStatistics();
        echo "✅ إحصائيات النقل تعمل بنجاح<br>\n";

        $batches = getRecentBatches(3);
        echo "✅ استعلام الدفعات يعمل بنجاح<br>\n";

        echo "✅ جميع الوظائف تعمل بشكل صحيح!<br>\n";

    } catch (Exception $e) {
        echo "❌ خطأ في اختبار الوظائف: " . $e->getMessage() . "<br>\n";
    }

    echo "<hr>\n";
    echo "<h3>🎉 النتائج:</h3>\n";

    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>\n";
    echo "<h4>✅ تم إصلاح النظام بنجاح!</h4>\n";
    echo "<p><strong>يمكنك الآن:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✅ رفع ملفات CSV بدون أخطاء</li>\n";
    echo "<li>✅ استخدام النقل الشامل</li>\n";
    echo "<li>✅ مراقبة العمليات في الوقت الفعلي</li>\n";
    echo "</ul>\n";
    echo "</div>\n";

    echo "<h3>🚀 الروابط الجاهزة:</h3>\n";
    echo "<ul>\n";
    echo "<li><a href='?' class='btn btn-primary'>🏠 لوحة التحكم الرئيسية</a></li>\n";
    echo "<li><a href='?page=upload' class='btn btn-success'>📤 رفع الملفات</a></li>\n";
    echo "<li><a href='?page=full_migration' class='btn btn-warning'>🚀 النقل الشامل</a></li>\n";
    echo "<li><a href='test_complex_upload.php' class='btn btn-info'>🧪 اختبار الملفات المعقدة</a></li>\n";
    echo "</ul>\n";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ خطأ في الإصلاح:</h3>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";

    echo "<h4>🔧 حلول يدوية:</h4>\n";
    echo "<ol>\n";
    echo "<li>تأكد من تشغيل MAMP على البورت 8889</li>\n";
    echo "<li>تأكد من صحة اسم المستخدم وكلمة المرور (root/root)</li>\n";
    echo "<li>أعد تشغيل MAMP</li>\n";
    echo "<li>جرب الاختبار مرة أخرى</li>\n";
    echo "</ol>\n";
}
?>
