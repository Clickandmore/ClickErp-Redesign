<?php

return [
    'general_operations' => [
        'name' => 'الموديل التشغيلي العام',
        'description' => 'أساس العمليات العامة والميزات التي ستضاف لقائمة النظام العامة.',
        'profiles' => ['general'],
        'default_enabled' => true,
        'route_patterns' => [],
        'path_patterns' => [],
    ],

    'operations' => [
        'name' => 'إدارة التشغيل',
        'description' => 'العقود، الطلبات، السير الذاتية، الشكاوى، النقل، السفر والتشغيل اليومي.',
        'profiles' => ['recruitment'],
        'route_patterns' => [
            'dashboard.index',
            'dashboard.professional',
            'dashboard.old',
            'dashboard.employees.show',
            'maids.*',
            'noneAvailableMaids',
            'orders.*',
            'return_requests.*',
            'professional_application.*',
            'recruitment_requests.*',
            'renew-contracts.*',
            'known-service-requests.*',
            'contract-extension-requests.*',
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
            'recruitment-offices.*',
            'job-titles.*',
            'nationalities.*',
            'religions.*',
            'languages.*',
            'education-qualifications.*',
            'social-statuses.*',
            'skills.*',
            'country-polices.*',
            'police-settings.*',
            'settings.cv-template.*',
            'settings.cv-options.*',
            'form-settings.*',
            'recruitment-contract-stages.*',
            'ingaz-delegation-stages.*',
        ],
        'path_patterns' => [
            'dashboard/reports*',
        ],
    ],

    'branches' => [
        'name' => 'إدارة الفروع',
        'description' => 'تفعيل إدارة فروع الشركة وربط الموظفين والعمليات والتقارير بالفروع.',
        'default_enabled' => false,
        'route_patterns' => [
            'branches.*',
        ],
        'path_patterns' => [
            'admin/branches*',
            'admin/ingaz/branches*',
        ],
    ],

    'website' => [
        'name' => 'الموقع الخارجي',
        'description' => 'إعدادات وبيانات واجهة العميل والمحتوى العام.',
        'route_patterns' => [
            'settings.*',
            'offers.*',
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
            'whatsapp.inbox.*',
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

    'commissions' => [
        'name' => 'نظام العمولات',
        'description' => 'احتساب وإدارة عمولات المسوّقين وسداد المستحقات عبر سندات الصرف.',
        'default_enabled' => false,
        'route_patterns' => [
            'commissions.*',
        ],
        'path_patterns' => [
            'commissions*',
            'admin/commissions*',
        ],
    ],
];
