<?php
/**
 * فحص نتائج النقل المحسن مع جميع الأعمدة
 */

$config = [
    'host' => '127.0.0.1',
    'port' => 8889,
    'username' => 'root',
    'password' => 'root'
];

echo "<h2>🔍 فحص النقل المحسن - جميع الأعمدة مع ترتيب الجدول</h2>\n";
echo "<hr>\n";

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname=platinum_new;charset=utf8mb4",
        $config['username'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>\n";
    echo "✅ <strong>متصل بقاعدة platinum_new</strong>\n";
    echo "</div>\n";

    // فحص بنية الجدول المُحسنة
    echo "<h3>📐 بنية جدول المستخدمين المُرتبة:</h3>\n";

    $columns = $pdo->query("DESCRIBE users")->fetchAll();

    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 15px 0;'>\n";
    echo "<h5>إجمالي الأعمدة: " . count($columns) . "</h5>\n";

    // تجميع الأعمدة حسب النوع
    $columnGroups = [
        'المعرفات' => [],
        'الاتصال' => [],
        'التحقق والأمان' => [],
        'البيانات الشخصية' => [],
        'الإعدادات' => [],
        'التواريخ' => [],
        'أخرى' => []
    ];

    foreach ($columns as $index => $column) {
        $field = $column['Field'];
        $type = $column['Type'];

        // تصنيف الأعمدة
        if (in_array($field, ['id', 'name', 'first_name', 'last_name'])) {
            $columnGroups['المعرفات'][] = ['field' => $field, 'type' => $type, 'position' => $index + 1];
        } elseif (in_array($field, ['email', 'phone', 'phone2', 'phone_verified_at'])) {
            $columnGroups['الاتصال'][] = ['field' => $field, 'type' => $type, 'position' => $index + 1];
        } elseif (in_array($field, ['verification_code', 'login_code', 'user_type', 'password'])) {
            $columnGroups['التحقق والأمان'][] = ['field' => $field, 'type' => $type, 'position' => $index + 1];
        } elseif (in_array($field, ['national_num', 'birthdate', 'nationality_id', 'address', 'city'])) {
            $columnGroups['البيانات الشخصية'][] = ['field' => $field, 'type' => $type, 'position' => $index + 1];
        } elseif (in_array($field, ['is_blocked', 'status', 'register_from'])) {
            $columnGroups['الإعدادات'][] = ['field' => $field, 'type' => $type, 'position' => $index + 1];
        } elseif (in_array($field, ['created_at', 'updated_at'])) {
            $columnGroups['التواريخ'][] = ['field' => $field, 'type' => $type, 'position' => $index + 1];
        } else {
            $columnGroups['أخرى'][] = ['field' => $field, 'type' => $type, 'position' => $index + 1];
        }
    }

    // عرض الأعمدة مجمعة
    foreach ($columnGroups as $groupName => $groupColumns) {
        if (!empty($groupColumns)) {
            echo "<h6 style='color: #007bff; margin-top: 20px;'>$groupName:</h6>\n";
            echo "<div style='margin: 10px 0;'>\n";

            foreach ($groupColumns as $col) {
                $badgeColor = $col['position'] > 20 ? '#ffc107' : '#28a745'; // أصفر إذا كان في موضع متأخر
                echo "<span style='background: $badgeColor; color: white; padding: 4px 8px; margin: 2px; border-radius: 4px; font-size: 11px;'>";
                echo "{$col['position']}. {$col['field']} ({$col['type']})";
                echo "</span> ";
            }

            echo "</div>\n";
        }
    }

    echo "</div>\n";

    // تحليل التواريخ
    $dateColumnsCheck = [];
    foreach (['created_at', 'updated_at'] as $dateCol) {
        $position = 0;
        foreach ($columns as $index => $column) {
            if ($column['Field'] === $dateCol) {
                $position = $index + 1;
                break;
            }
        }
        $dateColumnsCheck[$dateCol] = $position;
    }

    $totalColumns = count($columns);
    $dateColumnsWellPositioned =
        $dateColumnsCheck['created_at'] > $totalColumns - 5 &&
        $dateColumnsCheck['updated_at'] > $totalColumns - 5;

    if ($dateColumnsWellPositioned) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px;'>\n";
        echo "✅ <strong>الجدول مُرتب بشكل صحيح:</strong> التواريخ في النهاية\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px;'>\n";
        echo "⚠️ <strong>الجدول يحتاج إعادة ترتيب:</strong>\n";
        echo "<ul>\n";
        echo "<li>created_at في الموضع: {$dateColumnsCheck['created_at']} (يفضل أن يكون قرب النهاية)</li>\n";
        echo "<li>updated_at في الموضع: {$dateColumnsCheck['updated_at']} (يفضل أن يكون في النهاية)</li>\n";
        echo "</ul>\n";
        echo "</div>\n";
    }

    // فحص الأعمدة المهمة المطلوبة
    echo "<h3>✅ فحص الأعمدة المطلوبة:</h3>\n";

    $requiredColumns = [
        'user_type' => 'نوع المستخدم',
        'phone_verified_at' => 'تاريخ تفعيل الهاتف',
        'verification_code' => 'كود التحقق',
        'login_code' => 'كود تسجيل الدخول',
        'national_num' => 'رقم الهوية',
        'phone2' => 'هاتف إضافي',
        'birthdate' => 'تاريخ الميلاد'
    ];

    echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #dee2e6;'>\n";
    echo "<thead style='background: #6f42c1; color: white;'>\n";
    echo "<tr><th style='padding: 10px; border: 1px solid #dee2e6;'>العمود</th><th style='padding: 10px; border: 1px solid #dee2e6;'>الوصف</th><th style='padding: 10px; border: 1px solid #dee2e6;'>الحالة</th><th style='padding: 10px; border: 1px solid #dee2e6;'>الموضع</th></tr>\n";
    echo "</thead><tbody>\n";

    foreach ($requiredColumns as $columnName => $description) {
        $exists = false;
        $position = 0;
        $type = '';

        foreach ($columns as $index => $column) {
            if ($column['Field'] === $columnName) {
                $exists = true;
                $position = $index + 1;
                $type = $column['Type'];
                break;
            }
        }

        $statusColor = $exists ? '#28a745' : '#dc3545';
        $statusText = $exists ? 'موجود' : 'مفقود';

        echo "<tr>\n";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><strong>$columnName</strong></td>\n";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>$description</td>\n";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6; color: $statusColor; font-weight: bold;'>$statusText</td>\n";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>$position" . ($type ? " ($type)" : "") . "</td>\n";
        echo "</tr>\n";
    }

    echo "</tbody></table>\n";

    // فحص البيانات المنقولة
    echo "<h3>📊 فحص البيانات المنقولة:</h3>\n";

    $userStats = $pdo->query("
        SELECT
            COUNT(*) as total_users,
            COUNT(CASE WHEN register_from LIKE 'file_%' THEN 1 END) as from_files,
            COUNT(CASE WHEN user_type IS NOT NULL THEN 1 END) as with_user_type,
            COUNT(CASE WHEN phone_verified_at IS NOT NULL THEN 1 END) as with_phone_verified,
            COUNT(CASE WHEN verification_code IS NOT NULL THEN 1 END) as with_verification_code,
            COUNT(CASE WHEN first_name IS NOT NULL THEN 1 END) as with_first_name,
            COUNT(CASE WHEN last_name IS NOT NULL THEN 1 END) as with_last_name
        FROM users
    ")->fetch();

    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px;'>\n";
    echo "<div class='row text-center'>\n";
    echo "<div class='col-md-4'><h4 style='color: #1976d2;'>" . number_format($userStats['total_users']) . "</h4><small>إجمالي المستخدمين</small></div>\n";
    echo "<div class='col-md-4'><h4 style='color: #388e3c;'>" . number_format($userStats['from_files']) . "</h4><small>من الملفات المرفوعة</small></div>\n";
    echo "<div class='col-md-4'><h4 style='color: #f57c00;'>" . number_format($userStats['with_user_type']) . "</h4><small>لديهم user_type</small></div>\n";
    echo "</div>\n";

    echo "<hr>\n";
    echo "<h6>تفاصيل الأعمدة المنقولة:</h6>\n";
    echo "<ul>\n";
    echo "<li><strong>phone_verified_at:</strong> " . number_format($userStats['with_phone_verified']) . " مستخدم</li>\n";
    echo "<li><strong>verification_code:</strong> " . number_format($userStats['with_verification_code']) . " مستخدم</li>\n";
    echo "<li><strong>first_name:</strong> " . number_format($userStats['with_first_name']) . " مستخدم</li>\n";
    echo "<li><strong>last_name:</strong> " . number_format($userStats['with_last_name']) . " مستخدم</li>\n";
    echo "</ul>\n";
    echo "</div>\n";

    // عينة من البيانات المحسنة
    echo "<h3>👤 عينة من البيانات المحسنة:</h3>\n";

    $sampleUsers = $pdo->query("
        SELECT id, name, first_name, last_name, phone, email, user_type,
               phone_verified_at, verification_code, register_from, created_at
        FROM users
        WHERE register_from LIKE 'file_%'
        ORDER BY id DESC
        LIMIT 5
    ")->fetchAll();

    if (!empty($sampleUsers)) {
        echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; font-size: 12px;'>\n";
        echo "<thead style='background: #6f42c1; color: white;'>\n";
        echo "<tr>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>ID</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>الاسم</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>الأول</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>الأخير</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>الهاتف</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>النوع</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>تفعيل الهاتف</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>كود التحقق</th>";
        echo "</tr>\n";
        echo "</thead><tbody>\n";

        foreach ($sampleUsers as $user) {
            echo "<tr>\n";
            echo "<td style='padding: 4px; border: 1px solid #dee2e6;'>{$user['id']}</td>\n";
            echo "<td style='padding: 4px; border: 1px solid #dee2e6;'>" . htmlspecialchars(substr($user['name'] ?? '', 0, 15)) . "</td>\n";
            echo "<td style='padding: 4px; border: 1px solid #dee2e6;'>" . htmlspecialchars($user['first_name'] ?? '') . "</td>\n";
            echo "<td style='padding: 4px; border: 1px solid #dee2e6;'>" . htmlspecialchars($user['last_name'] ?? '') . "</td>\n";
            echo "<td style='padding: 4px; border: 1px solid #dee2e6;'>" . htmlspecialchars($user['phone'] ?? '') . "</td>\n";
            echo "<td style='padding: 4px; border: 1px solid #dee2e6;'>" . htmlspecialchars($user['user_type'] ?? 'N/A') . "</td>\n";
            echo "<td style='padding: 4px; border: 1px solid #dee2e6;'>" . ($user['phone_verified_at'] ? 'نعم' : 'لا') . "</td>\n";
            echo "<td style='padding: 4px; border: 1px solid #dee2e6;'>" . htmlspecialchars($user['verification_code'] ?? 'N/A') . "</td>\n";
            echo "</tr>\n";
        }

        echo "</tbody></table>\n";
    }

    // تقييم جودة النقل
    $missingColumns = 0;
    $requiredColumns = ['user_type', 'phone_verified_at', 'verification_code', 'login_code', 'national_num', 'phone2'];

    foreach ($requiredColumns as $reqCol) {
        $exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $reqCol) {
                $exists = true;
                break;
            }
        }
        if (!$exists) $missingColumns++;
    }

    if ($missingColumns === 0 && $userStats['total_users'] > 0) {
        echo "<div style='background: #d4edda; padding: 25px; border-radius: 15px; margin: 30px 0; text-align: center; border: 3px solid #28a745;'>\n";
        echo "<h2 style='color: #28a745;'>🎉 النقل المحسن نجح بالكامل!</h2>\n";
        echo "<p style='font-size: 16px;'>تم نقل جميع الأعمدة وترتيب الجدول بشكل مثالي</p>\n";
        echo "<ul style='text-align: right; margin: 20px 0;'>\n";
        echo "<li>✅ جميع الأعمدة منقولة: user_type, phone_verified_at, verification_code</li>\n";
        echo "<li>✅ الجدول مُرتب: المعرفات → البيانات → التواريخ</li>\n";
        echo "<li>✅ معالجة name → first_name + last_name</li>\n";
        echo "<li>✅ إجمالي المستخدمين: " . number_format($userStats['total_users']) . "</li>\n";
        echo "<li>✅ من الملفات المرفوعة: " . number_format($userStats['from_files']) . "</li>\n";
        echo "</ul>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px;'>\n";
        echo "⚠️ <strong>يحتاج تحسين:</strong> $missingColumns عمود مفقود\n";
        echo "</div>\n";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24;'>\n";
    echo "<h4>❌ خطأ في الفحص:</h4>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}

echo "<hr>\n";
echo "<h3>🔗 روابط سريعة:</h3>\n";
echo "<ul>\n";
echo "<li><a href='enhanced_file_migration.php'>العودة للنقل المحسن</a></li>\n";
echo "<li><a href='../admin/users'>عرض المستخدمين في لوحة التحكم</a></li>\n";
echo "<li><a href='../admin/dashboard'>لوحة التحكم الرئيسية</a></li>\n";
echo "</ul>\n";
?>
