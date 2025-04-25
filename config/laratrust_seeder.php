<?php

return [
    /**
     * Control if the seeder should create a user per role while seeding the data.
     */
    'create_users' => false,

    /**
     * Control if all the laratrust tables should be truncated before running the seeder.
     */
    'truncate_tables' => true,

    'roles_structure' => [
        'super_admin' => [
            'المستخدمون' => 'c,r,u,d',
            'الإعدادات' => 'c,r,u,d',
            'المسميات الوظيفية' => 'c,r,u,d',
            'الجنسيات' => 'c,r,u,d',
            'بانر العرض' => 'c,r,u,d',
            'الديانات' => 'c,r,u,d',
            'الأسئلة' => 'c,r,u,d',
            'الاتصال' => 'c,r,u,d',
            'الخدمات' => 'c,r,u,d',
            'عنا' => 'c,r,u,d',
            'العملاء' => 'c,r,u,d',
            'العاملات' => 'c,r,u,d',
            'الباقات' => 'c,r,u,d',
            'الشكاوى' => 'c,r,u,d',
            'الطلبات' => 'c,r,u,d',
            'الإشعارات' => 'c,r,u,d',
            'الإحصائيات' => 'c,r,u,d',
            'الخطوات' => 'c,r,u,d',
            'مكاتب الاستقدام' => 'c,r,u,d',
            'الأدوار' => 'c,r,u,d',
            'طلبات الارجاع' => 'c,r,u,d',
            'مقرات الايواء' => 'c,r,u,d',
            'العقود' => 'c,r,u,d',
        ],
        'admin' => [],
        'customer_service' => [
            'الطلبات' => 'c,r,u,d',
        ]

    ],

    'permissions_map' => [
        'c' => 'انشاء',
        'r' => 'قراءة',
        'u' => 'تعديل',
        'd' => 'حذف'
    ]
];
