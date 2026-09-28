<?php

namespace App\Support;

/**
 * المصدر المركزي لترتيب شاشة الصلاحيات ومسمياتها.
 *
 * الوحدات أدناه هي نفس الوحدات المرئية في القائمة الجانبية. أي شاشة أو
 * مجموعة جديدة يجب أن تُسجل هنا، وبذلك لا يعود الكنترولر يملك ترتيباً وترجمات
 * مستقلة تتباعد عن الواجهة الفعلية.
 */
final class AdminPermissionNavigation
{
    private const UNITS = [
        'crm' => [
            'title' => 'إدارة العملاء (CRM)',
            'title_en' => 'Client Management (CRM)',
            'description' => 'العملاء ومتابعاتهم وأدوات نظام إدارة علاقات العملاء.',
            'tone' => 'core',
            'sections' => ['clients'],
        ],
        'operations' => [
            'title' => 'إدارة التشغيل',
            'title_en' => 'Operations Management',
            'description' => 'الشاشات التشغيلية بالترتيب نفسه الذي يعمل به الموظف يومياً.',
            'tone' => 'core',
            'sections' => [
                'dashboard', 'cv_management', 'orders', 'recruitment_contracts',
                'rent_contracts', 'shelter_management', 'additional_services',
                'travel_management', 'complaints', 'operations', 'commissions', 'reports',
            ],
        ],
        'hourly_rental' => [
            'title' => 'نظام التأجير بالساعة',
            'title_en' => 'Hourly Rental System',
            'description' => 'صلاحيات خدمات وباقات وطلبات وعملاء وفرق التأجير بالساعة والتوجيه والتتبع.',
            'tone' => 'core',
            'sections' => ['hourly_rental'],
        ],
        'hr' => [
            'title' => 'إدارة الموارد البشرية',
            'title_en' => 'Human Resources',
            'description' => 'صلاحيات نظام الموارد البشرية وبوابة الموظفين.',
            'tone' => 'packages',
            'sections' => ['hr'],
        ],
        'finance' => [
            'title' => 'إدارة الماليات',
            'title_en' => 'Finance Management',
            'description' => 'الحسابات والمخزون والمشتريات وهيئة الزكاة والضريبة.',
            'tone' => 'packages',
            'sections' => ['finance'],
        ],
        'messages' => [
            'title' => 'إدارة الرسائل',
            'title_en' => 'Messages Management',
            'description' => 'إرسال الإشعارات وقنوات الرسائل والجمهور المستهدف.',
            'tone' => 'management',
            'sections' => ['messages'],
        ],
        'settings' => [
            'title' => 'إعدادات النظام',
            'title_en' => 'System Settings',
            'description' => 'إعدادات الموقع والبيانات المساعدة والمستخدمين والتحكم بالوصول.',
            'tone' => 'management',
            'sections' => ['content_management', 'maids_settings', 'settings', 'users_management'],
        ],
    ];

    private const SECTION_TITLES = [
        'hourly_rental' => 'نظام التأجير بالساعة',
        'dashboard' => 'الرئيسية ولوحة المؤشرات',
        'clients' => 'العملاء وإدارة العلاقات',
        'cv_management' => 'إدارة السير الذاتية',
        'orders' => 'إدارة الطلبات والحجوزات',
        'recruitment_contracts' => 'عقود الاستقدام وتفاويض الإنجاز',
        'rent_contracts' => 'عقود وطلبات التأجير',
        'shelter_management' => 'إدارة الإيواء',
        'additional_services' => 'نقل الخدمات',
        'travel_management' => 'الخدمات اللوجستية',
        'complaints' => 'إدارة الشكاوى',
        'operations' => 'الطلبات المالية',
        'commissions' => 'إدارة العمولات',
        'reports' => 'التقارير والإحصائيات',
        'hr' => 'نظام الموارد البشرية وبوابة الموظف',
        'finance' => 'النظام المالي والمخزون',
        'messages' => 'الإشعارات والرسائل',
        'content_management' => 'إعدادات موقع العميل',
        'maids_settings' => 'إعدادات الاستقدام والسير الذاتية',
        'settings' => 'الإعدادات العامة والداخلية',
        'users_management' => 'المستخدمون والأدوار والصلاحيات والفروع',
    ];

