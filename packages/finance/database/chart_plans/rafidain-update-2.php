<?php

// استكمال طلب العميل بعد تنفيذ rafidain-update-1: نقل العهدتين من النقدية إلى عهد الموظفين.
// هذا بالضبط ما نُفذ على اللايف في 2026-11-02؛ لا تعدّل خطة منفذة، أضف خطة جديدة (مثل rafidain-update-3).
// معاينة: php artisan finance:chart-restructure --plan=rafidain-update-2
// حفظ: php artisan finance:chart-restructure --plan=rafidain-update-2 --execute
return [
    'keep_codes' => true,
    'requires' => ['rafidain-update-1'],
    'strict_names' => true,

    'moves' => [
        '12010003' => 'c:1206',
        '12010004' => 'c:1206',
    ],

    'type_labels' => [
        '12010003' => 'عهد الموظفين',
        '12010004' => 'عهد الموظفين',
    ],

    'expected_names' => [
        '1206' => 'عهد الموظفين',
        '12010003' => 'عهدة عبد الواحد الفرج',
        '12010004' => 'عهدة احمد السعيد',
    ],
];
