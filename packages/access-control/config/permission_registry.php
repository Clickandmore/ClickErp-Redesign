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
      'name' => 'contact_view',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'عرض طلبات التواصل',
      'description' => 'إمكانية عرض طلبات التواصل ضمن قائمة الطلبات.',
    ),
    37 =>
    array (
      'name' => 'contact_create',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'إضافة طلب تواصل',
      'description' => 'إمكانية إضافة طلب تواصل.',
    ),
    38 =>
    array (
      'name' => 'contact_edit',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'تعديل طلبات التواصل',
      'description' => 'إمكانية تعديل بيانات طلبات التواصل.',
    ),
    39 =>
    array (
      'name' => 'contact_delete',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'حذف طلبات التواصل',
      'description' => 'إمكانية حذف طلبات التواصل.',
    ),
    40 =>
    array (
      'name' => 'contact_control',
      'group' => 'contact',
      'section' => 'orders',
      'display_name' => 'التحكم في طلبات التواصل',
      'description' => 'إمكانية إدارة طلبات التواصل والتحكم بها.',
    ),
  ),
);