    private const GROUPS = [
        'hourly_rental' => ['hourly_rental', 'صلاحيات التأجير بالساعة'],
        'clients' => ['clients', 'العملاء'],
        'crm' => ['clients', 'نظام إدارة العملاء'],
        'dashboard' => ['dashboard', 'لوحة التحكم'],
        'maids' => ['cv_management', 'السير الذاتية'],
        'cv_legacy' => ['cv_management', 'صلاحيات السير الذاتية القديمة'],
        'maids_rental' => ['cv_management', 'سير عاملات الإيجار'],
        'maids_backout' => ['cv_management', 'عاملات باك أوت'],
        'maids_deleted' => ['cv_management', 'السير الذاتية المحذوفة'],
        'maid_languages' => ['maids_settings', 'لغات السير الذاتية'],
        'orders' => ['orders', 'الطلبات'],
        'return_request' => ['orders', 'طلبات الاسترجاع'],
        'professional_application' => ['orders', 'طلبات العمالة المهنية'],
        'recruitment_requests' => ['orders', 'الطلبات الخاصة'],
        'renew_contracts' => ['orders', 'طلبات تجديد العقود'],
        'contact' => ['orders', 'طلبات التواصل'],
        'rec_contracts' => ['recruitment_contracts', 'عقود الاستقدام'],
        'ingaz_delegations' => ['recruitment_contracts', 'تفاويض الإنجاز'],
        'invoices' => ['recruitment_contracts', 'الفواتير'],
        'invoice_credit_notes' => ['recruitment_contracts', 'الإشعارات الدائنة للفواتير'],
        'musaned_transfers' => ['recruitment_contracts', 'تحويلات مساند'],
        'cancel_contract' => ['recruitment_contracts', 'طلبات إلغاء العقود'],
        'contract_settings' => ['recruitment_contracts', 'إعدادات العقود'],
        'post_arrival_refund' => ['recruitment_contracts', 'الاسترداد بعد الوصول'],
        'rent_contract' => ['rent_contracts', 'عقود التأجير'],
        'rental_orders' => ['rent_contracts', 'طلبات التأجير'],
        'shelter_contract' => ['shelter_management', 'عقود الإيواء'],
        'shelter_places' => ['shelter_management', 'مقرات الإيواء'],
        'shelter_workers' => ['shelter_management', 'عمال الإيواء'],
        'sponsor_transfer' => ['additional_services', 'نقل الخدمات'],
        'sponsor_transfer_web_requests' => ['additional_services', 'طلبات الموقع الخارجي'],
        'travel' => ['travel_management', 'رحلات الاستقدام والترحيل'],
        'complaint' => ['complaints', 'الشكاوى'],
        'complaint_type' => ['complaints', 'أنواع الشكاوى'],
        'financial_requests' => ['operations', 'الطلبات المالية'],
        'office' => ['operations', 'الوكلاء الخارجيون'],
        'commissions' => ['commissions', 'العمولات'],
        'statistics' => ['reports', 'التقارير والإحصائيات'],
        'reports' => ['reports', 'التقارير'],
        'hr' => ['hr', 'نظام الموارد البشرية'],
        'employee' => ['hr', 'بوابة الموظفين'],
        'finance' => ['finance', 'النظام المالي'],
        'finance_exports' => ['finance', 'تصدير التقارير المالية'],
        'finance_procurement' => ['finance', 'المشتريات'],
        'zatca' => ['finance', 'هيئة الزكاة والضريبة والجمارك'],
        'admin_notifications' => ['messages', 'إشعارات الموظفين'],
        'notifications' => ['messages', 'إشعارات الموقع'],
        'sliders' => ['content_management', 'سلايدر الموقع'],
        'faq' => ['content_management', 'الأسئلة الشائعة'],
        'about_us' => ['content_management', 'صفحة من نحن'],
        'blogs' => ['content_management', 'المدونة'],
        'guidelines' => ['content_management', 'الإرشادات'],
        'home_hero' => ['content_management', 'واجهة الصفحة الرئيسية'],
        'home_sections' => ['content_management', 'أقسام الصفحة الرئيسية'],
        'musaned_section' => ['content_management', 'قسم مساند'],
        'steps' => ['content_management', 'خطوات الموقع'],
        'services' => ['content_management', 'خدمات الموقع'],
        'content' => ['content_management', 'محتوى الموقع'],
        'partners' => ['content_management', 'الشركاء'],
        'packages' => ['maids_settings', 'باقات التأجير'],
        'nationalities' => ['maids_settings', 'الجنسيات'],
        'religions' => ['maids_settings', 'الأديان'],
        'skills' => ['maids_settings', 'المهارات'],
        'countries' => ['maids_settings', 'الدول'],
        'social_status' => ['maids_settings', 'الحالة الاجتماعية'],
        'job_titles' => ['maids_settings', 'المسميات الوظيفية'],
        'rec_offices' => ['maids_settings', 'مكاتب الاستقدام'],
        'external_agents' => ['maids_settings', 'الوكلاء الخارجيون'],
        'settings' => ['settings', 'إعدادات لوحة التحكم'],
        'moyasar_settings' => ['settings', 'إعدادات بوابة ميسر'],
        'developers' => ['settings', 'صلاحيات المطور'],
        'admins' => ['users_management', 'المشرفون'],
        'users' => ['users_management', 'المستخدمون'],
        'roles' => ['users_management', 'الأدوار'],
        'permissions' => ['users_management', 'الصلاحيات'],
        'role_permissions' => ['users_management', 'صلاحيات الأدوار'],
        'branches' => ['users_management', 'الفروع'],
        'activity_log' => ['users_management', 'سجل النشاط'],
    ];

