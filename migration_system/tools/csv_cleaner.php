<?php
/**
 * أداة تنظيف ملفات CSV
 * CSV File Cleaner Tool
 */

function cleanCsvFile($input_file, $output_file = null) {
    if (!file_exists($input_file)) {
        throw new Exception("الملف غير موجود: $input_file");
    }

    $output_file = $output_file ?: str_replace('.csv', '_cleaned.csv', $input_file);

    $cleaned_data = [];
    $headers = [];
    $row_count = 0;

    if (($handle = fopen($input_file, "r")) !== false) {
        // قراءة العناوين وتنظيفها
        if (($row = fgetcsv($handle, 0, ',')) !== false) {
            $headers = array_map(function($header) {
                return trim($header, '"\'');
            }, $row);

            // إزالة العناوين الفارغة
            $headers = array_filter($headers, function($header) {
                return !empty(trim($header));
            });

            $cleaned_data[] = $headers;
        }

        // قراءة البيانات وتنظيفها
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $row_count++;

            // التأكد من تطابق عدد الحقول
            if (count($row) >= count($headers)) {
                // أخذ فقط عدد الحقول المطلوب
                $cleaned_row = array_slice($row, 0, count($headers));

                // تنظيف البيانات
                $cleaned_row = array_map(function($cell) {
                    $cell = trim($cell, '"\'');
                    return ($cell === 'NULL' || $cell === '') ? null : $cell;
                }, $cleaned_row);

                $cleaned_data[] = $cleaned_row;
            }
        }

        fclose($handle);
    }

    // كتابة الملف المنظف
    if (($handle = fopen($output_file, 'w')) !== false) {
        foreach ($cleaned_data as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }

    return [
        'input_file' => $input_file,
        'output_file' => $output_file,
        'original_rows' => $row_count,
        'cleaned_rows' => count($cleaned_data) - 1, // -1 للعناوين
        'headers_count' => count($headers)
    ];
}

// تشغيل الأداة إذا تم استدعاؤها مباشرة
if (php_sapi_name() === 'cli') {
    $input_file = $argv[1] ?? null;

    if (!$input_file) {
        echo "الاستخدام: php csv_cleaner.php input_file.csv [output_file.csv]\n";
        exit(1);
    }

    $output_file = $argv[2] ?? null;

    try {
        $result = cleanCsvFile($input_file, $output_file);

        echo "تم تنظيف الملف بنجاح!\n";
        echo "الملف الأصلي: {$result['input_file']}\n";
        echo "الملف المنظف: {$result['output_file']}\n";
        echo "الصفوف الأصلية: {$result['original_rows']}\n";
        echo "الصفوف المنظفة: {$result['cleaned_rows']}\n";
        echo "عدد الحقول: {$result['headers_count']}\n";

    } catch (Exception $e) {
        echo "خطأ: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
