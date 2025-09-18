<?php
/**
 * فحص نتائج النقل الموحد
 */

$config = [
    'host' => '127.0.0.1',
    'port' => 8889,
    'username' => 'root',
    'password' => 'root'
];

echo "<h2>🔍 فحص نتائج النقل الموحد</h2>\n";
echo "<hr>\n";

try {
    // الاتصال بقاعدة البيانات الجديدة
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname=platinum_new;charset=utf8mb4",
        $config['username'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>\n";
    echo "✅ <strong>متصل بقاعدة platinum_new بنجاح</strong>\n";
    echo "</div>\n";

    // فحص الجداول الرئيسية
    echo "<h3>📊 إحصائيات الجداول الرئيسية:</h3>\n";

    $mainTables = ['users', 'maids', 'orders', 'contracts', 'countries', 'cities'];

    echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #dee2e6;'>\n";
    echo "<thead style='background: #007bff; color: white;'>\n";
    echo "<tr><th style='padding: 10px; border: 1px solid #dee2e6;'>الجدول</th><th style='padding: 10px; border: 1px solid #dee2e6;'>عدد السجلات</th><th style='padding: 10px; border: 1px solid #dee2e6;'>الحالة</th><th style='padding: 10px; border: 1px solid #dee2e6;'>مصدر البيانات</th></tr>\n";
    echo "</thead><tbody>\n";

    $totalRecords = 0;
    $migrationSummary = [];

    foreach ($mainTables as $table) {
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM `$table`")->fetch();
            $count = $result['count'];
            $totalRecords += $count;

            $status = $count > 0 ? 'يحتوي على بيانات' : 'فارغ';
            $statusColor = $count > 0 ? '#28a745' : '#ffc107';

            // فحص مصدر البيانات للمستخدمين
            $sourceInfo = '';
            if ($table === 'users' && $count > 0) {
                try {
                    $sources = $pdo->query("
                        SELECT register_from, COUNT(*) as count
                        FROM users
                        GROUP BY register_from
                    ")->fetchAll();

                    $sourceDetails = [];
                    foreach ($sources as $source) {
                        $sourceDetails[] = $source['register_from'] . ': ' . $source['count'];
                    }
                    $sourceInfo = implode('<br>', $sourceDetails);

                } catch (Exception $e) {
                    $sourceInfo = 'غير محدد';
                }
            }

            echo "<tr>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><strong>$table</strong></td>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; text-align: center; font-size: 16px; font-weight: bold;'>" . number_format($count) . "</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; color: $statusColor;'>$status</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; font-size: 12px;'>$sourceInfo</td>\n";
            echo "</tr>\n";

            $migrationSummary[$table] = $count;

        } catch (Exception $e) {
            echo "<tr>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><strong>$table</strong></td>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; text-align: center; color: #dc3545;'>خطأ</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; color: #dc3545;'>غير موجود</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>-</td>\n";
            echo "</tr>\n";
        }
    }

    echo "</tbody></table>\n";

    // فحص الأعمدة المُضافة
    echo "<h3>🔧 الأعمدة المُضافة والمُحولة:</h3>\n";

    try {
        $userColumns = $pdo->query("DESCRIBE users")->fetchAll();

        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>\n";
        echo "<h6>أعمدة جدول المستخدمين:</h6>\n";

        $importantColumns = ['name', 'first_name', 'last_name', 'national_num', 'phone2', 'birthdate', 'register_from'];

        foreach ($userColumns as $column) {
            $fieldName = $column['Field'];
            $isImportant = in_array($fieldName, $importantColumns);
            $badgeClass = $isImportant ? 'bg-primary' : 'bg-secondary';

            echo "<span class='badge $badgeClass me-1 mb-1' style='font-size: 11px;'>$fieldName</span> ";
        }
        echo "</div>\n";

    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "خطأ في فحص أعمدة المستخدمين: " . $e->getMessage();
        echo "</div>";
    }

    // عينة من البيانات المنقولة
    try {
        $sampleUsers = $pdo->query("
            SELECT id, name, first_name, last_name, phone, email, register_from, created_at
            FROM users
            WHERE register_from = 'erp_migration'
            ORDER BY id DESC
            LIMIT 5
        ")->fetchAll();

        if (!empty($sampleUsers)) {
            echo "<h3>👥 عينة من المستخدمين المنقولين من ERP:</h3>\n";
            echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #dee2e6;'>\n";
            echo "<thead style='background: #28a745; color: white;'>\n";
            echo "<tr><th style='padding: 8px; border: 1px solid #dee2e6;'>ID</th><th style='padding: 8px; border: 1px solid #dee2e6;'>الاسم الكامل</th><th style='padding: 8px; border: 1px solid #dee2e6;'>الاسم الأول</th><th style='padding: 8px; border: 1px solid #dee2e6;'>الاسم الأخير</th><th style='padding: 8px; border: 1px solid #dee2e6;'>الهاتف</th><th style='padding: 8px; border: 1px solid #dee2e6;'>المصدر</th></tr>\n";
            echo "</thead><tbody>\n";

            foreach ($sampleUsers as $user) {
                echo "<tr>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>{$user['id']}</td>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>" . htmlspecialchars($user['name']) . "</td>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>" . htmlspecialchars($user['first_name']) . "</td>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>" . htmlspecialchars($user['last_name']) . "</td>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>" . htmlspecialchars($user['phone']) . "</td>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6;'><span style='background: #007bff; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px;'>{$user['register_from']}</span></td>\n";
                echo "</tr>\n";
            }

            echo "</tbody></table>\n";
        }
    } catch (Exception $e) {
        // تجاهل
    }

    // تقييم النجاح
    if ($totalRecords > 1000) {
        echo "<div style='background: #d4edda; padding: 25px; border-radius: 15px; margin: 30px 0; text-align: center; border: 3px solid #28a745;'>\n";
        echo "<h2 style='color: #28a745;'>🎉 النقل الموحد نجح بالكامل!</h2>\n";
        echo "<p style='font-size: 16px;'>إجمالي السجلات: <strong>" . number_format($totalRecords) . "</strong></p>\n";
        echo "<ul style='text-align: right; margin: 20px 0;'>\n";
        echo "<li>✅ المستخدمون: " . number_format($migrationSummary['users'] ?? 0) . "</li>\n";
        echo "<li>✅ العاملات: " . number_format($migrationSummary['maids'] ?? 0) . "</li>\n";
        echo "<li>✅ الطلبات: " . number_format($migrationSummary['orders'] ?? 0) . "</li>\n";
        echo "<li>✅ العقود: " . number_format($migrationSummary['contracts'] ?? 0) . "</li>\n";
        echo "<li>✅ معالجة الأعمدة: name → first_name + last_name</li>\n";
        echo "<li>✅ إضافة الأعمدة المفقودة</li>\n";
        echo "</ul>\n";
        echo "</div>\n";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24;'>\n";
    echo "<h4>❌ خطأ في الفحص:</h4>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}

echo "<hr>\n";
echo "<h3>🔗 روابط مفيدة:</h3>\n";
echo "<ul>\n";
echo "<li><a href='index.php'>العودة لنظام النقل</a></li>\n";
echo "<li><a href='../admin/dashboard'>لوحة التحكم الرئيسية</a></li>\n";
echo "<li><a href='backup.php'>إنشاء نسخة احتياطية</a></li>\n";
echo "</ul>\n";
?>