    private const GROUP_ALIASES = [
        'cv' => 'cv_legacy',
        'return_requests' => 'return_request',
        'recruitment_contracts' => 'rec_contracts',
        'cancel_contracts' => 'cancel_contract',
        'contracts' => 'rec_contracts',
        'contract' => 'contract_settings',
        'ingaz' => 'ingaz_delegations',
        'musaned' => 'musaned_transfers',
        'offices' => 'external_agents',
        'partenr' => 'partners',
        'relegions' => 'religions',
    ];

    public static function units(): array
    {
        return self::UNITS;
    }

    /**
     * عنوان الوحدة بلغة الواجهة الحالية. القائمة الجانبية وشاشة الصلاحيات
     * تقرآن من هنا حتى لا يتباعد الاسمان، ومع ذلك تبقى الواجهة الإنجليزية تعمل.
     */
    public static function unitTitle(string $unit, ?string $locale = null): string
    {
        $definition = self::UNITS[$unit] ?? null;

        if ($definition === null) {
            return $unit;
        }

        $locale = $locale ?: (function_exists('app') ? app()->getLocale() : 'ar');

        return $locale === 'en'
            ? ($definition['title_en'] ?? $definition['title'])
            : $definition['title'];
    }

    public static function sectionOrder(): array
    {
        return array_values(array_merge(...array_column(self::UNITS, 'sections')));
    }

    public static function groupOrder(): array
    {
        return array_merge(array_keys(HourlyPermissionCatalog::PAGES), array_keys(self::GROUPS));
    }

    public static function sectionTitle(string $key): string
    {
        return self::SECTION_TITLES[$key] ?? 'قسم غير مصنف — ' . $key;
    }

    public static function hasSection(string $key): bool
    {
        return isset(self::SECTION_TITLES[$key]);
    }

    public static function groupTitle(string $key): string
    {
        $key = self::canonicalGroup($key);

        if (isset(HourlyPermissionCatalog::PAGES[$key])) return HourlyPermissionCatalog::PAGES[$key];

        return self::GROUPS[$key][1] ?? 'مجموعة غير مصنفة — ' . $key;
    }

    public static function hasGroup(string $key): bool
    {
        return isset(HourlyPermissionCatalog::PAGES[$key]) || isset(self::GROUPS[self::canonicalGroup($key)]);
    }

