<?php

return array (
  'defaults' => 
  array (
    'guard_name' => 'admin',
    'assign_roles' => 
    array (
      0 => 'Administrator',
    ),
    'include_legacy' => true,
    'include_legacy_main' => false,
  ),
  'group_sections' => 
  array (
    'main' => 'users_management',
    'finance' => 'modules',
    'hr' => 'modules',
    'crm' => 'clients',
    'employee' => 'modules',
    'zatca' => 'modules',
    'access_control' => 'users_management',
  ),
  'resources' => 
  array (
    'main' => 
    array (
    ),
    'finance' => 
    array (
      'finance.reports' => 
      array (
        'actions' => 
        array (
          0 => 'view',
          1 => 'create',
          2 => 'edit',
          3 => 'delete',
        ),
      ),
    ),
    'hr' => 
    array (
    ),
    'crm' => 
    array (
    ),
    'employee' => 
    array (
    ),
    'zatca' => 
    array (
    ),
  ),
  'permissions' => 
  array (
    0 =>
    array (
      'name' => 'dashboard_view',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض لوحة التحكم',
      'description' => 'إمكانية عرض لوحة التحكم الرئيسية.',
    ),
    1 =>
    array (
      'name' => 'dashboard_view_statistics',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض إحصائيات لوحة التحكم',
      'description' => 'إمكانية عرض بطاقات وإحصائيات لوحة التحكم.',
    ),
    2 =>
    array (
      'name' => 'dashboard_analytics',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'إحصائيات لوحة التحكم',
      'description' => 'إمكانية عرض الأقسام التحليلية داخل لوحة التحكم.',
    ),
    3 =>
    array (
      'name' => 'dashboard_export',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'تصدير بيانات لوحة التحكم',
      'description' => 'إمكانية تصدير بيانات لوحة التحكم.',
    ),
    4 =>
    array (
      'name' => 'dashboard_manage',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'إدارة لوحة التحكم',
      'description' => 'إمكانية إدارة جميع أقسام وإحصائيات لوحة التحكم.',
    ),
    5 =>
    array (
      'name' => 'dashboard_recruitment_orders_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض طلبات الاستقدام',
      'description' => 'إمكانية عرض بطاقة طلبات الاستقدام في لوحة التحكم.',
    ),
    6 =>
    array (
      'name' => 'dashboard_recruitment_contracts_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض عقود الاستقدام',
      'description' => 'إمكانية عرض بطاقة عقود الاستقدام في لوحة التحكم.',
    ),
    7 =>
    array (
      'name' => 'dashboard_clients_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض عدد العملاء',
      'description' => 'إمكانية عرض بطاقة العملاء في لوحة التحكم.',
    ),
    8 =>
    array (
      'name' => 'dashboard_contacts_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض عدد رسائل التواصل',
      'description' => 'إمكانية عرض إحصائية رسائل التواصل في لوحة التحكم.',
    ),
    9 =>
    array (
      'name' => 'dashboard_current_orders_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض عدد الطلبات الحالية',
      'description' => 'إمكانية عرض إحصائية الطلبات الحالية في لوحة التحكم.',
    ),
    10 =>
    array (
      'name' => 'dashboard_cv_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض عدد السير الذاتية',
      'description' => 'إمكانية عرض بطاقة السير الذاتية في لوحة التحكم.',
    ),
    11 =>
    array (
      'name' => 'dashboard_employees_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض عدد الموظفين',
      'description' => 'إمكانية عرض إحصائية الموظفين في لوحة التحكم.',
    ),
    12 =>
    array (
      'name' => 'dashboard_completed_cv_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض عدد السير الذاتية المكتملة',
      'description' => 'إمكانية عرض إحصائية السير الذاتية المكتملة في لوحة التحكم.',
    ),
    13 =>
    array (
      'name' => 'dashboard_rental_contracts_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض عقود الإيجار',
      'description' => 'إمكانية عرض بطاقة عقود الإيجار في لوحة التحكم.',
    ),
    14 =>
    array (
      'name' => 'dashboard_rental_orders_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض طلبات الإيجار من الموقع',
      'description' => 'إمكانية عرض بطاقة طلبات الإيجار من الموقع في لوحة التحكم.',
    ),
    15 =>
    array (
      'name' => 'dashboard_sponsorship_transfers_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض معاملات نقل الكفالة',
      'description' => 'إمكانية عرض بطاقة معاملات نقل الكفالة في لوحة التحكم.',
    ),
    16 =>
    array (
      'name' => 'dashboard_sponsorship_transfer_requests_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض طلبات نقل الكفالة من الموقع',
      'description' => 'إمكانية عرض بطاقة طلبات نقل الكفالة من الموقع في لوحة التحكم.',
    ),
    17 =>
    array (
      'name' => 'dashboard_shelter_count',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض إحصائيات الإيواء',
      'description' => 'إمكانية عرض بطاقة الإيواء في لوحة التحكم.',
    ),
    18 =>
    array (
      'name' => 'dashboard_orders_statistics',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض إحصائيات الطلبات',
      'description' => 'إمكانية عرض إحصائيات الطلبات في لوحة التحكم.',
    ),
    19 =>
    array (
      'name' => 'dashboard_operations_overview',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض مؤشرات التشغيل السريعة',
      'description' => 'إمكانية عرض قسم مؤشرات التشغيل السريعة في لوحة التحكم.',
    ),
    20 =>
    array (
      'name' => 'dashboard_contracts_overview',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض نظرة العقود التحليلية',
      'description' => 'إمكانية عرض قسم نظرة العقود التحليلية في لوحة التحكم.',
    ),
    21 =>
    array (
      'name' => 'dashboard_nationalities_statistics',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض إحصائيات الجنسيات',
      'description' => 'إمكانية عرض قسم أبرز الجنسيات المتاحة في لوحة التحكم.',
    ),
    22 =>
    array (
      'name' => 'dashboard_offices_statistics',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض إحصائيات المكاتب الخارجية',
      'description' => 'إمكانية عرض قسم المخزون لكل مكتب في لوحة التحكم.',
    ),
    23 =>
    array (
      'name' => 'dashboard_recent_activities',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض آخر الأنشطة',
      'description' => 'إمكانية عرض قسم آخر الأنشطة في لوحة التحكم.',
    ),
    24 =>
    array (
      'name' => 'dashboard_calendar_events',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض الجدول الزمني والتذكيرات',
      'description' => 'إمكانية عرض قسم الجدول الزمني والتذكيرات في لوحة التحكم.',
    ),
    25 =>
    array (
      'name' => 'dashboard_employee_performance',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض أداء طاقم العمل',
      'description' => 'إمكانية عرض قسم حجوزات وعقود الموظفين في لوحة التحكم.',
    ),
    26 =>
    array (
      'name' => 'dashboard_top_customer_services',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض أفضل خدمة عملاء',
      'description' => 'إمكانية عرض أفضل خدمة عملاء في لوحة التحكم.',
    ),
    27 =>
    array (
      'name' => 'dashboard_orders_types',
      'group' => 'dashboard',
      'section' => 'dashboard',
      'display_name' => 'عرض أنواع الطلبات',
      'description' => 'إمكانية عرض أنواع الطلبات في لوحة التحكم.',
    ),
    28 => 
    array (
      'name' => 'crm_module',
      'group' => 'crm',
      'section' => 'clients',
    ),
    29 => 
    array (
      'name' => 'hr_module',
      'group' => 'hr',
      'section' => 'modules',
    ),
    30 => 
    array (
      'name' => 'finance_module',
      'group' => 'finance',
      'section' => 'modules',
      'is_active' => true,
    ),
    31 =>
    array (
      'name' => 'professional_application_view',
      'group' => 'professional_application',
      'section' => 'orders',
      'display_name' => 'عرض طلبات العمالة المهنية',
      'description' => 'إمكانية عرض طلبات العمالة المهنية في قائمة الطلبات.',
    ),
    32 =>
    array (
      'name' => 'recruitment_requests_view',
      'group' => 'recruitment_requests',
      'section' => 'orders',
      'display_name' => 'عرض الطلبات الخاصة',
      'description' => 'إمكانية عرض الطلبات الخاصة ضمن قائمة الطلبات.',
    ),
    33 =>
    array (
      'name' => 'renew_contracts_view',
      'group' => 'renew_contracts',
      'section' => 'orders',
      'display_name' => 'عرض طلبات تجديد العقود',
      'description' => 'إمكانية عرض طلبات تجديد العقود ضمن قائمة الطلبات.',
    ),
    34 =>
    array (
      'name' => 'rental_orders_view',
      'group' => 'rental_orders',
      'section' => 'rent_contracts',
      'display_name' => 'عرض طلبات الإيجار',
      'description' => 'إمكانية عرض طلبات الإيجار من قائمة عقود الإيجار.',
    ),
    35 =>
    array (
      'name' => 'sponsor_transfer_web_requests_view',
      'group' => 'sponsor_transfer_web_requests',
      'section' => 'additional_services',
      'display_name' => 'عرض طلبات الموقع الخارجي لنقل الكفالة',
      'description' => 'إمكانية عرض طلبات نقل الكفالة الواردة من الموقع الخارجي.',
    ),
    36 =>
    array (
      'name' => 'sponsor_transfer_view',
      'group' => 'sponsor_transfer',
      'section' => 'additional_services',
      'display_name' => 'عرض طلبات نقل الكفالة',
      'description' => 'إمكانية عرض طلبات نقل الكفالة من لوحة التحكم.',
    ),
    37 =>
    array (
      'name' => 'sponsor_transfer_create',
      'group' => 'sponsor_transfer',
      'section' => 'additional_services',
      'display_name' => 'إضافة طلب نقل خدمات',
      'description' => 'إمكانية إنشاء طلب نقل خدمات جديد.',
    ),
    38 =>
    array (
      'name' => 'sponsor_transfer_edit',
      'group' => 'sponsor_transfer',
      'section' => 'additional_services',
      'display_name' => 'تعديل طلبات نقل الكفالة',
      'description' => 'إمكانية تعديل بيانات طلبات نقل الكفالة.',
    ),
    39 =>
    array (
      'name' => 'sponsor_transfer_delete',
      'group' => 'sponsor_transfer',
      'section' => 'additional_services',
      'display_name' => 'حذف طلبات نقل الكفالة',
      'description' => 'إمكانية حذف طلبات نقل الكفالة.',
    ),
    40 =>
    array (
      'name' => 'sponsor_transfer_control',
      'group' => 'sponsor_transfer',
      'section' => 'additional_services',
      'display_name' => 'التحكم في طلبات نقل الكفالة',
      'description' => 'إمكانية إدارة خطوات طلبات نقل الكفالة والتحكم بها.',
    ),
    41 =>
    array (
      'name' => 'contact_view',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'عرض طلبات التواصل',
      'description' => 'إمكانية عرض طلبات التواصل ضمن قائمة الطلبات.',
    ),
    42 =>
    array (
      'name' => 'contact_create',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'إضافة طلب تواصل',
      'description' => 'إمكانية إضافة طلب تواصل.',
    ),
    43 =>
    array (
      'name' => 'contact_edit',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'تعديل طلبات التواصل',
      'description' => 'إمكانية تعديل بيانات طلبات التواصل.',
    ),
    44 =>
    array (
      'name' => 'contact_delete',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'حذف طلبات التواصل',
      'description' => 'إمكانية حذف طلبات التواصل.',
    ),
    45 =>
    array (
      'name' => 'contact_control',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'التحكم في طلبات التواصل',
      'description' => 'إمكانية إدارة طلبات التواصل والتحكم بها.',
    ),
    46 =>
    array (
      'name' => 'travel_view',
      'group' => 'travel',
      'section' => 'travel_management',
      'display_name' => 'عرض رحلات الاستقدام والترحيل',
      'description' => 'إمكانية عرض صفحات وقوائم رحلات الاستقدام والترحيل ضمن الخدمات اللوجستية.',
    ),
    47 =>
    array (
      'name' => 'travel_create',
      'group' => 'travel',
      'section' => 'travel_management',
      'display_name' => 'جدولة رحلة استقدام أو ترحيل',
      'description' => 'إمكانية إنشاء وجدولة رحلات الاستقدام والترحيل ضمن الخدمات اللوجستية.',
    ),
    48 =>
    array (
      'name' => 'travel_edit',
      'group' => 'travel',
      'section' => 'travel_management',
      'display_name' => 'تعديل رحلات الاستقدام والترحيل',
      'description' => 'إمكانية تعديل بيانات الرحلات اللوجستية قبل اكتمالها.',
    ),
    49 =>
    array (
      'name' => 'travel_delete',
      'group' => 'travel',
      'section' => 'travel_management',
      'display_name' => 'حذف رحلات الاستقدام والترحيل',
      'description' => 'إمكانية حذف الرحلات اللوجستية غير المكتملة ومرفقاتها.',
    ),
    50 =>
    array (
      'name' => 'travel_control',
      'group' => 'travel',
      'section' => 'travel_management',
      'display_name' => 'إدارة رحلات الاستقدام والترحيل',
      'description' => 'إمكانية إسناد الرحلات، تأكيد اكتمالها، وإدارة الإجراءات التشغيلية لرحلات الخدمات اللوجستية.',
    ),
    51 =>
    array (
      'name' => 'rec_contracts_add_travel',
      'group' => 'rec_contracts',
      'section' => 'recruitment_contracts',
      'display_name' => 'إضافة رحلة للعقد',
      'description' => 'إمكانية إضافة رحلة جديدة لعقد الاستقدام من شاشة العقود.',
    ),
    52 =>
    array (
      'name' => 'rec_contracts_edit_travel',
      'group' => 'rec_contracts',
      'section' => 'recruitment_contracts',
      'display_name' => 'تعديل رحلة العقد',
      'description' => 'إمكانية تعديل بيانات رحلة عقد الاستقدام من شاشة العقود.',
    ),
    53 =>
    array (
      'name' => 'rec_contracts_delete_travel',
      'group' => 'rec_contracts',
      'section' => 'recruitment_contracts',
      'display_name' => 'حذف رحلة العقد',
      'description' => 'إمكانية حذف رحلة مرتبطة بعقد الاستقدام من شاشة العقود.',
    ),
    54 =>
    array (
      'name' => 'rec_contracts_complete_travel',
      'group' => 'rec_contracts',
      'section' => 'recruitment_contracts',
      'display_name' => 'تحديد اكتمال الرحلة',
      'description' => 'إمكانية تأكيد اكتمال رحلة عقد الاستقدام من شاشة العقود.',
    ),
    55 =>
    array (
      'name' => 'rec_contracts_view_travel',
      'group' => 'rec_contracts',
      'section' => 'recruitment_contracts',
      'display_name' => 'عرض تفاصيل الرحلة',
      'description' => 'إمكانية عرض تفاصيل الرحلات المرتبطة بعقود الاستقدام.',
    ),
    56 =>
    array (
      'name' => 'shelter_contract_view',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'عرض عقود الإيواء',
      'description' => 'إمكانية عرض شاشات وبيانات عقود الإيواء.',
    ),
    57 =>
    array (
      'name' => 'shelter_contract_create',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'إضافة عقد إيواء',
      'description' => 'إمكانية إنشاء عقد جديد داخل الإيواء.',
    ),
    58 =>
    array (
      'name' => 'shelter_contract_edit',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'تعديل عقود الإيواء',
      'description' => 'إمكانية تعديل بيانات عقود الإيواء الحالية.',
    ),
    59 =>
    array (
      'name' => 'shelter_contract_delete',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'حذف عقود الإيواء',
      'description' => 'إمكانية حذف سجلات عقود الإيواء.',
    ),
    60 =>
    array (
      'name' => 'shelter_contract_control',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'إدارة عقود الإيواء',
      'description' => 'إمكانية إدارة جميع عمليات الإيواء والتحكم بها.',
    ),
    61 =>
    array (
      'name' => 'shelter_workers_view',
      'group' => 'shelter_workers',
      'section' => 'shelter_management',
      'display_name' => 'عرض عمال الإيواء',
      'description' => 'إمكانية عرض عمال الإيواء وبياناتهم.',
    ),
    62 =>
    array (
      'name' => 'shelter_workers_create',
      'group' => 'shelter_workers',
      'section' => 'shelter_management',
      'display_name' => 'إضافة عامل إيواء',
      'description' => 'إمكانية إضافة عامل جديد إلى الإيواء.',
    ),
    63 =>
    array (
      'name' => 'shelter_workers_edit',
      'group' => 'shelter_workers',
      'section' => 'shelter_management',
      'display_name' => 'تعديل عمال الإيواء',
      'description' => 'إمكانية تعديل بيانات عمال الإيواء.',
    ),
    64 =>
    array (
      'name' => 'shelter_workers_delete',
      'group' => 'shelter_workers',
      'section' => 'shelter_management',
      'display_name' => 'حذف عمال الإيواء',
      'description' => 'إمكانية حذف سجلات عمال الإيواء.',
    ),
    65 =>
    array (
      'name' => 'shelter_workers_control',
      'group' => 'shelter_workers',
      'section' => 'shelter_management',
      'display_name' => 'إدارة عمال الإيواء',
      'description' => 'إمكانية إدارة جميع عمليات عمال الإيواء.',
    ),
    66 =>
    array (
      'name' => 'shelter_places_view',
      'group' => 'shelter_places',
      'section' => 'shelter_management',
      'display_name' => 'عرض مقرات الإيواء',
      'description' => 'إمكانية عرض مقرات الإيواء المسجلة.',
    ),
    67 =>
    array (
      'name' => 'shelter_places_create',
      'group' => 'shelter_places',
      'section' => 'shelter_management',
      'display_name' => 'إضافة مقر إيواء',
      'description' => 'إمكانية إضافة مقر إيواء جديد.',
    ),
    68 =>
    array (
      'name' => 'shelter_places_edit',
      'group' => 'shelter_places',
      'section' => 'shelter_management',
      'display_name' => 'تعديل مقرات الإيواء',
      'description' => 'إمكانية تعديل بيانات مقرات الإيواء.',
    ),
    69 =>
    array (
      'name' => 'shelter_places_delete',
      'group' => 'shelter_places',
      'section' => 'shelter_management',
      'display_name' => 'حذف مقرات الإيواء',
      'description' => 'إمكانية حذف سجلات مقرات الإيواء.',
    ),
    70 =>
    array (
      'name' => 'shelter_places_control',
      'group' => 'shelter_places',
      'section' => 'shelter_management',
      'display_name' => 'إدارة مقرات الإيواء',
      'description' => 'إمكانية إدارة مقرات الإيواء والتحكم بها.',
    ),
    71 =>
    array (
      'name' => 'shelter_contract_addition_create',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'الوصول إلى شاشة إضافة الإيواء',
      'description' => 'إمكانية الدخول إلى شاشة إضافة عقد جديد داخل الإيواء.',
    ),
    72 =>
    array (
      'name' => 'shelter_contract_inside_view',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'عرض داخل الإيواء',
      'description' => 'إمكانية عرض قائمة العقود الموجودة حالياً داخل الإيواء.',
    ),
    73 =>
    array (
      'name' => 'shelter_contract_available_for_transfer_view',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'عرض المتاح للنقل',
      'description' => 'إمكانية عرض قائمة العقود المتاحة للنقل من داخل الإيواء.',
    ),
    74 =>
    array (
      'name' => 'shelter_contract_deportation_stage_view',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'عرض مرحلة الترحيل',
      'description' => 'إمكانية عرض قائمة عقود مرحلة الترحيل.',
    ),
    75 =>
    array (
      'name' => 'shelter_contract_deported_view',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'عرض تم الترحيل',
      'description' => 'إمكانية عرض قائمة العقود التي اكتمل ترحيلها.',
    ),
    76 =>
    array (
      'name' => 'shelter_contract_deportation_procedures_view',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'عرض إجراءات الترحيل',
      'description' => 'إمكانية عرض تفاصيل وخطوات إجراءات الترحيل لكل عقد.',
    ),
    77 =>
    array (
      'name' => 'shelter_contract_deportation_procedures_edit',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'إدارة إجراءات الترحيل',
      'description' => 'إمكانية بدء وتحديث واستكمال إجراءات الترحيل.',
    ),
    78 =>
    array (
      'name' => 'shelter_contract_work_desire_edit',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'تغيير رغبة العمل',
      'description' => 'إمكانية تحديث رغبة العاملة في العمل من شاشات الإيواء.',
    ),
    79 =>
    array (
      'name' => 'shelter_contract_quick_tags_edit',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'تعديل التصنيفات السريعة',
      'description' => 'إمكانية تعديل التصنيفات السريعة لعقود الإيواء.',
    ),
    80 =>
    array (
      'name' => 'shelter_contract_instant_receipt_edit',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'تعديل الاستلام الفوري',
      'description' => 'إمكانية تغيير إتاحة العقد للاستلام الفوري.',
    ),
    81 =>
    array (
      'name' => 'shelter_contract_remove_from_shelter_edit',
      'group' => 'shelter_contract',
      'section' => 'shelter_management',
      'display_name' => 'حذف من الإيواء',
      'description' => 'إمكانية إخراج العقد من الإيواء وإرجاعه إلى مرحلته السابقة.',
    ),
    82 =>
    array (
      'name' => 'maids_view',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'عرض السير الذاتية',
      'description' => 'إمكانية عرض قائمة السير الذاتية.',
    ),
    83 =>
    array (
      'name' => 'maids_create',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'إضافة سيرة ذاتية',
      'description' => 'إمكانية إضافة سيرة ذاتية جديدة.',
    ),
    84 =>
    array (
      'name' => 'maids_edit',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'تعديل السير الذاتية',
      'description' => 'إمكانية تعديل بيانات السير الذاتية.',
    ),
    85 =>
    array (
      'name' => 'maids_delete',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'حذف السير الذاتية',
      'description' => 'إمكانية حذف السير الذاتية واسترجاعها.',
    ),
    86 =>
    array (
      'name' => 'maids_export',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'تصدير السير الذاتية',
      'description' => 'إمكانية تصدير بيانات السير الذاتية.',
    ),
    87 =>
    array (
      'name' => 'maids_export_pdf',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'تصدير السير الذاتية بصيغة PDF',
      'description' => 'إمكانية تصدير السير الذاتية بصيغة PDF.',
    ),
    88 =>
    array (
      'name' => 'maids_export_excel',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'تصدير السير الذاتية بصيغة Excel',
      'description' => 'إمكانية تصدير السير الذاتية بصيغة Excel.',
    ),
    89 =>
    array (
      'name' => 'maids_show',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'عرض تفاصيل السيرة الذاتية',
      'description' => 'إمكانية عرض تفاصيل السيرة الذاتية.',
    ),
    90 =>
    array (
      'name' => 'maids_toggle_status',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'تغيير حالة الظهور',
      'description' => 'إمكانية تغيير حالة ظهور السيرة الذاتية.',
    ),
    91 =>
    array (
      'name' => 'maids_toggle_pin',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'تثبيت أو إلغاء تثبيت السيرة الذاتية',
      'description' => 'إمكانية تثبيت أو إلغاء تثبيت السيرة الذاتية.',
    ),
    92 =>
    array (
      'name' => 'maids_control',
      'group' => 'maids',
      'section' => 'cv_management',
      'display_name' => 'إدارة السير الذاتية',
      'description' => 'إمكانية التحكم في عمليات السير الذاتية.',
    ),
    93 =>
    array (
      'name' => 'maids_rental_view',
      'group' => 'maids_rental',
      'section' => 'cv_management',
      'display_name' => 'عرض سير عاملات الإيجار',
      'description' => 'إمكانية عرض قائمة سير عاملات الإيجار من القائمة الجانبية.',
    ),
    94 =>
    array (
      'name' => 'maids_backout_view',
      'group' => 'maids_backout',
      'section' => 'cv_management',
      'display_name' => 'عرض عاملات باك أوت',
      'description' => 'إمكانية عرض قائمة عاملات الباك أوت من القائمة الجانبية.',
    ),
    95 =>
    array (
      'name' => 'maids_deleted_view',
      'group' => 'maids_deleted',
      'section' => 'cv_management',
      'display_name' => 'عرض السير الذاتية المحذوفة',
      'description' => 'إمكانية عرض قائمة السير الذاتية المحذوفة من القائمة الجانبية.',
    ),
    97 =>
    array (
      'name' => 'branches_view_all',
      'group' => 'branches',
      'section' => 'users_management',
      'display_name' => 'عرض بيانات جميع الفروع',
      'description' => 'السماح للمستخدم بعرض بيانات جميع الفروع واستخدام فلتر الفروع عند تفعيل موديول الفروع.',
    ),
    96 =>
    array (
      'name' => 'finance.constraints-approve',
      'group' => 'finance',
      'section' => 'modules',
      'display_name' => 'اعتماد القيود المحاسبية',
      'description' => 'إمكانية اعتماد القيود المحاسبية المنتظرة.',
    ),
  ),
);
