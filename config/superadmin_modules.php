<?php

return [
    'operations' => [
        'name' => 'إدارة التشغيل',
        'description' => 'العقود، الطلبات، السير الذاتية، الشكاوى، النقل، السفر والتشغيل اليومي.',
        'route_patterns' => [
            'maids.*',
            'noneAvailableMaids',
            'orders.*',
            'return_requests.*',
            'professional_application.*',
            'recruitment_requests.*',
            'renew-contracts.*',
            'known-service-requests.*',
            'contact.*',
            'RecruitmentContract.*',
            'RentContract.*',
            'rental-orders.*',
            'rent-contract.*',
            'rent-contract-terms.*',
            'ingaz-delegations.*',
            'ShelterContract.*',
            'shelter_workers',
            'shelter-places.*',
            'DeportationTravel.*',
            'travel',
            'complaints.*',
            'complaint-types.*',
            'SponsorshipTransfer.*',
        ],
        'path_patterns' => [
            'dashboard/reports*',
        ],
    ],

    'website' => [
        'name' => 'الموقع الخارجي',
        'description' => 'إعدادات وبيانات واجهة العميل والمحتوى العام.',
        'route_patterns' => [
            'settings.*',
            'sliders.*',
            'about-us.*',
            'questions.*',
            'services.*',
            'statistics.*',
            'steps.*',
            'partenrs.*',
            'fast-news.*',
            'features.*',
            'what-do.*',
            'what-do-setting.*',
            'select-workers.*',
            'select-workers-settings.*',
            'requiremens.*',
        ],
        'path_patterns' => [],
    ],

    'api_links' => [
        'name' => 'روابط API والربط',
        'description' => 'إعدادات الرسائل، واتساب، SMS، والتنبيهات وروابط التكامل.',
        'route_patterns' => [
            'notifications.*',
            'whatsapp.notifications.*',
            'messages.*',
            'admin.sms-templates.*',
        ],
        'path_patterns' => [
            'admin/sms-templates*',
        ],
    ],

    'crm' => [
        'name' => 'CRM',
        'description' => 'العملاء وقوائم إدارة علاقات العملاء.',
        'route_patterns' => [
            'clients.*',
            'crm.*',
        ],
        'path_patterns' => [
            'crm*',
        ],
    ],

    'hr' => [
        'name' => 'الموارد البشرية',
        'description' => 'حزمة HR وأقسام الموظفين والدوام والرواتب المرتبطة بها.',
        'route_patterns' => [
            'hr.*',
            'departments-*',
            'jobs-*',
            'employees-*',
            'vacations-*',
            'permissions-*',
            'shifts-*',
        ],
        'path_patterns' => [
            'hr*',
        ],
    ],

    'finance' => [
        'name' => 'المالية والفوترة',
        'description' => 'حزمة المالية، الفواتير، القيود، والزكاة والضريبة.',
        'route_patterns' => [
            'finance.*',
            'invoices.*',
            'musaned-transfers.*',
        ],
        'path_patterns' => [
            'finance*',
            'zatca*',
        ],
    ],
];