    public static function canonicalGroup(?string $group): string
    {
        $group = trim(str_replace('-', '_', (string) $group));

        return self::GROUP_ALIASES[$group] ?? ($group !== '' ? $group : 'other');
    }

    /** @return array{section: string, group: string} */
    public static function canonicalPlacement(string $section, string $group): array
    {
        $group = self::canonicalGroup($group);
        $canonicalSection = self::GROUPS[$group][0] ?? null;

        return [
            'section' => $canonicalSection ?? (isset(self::SECTION_TITLES[$section]) ? $section : 'settings'),
            'group' => $group,
        ];
    }

    /** @return array{section: string, group: string} */
    public static function placementForPermission(string $name, ?string $section, ?string $group): array
    {
        $normalized = str_replace(['.', '-'], '_', $name);
        // Keep imported and newly registered hourly permissions together,
        // regardless of legacy placement metadata stored in the database.
        if (str_starts_with($normalized, 'hourly_')) {
            return ['section' => 'hourly_rental', 'group' => HourlyPermissionCatalog::page($normalized) ?? 'hourly_rental'];
        }

        $group = self::canonicalGroup($group);

        if ($group === 'other') {
            foreach (self::permissionPrefixes() as $prefix => $inferredGroup) {
                if (str_starts_with($normalized, $prefix)) {
                    $group = $inferredGroup;
                    break;
                }
            }
        }

        if ($normalized === 'complaints_read') {
            $group = 'complaint';
        }

        return self::canonicalPlacement((string) $section, $group);
    }

    /** @return array<string, string> */
    private static function permissionPrefixes(): array
    {
        return [
            'admin_notifications_' => 'admin_notifications',
            'dashboard_' => 'dashboard', 'clients_' => 'clients',
            'maids_rental_' => 'maids_rental', 'maids_backout_' => 'maids_backout',
            'maids_deleted_' => 'maids_deleted', 'maids_' => 'maids', 'cv_' => 'cv_legacy',
            'maid_languages_' => 'maid_languages',
            'orders_' => 'orders', 'return_request_' => 'return_request',
            'professional_application_' => 'professional_application',
            'recruitment_requests_' => 'recruitment_requests', 'renew_contracts_' => 'renew_contracts',
            'financial_requests_' => 'financial_requests', 'rec_contracts_' => 'rec_contracts',
            'rec_contract_' => 'rec_contracts', 'ingaz_delegations_' => 'ingaz_delegations',
            'post_arrival_refund_' => 'post_arrival_refund',
            'invoices_' => 'invoices', 'invoice_credit_notes_' => 'invoice_credit_notes',
            'musaned_transfers_' => 'musaned_transfers', 'cancel_contract_' => 'cancel_contract',
            'rent_contract_' => 'rent_contract', 'rental_orders_' => 'rental_orders',
            'shelter_contract_' => 'shelter_contract', 'shelter_workers_' => 'shelter_workers',
            'shelter_places_' => 'shelter_places', 'travel_' => 'travel',
            'complaint_type_' => 'complaint_type', 'complaint_' => 'complaint', 'complaints_' => 'complaint',
            'sponsor_transfer_web_requests_' => 'sponsor_transfer_web_requests',
            'sponsor_transfer_' => 'sponsor_transfer', 'commissions_' => 'commissions',
            'recruitment_offices_' => 'rec_offices', 'rec_offices_' => 'rec_offices',
            'office_' => 'external_agents', 'users_' => 'users', 'roles_' => 'roles',
            'permissions_' => 'permissions', 'branches_' => 'branches',
            'activity_' => 'activity_log', 'hr_' => 'hr', 'employee_' => 'employee',
            'finance_' => 'finance', 'zatca_' => 'zatca', 'crm_' => 'crm',
        ];
    }

    public static function unitForSection(string $section): string
    {
        foreach (self::UNITS as $unit => $definition) {
            if (in_array($section, $definition['sections'], true)) {
                return $unit;
            }
        }

        return 'settings';
    }
}
