<?php

use App\NewPermKeys;

return [
    // تُعرض هذه الصلاحيات في شاشة الأدوار، ولا يمنحها أمر تجهيز السيرفر تلقائياً.
    'permissions' => [
        NewPermKeys::REC_CONTRACTS_VIEW_SHELTER,
        NewPermKeys::REC_CONTRACTS_LATE_FINANCE,
    ],
];
