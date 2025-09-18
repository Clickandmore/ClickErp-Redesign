<?php
/**
 * تحليل هيكل جداول CV/Maids والجداول المرتبطة
 */

header('Content-Type: application/json; charset=utf-8');

try {
    // الاتصال بالقواعد
    $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root');
    $newDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum_new;charset=utf8mb4", 'root', 'root');

    $analysis = [];

    // الجداول المرتبطة بـ maids
    $relatedTables = [
        'maids',
        'nationalities',
        'nationalities_translations',
        'religions',
        'religions_translations',
        'maid_translations',
        'maid_jobs',
        'maid_job_titles'
    ];

    foreach ($relatedTables as $table) {
        $tableAnalysis = [
            'table' => $table,
            'old_exists' => false,
            'new_exists' => false,
            'old_count' => 0,
            'new_count' => 0,
            'old_columns' => [],
            'new_columns' => []
        ];

        // فحص القاعدة القديمة
        try {
            $oldExists = $oldDb->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
            if ($oldExists) {
                $tableAnalysis['old_exists'] = true;
                $tableAnalysis['old_count'] = $oldDb->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                $tableAnalysis['old_columns'] = $oldDb->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
            }
        } catch (Exception $e) {
            $tableAnalysis['old_error'] = $e->getMessage();
        }

        // فحص القاعدة الجديدة
        try {
            $newExists = $newDb->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
            if ($newExists) {
                $tableAnalysis['new_exists'] = true;
                $tableAnalysis['new_count'] = $newDb->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                $tableAnalysis['new_columns'] = $newDb->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
            }
        } catch (Exception $e) {
            $tableAnalysis['new_error'] = $e->getMessage();
        }

        // مقارنة الأعمدة
        if (!empty($tableAnalysis['old_columns']) && !empty($tableAnalysis['new_columns'])) {
            $tableAnalysis['common_columns'] = array_intersect($tableAnalysis['old_columns'], $tableAnalysis['new_columns']);
            $tableAnalysis['missing_in_new'] = array_diff($tableAnalysis['old_columns'], $tableAnalysis['new_columns']);
            $tableAnalysis['extra_in_new'] = array_diff($tableAnalysis['new_columns'], $tableAnalysis['old_columns']);
        }

        $analysis[$table] = $tableAnalysis;
    }

    // تحليل cv_status في maids القديم
    $cvStatusAnalysis = [];
    if ($analysis['maids']['old_exists']) {
        try {
            $cvStatusQuery = $oldDb->query("
                SELECT
                    cv_status,
                    COUNT(*) as count,
                    CASE
                        WHEN cv_status = 0 THEN 'غير محجوز'
                        WHEN cv_status = 1 THEN 'محجوز'
                        WHEN cv_status = 2 THEN 'باك آوت'
                        WHEN cv_status = 3 THEN 'معلق'
                        WHEN cv_status = 4 THEN 'تم الترحيل'
                        WHEN cv_status = 5 THEN 'هروب'
                        ELSE 'غير معروف'
                    END as status_name
                FROM maids
                GROUP BY cv_status
                ORDER BY cv_status
            ");

            $cvStatusAnalysis = $cvStatusQuery->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $cvStatusAnalysis = ['error' => $e->getMessage()];
        }
    }

    // عينة من بيانات maids
    $maidsSample = [];
    if ($analysis['maids']['old_exists']) {
        try {
            $maidsSample = $oldDb->query("SELECT * FROM maids LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $maidsSample = ['error' => $e->getMessage()];
        }
    }

    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'tables_analysis' => $analysis,
        'cv_status_breakdown' => $cvStatusAnalysis,
        'maids_sample' => $maidsSample,
        'migration_strategy' => [
            'step1' => 'نقل الجداول المرجعية أولاً (nationalities, religions)',
            'step2' => 'تحويل IDs والتأكد من التطابق',
            'step3' => 'نقل maids مع معالجة cv_status بعناية',
            'step4' => 'نقل الترجمات والبيانات الإضافية'
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في التحليل: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
