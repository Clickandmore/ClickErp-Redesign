<?php
/**
 * فحص نتائج النقل من الملفات المرفوعة
 */

$config = [
    'host' => '127.0.0.1',
    'port' => 8889,
    'username' => 'root',
    'password' => 'root'
];

echo "<h2>🔍 فحص نتائج النقل من الملفات المرفوعة</h2>\n";
echo "<hr>\n";

try {
    // الاتصال بقاعدة البيانات
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname=platinum_new;charset=utf8mb4",
        $config['username'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>\n";
    echo "✅ <strong>متصل بقاعدة platinum_new بنجاح</strong>\n";
    echo "</div>\n";

    // فحص المستخدمين حسب مصدر البيانات
    echo "<h3>👥 تفاصيل المستخدمين حسب المصدر:</h3>\n";

    try {
        $userSources = $pdo->query("
            SELECT
                register_from,
                COUNT(*) as count,
                MIN(created_at) as first_record,
                MAX(created_at) as last_record
            FROM users
            GROUP BY register_from
            ORDER BY count DESC
        ")->fetchAll();

        echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #dee2e6;'>\n";
        echo "<thead style='background: #007bff; color: white;'>\n";
        echo "<tr><th style='padding: 10px; border: 1px solid #dee2e6;'>مصدر البيانات</th><th style='padding: 10px; border: 1px solid #dee2e6;'>عدد المستخدمين</th><th style='padding: 10px; border: 1px solid #dee2e6;'>أول سجل</th><th style='padding: 10px; border: 1px solid #dee2e6;'>آخر سجل</th></tr>\n";
        echo "</thead><tbody>\n";

        $totalUsers = 0;
        foreach ($userSources as $source) {
            $totalUsers += $source['count'];

            $sourceColor = strpos($source['register_from'], 'file_') === 0 ? '#28a745' :
                          ($source['register_from'] === 'erp_migration' ? '#dc3545' : '#6c757d');

            echo "<tr>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><span style='background: $sourceColor; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;'>{$source['register_from']}</span></td>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; text-align: center; font-size: 16px; font-weight: bold;'>" . number_format($source['count']) . "</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; font-size: 12px;'>{$source['first_record']}</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; font-size: 12px;'>{$source['last_record']}</td>\n";
            echo "</tr>\n";
        }

        echo "</tbody></table>\n";

        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0;'>\n";
        echo "<h5>📊 ملخص المستخدمين:</h5>\n";
        echo "<p><strong>إجمالي المستخدمين:</strong> " . number_format($totalUsers) . "</p>\n";

        // تحديد مصدر الملفات المرفوعة
        $fileUploaded = false;
        foreach ($userSources as $source) {
            if (strpos($source['register_from'], 'file_') === 0) {
                echo "<p style='color: #28a745;'><strong>من الملف المرفوع:</strong> " . number_format($source['count']) . " مستخدم</p>\n";
                $fileUploaded = true;
            }
        }

        if (!$fileUploaded) {
            echo "<p style='color: #dc3545;'><strong>تحذير:</strong> لا توجد بيانات من الملفات المرفوعة!</p>\n";
        }

        echo "</div>\n";

    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "خطأ في فحص المستخدمين: " . $e->getMessage();
        echo "</div>";
    }

    // فحص العاملات
    echo "<h3>👩‍💼 تفاصيل العاملات:</h3>\n";

    try {
        $maidsCount = $pdo->query("SELECT COUNT(*) FROM maids")->fetchColumn();

        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px;'>\n";
        echo "<h5>إجمالي العاملات: " . number_format($maidsCount) . "</h5>\n";

        if ($maidsCount > 0) {
            // عينة من العاملات
            $sampleMaids = $pdo->query("
                SELECT id, name, passport_num, nationality_id, created_at
                FROM maids
                ORDER BY id DESC
                LIMIT 5
            ")->fetchAll();

            echo "<h6>عينة من العاملات:</h6>\n";
            foreach ($sampleMaids as $maid) {
                echo "<p><strong>ID:</strong> {$maid['id']} | <strong>الاسم:</strong> " . htmlspecialchars($maid['name']) . " | <strong>الجواز:</strong> {$maid['passport_num']}</p>\n";
            }
        }

        echo "</div>\n";

    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "خطأ في فحص العاملات: " . $e->getMessage();
        echo "</div>";
    }

    // فحص الملفات المرفوعة
    echo "<h3>📁 الملفات المرفوعة المعالجة:</h3>\n";

    $uploadDir = __DIR__ . '/uploaded_files/';
    if (is_dir($uploadDir)) {
        $uploadedFiles = glob($uploadDir . '*.csv');

        if (!empty($uploadedFiles)) {
            echo "<table style='width: 100%; border-collapse: collapse; border: 1px solid #dee2e6;'>\n";
            echo "<thead style='background: #28a745; color: white;'>\n";
            echo "<tr><th style='padding: 8px; border: 1px solid #dee2e6;'>اسم الملف</th><th style='padding: 8px; border: 1px solid #dee2e6;'>الحجم</th><th style='padding: 8px; border: 1px solid #dee2e6;'>عدد السجلات</th><th style='padding: 8px; border: 1px solid #dee2e6;'>تاريخ الرفع</th></tr>\n";
            echo "</thead><tbody>\n";

            foreach ($uploadedFiles as $file) {
                $fileName = basename($file);
                $fileSize = formatFileSize(filesize($file));
                $analysis = analyzeCSVFileQuick($file);
                $uploadDate = date('Y-m-d H:i:s', filemtime($file));

                echo "<tr>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6;'><strong>$fileName</strong></td>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>$fileSize</td>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6; text-align: center; color: #dc3545; font-weight: bold;'>" . number_format($analysis['total_rows']) . "</td>\n";
                echo "<td style='padding: 6px; border: 1px solid #dee2e6; font-size: 11px;'>$uploadDate</td>\n";
                echo "</tr>\n";
            }

            echo "</tbody></table>\n";

            // مقارنة مع النتائج
            $fileRecordsTotal = 0;
            foreach ($uploadedFiles as $file) {
                $analysis = analyzeCSVFileQuick($file);
                $fileRecordsTotal += $analysis['total_rows'];
            }

            echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border: 2px solid #ffc107;'>\n";
            echo "<h4>📊 مقارنة الأعداد:</h4>\n";
            echo "<div style='display: flex; justify-content: space-around; text-align: center;'>\n";
            echo "<div><h5 style='color: #dc3545;'>📁 الملفات المرفوعة</h5><p style='font-size: 24px; font-weight: bold;'>" . number_format($fileRecordsTotal) . "</p></div>\n";
            echo "<div><h5 style='color: #28a745;'>📊 السجلات المنقولة</h5><p style='font-size: 24px; font-weight: bold;'>" . number_format($totalUsers) . "</p></div>\n";
            echo "</div>\n";

            if ($fileRecordsTotal === $totalUsers) {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; text-align: center;'>\n";
                echo "<h5 style='color: #28a745;'>✅ تطابق كامل! الأعداد متطابقة تماماً</h5>\n";
                echo "</div>\n";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center;'>\n";
                echo "<h5 style='color: #dc3545;'>⚠️ عدم تطابق! يحتاج إعادة معالجة</h5>\n";
                echo "</div>\n";
            }

            echo "</div>\n";

        } else {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px;'>\n";
            echo "⚠️ <strong>لا توجد ملفات مرفوعة في المجلد</strong>\n";
            echo "</div>\n";
        }
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px;'>\n";
        echo "❌ <strong>مجلد الملفات المرفوعة غير موجود</strong>\n";
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
echo "<li><a href='file_only_migration.php'>العودة لنظام النقل من الملفات</a></li>\n";
echo "<li><a href='../admin/dashboard'>لوحة التحكم الرئيسية</a></li>\n";
echo "</ul>\n";

/**
 * وظائف مساعدة
 */
function analyzeCSVFileQuick($filePath) {
    $totalRows = 0;

    if (($handle = fopen($filePath, "r")) !== false) {
        // تخطي العناوين
        fgetcsv($handle, 0, ',');

        // عد الصفوف
        while (fgetcsv($handle, 0, ',') !== false) {
            $totalRows++;
        }

        fclose($handle);
    }

    return ['total_rows' => $totalRows];
}

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
?>
