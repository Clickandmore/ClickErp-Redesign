<?php
/**
 * البحث عن جداول CV في القاعدة القديمة
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $oldDb = new PDO("mysql:host=127.0.0.1;port=8889;dbname=platinum;charset=utf8mb4", 'root', 'root');

    // الحصول على جميع الجداول في القاعدة القديمة
    $tables = $oldDb->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $cvRelatedTables = [];
    $potentialCVTables = [];

    // البحث عن الجداول المحتملة لـ CV
    $cvKeywords = ['cv', 'maid', 'employee', 'worker', 'staff', 'person'];

    foreach ($tables as $table) {
        $tableLower = strtolower($table);

        foreach ($cvKeywords as $keyword) {
            if (strpos($tableLower, $keyword) !== false) {
                // فحص محتوى الجدول
                try {
                    $count = $oldDb->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                    $columns = $oldDb->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);

                    // البحث عن أعمدة مميزة للـ CV
                    $cvColumns = array_intersect($columns, [
                        'cv_status', 'cv_file', 'nationality_id', 'national_id',
                        'religion_id', 'passport_num', 'age', 'experience'
                    ]);

                    $potentialCVTables[] = [
                        'table' => $table,
                        'count' => $count,
                        'columns' => $columns,
                        'cv_related_columns' => $cvColumns,
                        'likelihood' => count($cvColumns)
                    ];
                } catch (Exception $e) {
                    // تجاهل الجداول التي لا يمكن الوصول إليها
                }
                break;
            }
        }
    }

    // ترتيب حسب الاحتمالية
    usort($potentialCVTables, function($a, $b) {
        return $b['likelihood'] - $a['likelihood'];
    });

    // البحث عن الجداول المرتبطة
    $relatedKeywords = ['religion', 'nationality', 'nation', 'language', 'skill', 'job', 'profession'];

    foreach ($tables as $table) {
        $tableLower = strtolower($table);

        foreach ($relatedKeywords as $keyword) {
            if (strpos($tableLower, $keyword) !== false) {
                try {
                    $count = $oldDb->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                    $columns = $oldDb->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);

                    $cvRelatedTables[] = [
                        'table' => $table,
                        'count' => $count,
                        'columns' => $columns,
                        'type' => $keyword
                    ];
                } catch (Exception $e) {
                    // تجاهل
                }
                break;
            }
        }
    }

    // عينة من البيانات للجدول الأكثر احتمالاً
    $sampleData = [];
    if (!empty($potentialCVTables)) {
        $topTable = $potentialCVTables[0]['table'];
        try {
            $sampleData = $oldDb->query("SELECT * FROM $topTable LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $sampleData = ['error' => $e->getMessage()];
        }
    }

    echo json_encode([
        'success' => true,
        'total_tables' => count($tables),
        'all_tables' => $tables,
        'potential_cv_tables' => $potentialCVTables,
        'related_tables' => $cvRelatedTables,
        'top_candidate' => !empty($potentialCVTables) ? $potentialCVTables[0] : null,
        'sample_data' => $sampleData,
        'recommendation' => !empty($potentialCVTables) ?
            "الجدول الأكثر احتمالاً: {$potentialCVTables[0]['table']} ({$potentialCVTables[0]['count']} سجل)" :
            'لم يتم العثور على جداول CV محتملة'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
