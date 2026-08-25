<?php

return [
    'recruitment' => [
        'name' => 'نظام الاستقدام',
        'description' => 'النسخة الحالية بكامل عمليات الاستقدام والعقود والعمالة.',
        'default_dashboard' => 'recruitment',
        'required_modules' => [],
    ],

    'general' => [
        'name' => 'نظام عام',
        'description' => 'نسخة ERP عامة بدون عمليات الاستقدام المتخصصة.',
        'default_dashboard' => 'finance',
        'required_modules' => ['finance'],
    ],
];
