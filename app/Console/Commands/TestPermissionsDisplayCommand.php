<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class TestPermissionsDisplayCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:permissions-display';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test permissions display in the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== اختبار عرض الصلاحيات في النظام ===');

        // الحصول على جميع الصلاحيات
        $permissions = Permission::where('guard_name', 'admin')->get();
        $this->info("إجمالي الصلاحيات: {$permissions->count()}");

        // فحص صلاحيات عقود الاستقدام
        $recContractsPermissions = $permissions->filter(function($perm) {
            return str_starts_with($perm->name, 'rec_contracts_');
        });

        $this->info("صلاحيات عقود الاستقدام: {$recContractsPermissions->count()}");

        // محاكاة تنظيم الصلاحيات يدوياً
        $organized = [];

        foreach ($permissions as $permission) {
            $name = $permission->name;

            // تحديد القسم والمجموعة بناءً على اسم الصلاحية
            if (str_starts_with($name, 'dashboard_')) {
                $section = 'dashboard';
                $group = 'dashboard';
            } elseif (str_starts_with($name, 'clients_')) {
                $section = 'clients';
                $group = 'clients';
            } elseif (str_starts_with($name, 'maids_')) {
                $section = 'cv_management';
                $group = 'maids';
            } elseif (str_starts_with($name, 'orders_')) {
                $section = 'orders';
                $group = 'orders';
            } elseif (str_starts_with($name, 'return_request_')) {
                $section = 'orders';
                $group = 'return_request';
            } elseif (str_starts_with($name, 'rec_contract_')) {
                $section = 'contracts';
                $group = 'rec_contract';
            } elseif (str_starts_with($name, 'rec_contracts_')) {
                $section = 'contracts';
                $group = 'rec_contracts';
            } elseif (str_starts_with($name, 'cancel_contract_')) {
                $section = 'contracts';
                $group = 'cancel_contract';
            } elseif (str_starts_with($name, 'rent_contract_')) {
                $section = 'contracts';
                $group = 'rent_contract';
            } elseif (str_starts_with($name, 'shelter_contract_')) {
                $section = 'contracts';
                $group = 'shelter_contract';
            } elseif (str_starts_with($name, 'shelter_workers_')) {
                $section = 'contracts';
                $group = 'shelter_workers';
            } elseif (str_starts_with($name, 'shelter_places_')) {
                $section = 'contracts';
                $group = 'shelter_places';
            } elseif (str_starts_with($name, 'travel_')) {
                $section = 'contracts';
                $group = 'travel';
            } elseif (str_starts_with($name, 'rec_offices_')) {
                $section = 'contracts';
                $group = 'rec_offices';
            } elseif (str_starts_with($name, 'complaint_')) {
                $section = 'complaints';
                $group = 'complaint';
            } elseif (str_starts_with($name, 'complaint_type_')) {
                $section = 'complaints';
                $group = 'complaint_type';
            } elseif (str_starts_with($name, 'sponsor_transfer_')) {
                $section = 'additional_services';
                $group = 'sponsor_transfer';
            } elseif (str_starts_with($name, 'settings_')) {
                $section = 'settings';
                $group = 'settings';
            } elseif (str_starts_with($name, 'activity_')) {
                $section = 'settings';
                $group = 'activity_log';
            } elseif (str_starts_with($name, 'job_titles_')) {
                $section = 'maids_settings';
                $group = 'job_titles';
            } elseif (str_starts_with($name, 'packages_')) {
                $section = 'maids_settings';
                $group = 'packages';
            } elseif (str_starts_with($name, 'nationalities_')) {
                $section = 'maids_settings';
                $group = 'nationalities';
            } elseif (str_starts_with($name, 'religions_')) {
                $section = 'maids_settings';
                $group = 'religions';
            } elseif (str_starts_with($name, 'skills_')) {
                $section = 'maids_settings';
                $group = 'skills';
            } elseif (str_starts_with($name, 'countries_')) {
                $section = 'maids_settings';
                $group = 'countries';
            } elseif (str_starts_with($name, 'users_')) {
                $section = 'users_management';
                $group = 'users';
            } elseif (str_starts_with($name, 'roles_')) {
                $section = 'users_management';
                $group = 'roles';
            } elseif (str_starts_with($name, 'permissions_')) {
                $section = 'users_management';
                $group = 'permissions';
            } elseif (str_starts_with($name, 'statistics_')) {
                $section = 'content_management';
                $group = 'statistics';
            } elseif (str_starts_with($name, 'notifications_')) {
                $section = 'content_management';
                $group = 'notifications';
            } elseif (str_starts_with($name, 'sliders_')) {
                $section = 'content_management';
                $group = 'sliders';
            } elseif (str_starts_with($name, 'faq_')) {
                $section = 'content_management';
                $group = 'faq';
            } elseif (str_starts_with($name, 'about_us_')) {
                $section = 'content_management';
                $group = 'about_us';
            } elseif (str_starts_with($name, 'contact_')) {
                $section = 'content_management';
                $group = 'contact';
            } elseif (str_starts_with($name, 'steps_')) {
                $section = 'content_management';
                $group = 'steps';
            } elseif (str_starts_with($name, 'services_')) {
                $section = 'content_management';
                $group = 'services';
            } elseif (in_array($name, ['contract_settings', 'full_contract', 'set_perms', 'developers_perms', 'test_perm'])) {
                $section = 'settings';
                $group = 'contract_settings';
            } else {
                $section = 'other';
                $group = 'other';
            }

            if (!isset($organized[$section])) {
                $organized[$section] = [];
            }
            if (!isset($organized[$section][$group])) {
                $organized[$section][$group] = [];
            }

            $organized[$section][$group][] = $permission;
        }

        $this->info("\n=== الأقسام الموجودة ===");
        foreach ($organized as $section => $groups) {
            $this->info("القسم: {$section}");
            foreach ($groups as $group => $perms) {
                $this->info("  - المجموعة: {$group} (" . count($perms) . " صلاحية)");
            }
        }

        // فحص قسم العقود
        if (isset($organized['contracts'])) {
            $this->info("\n=== تفاصيل قسم العقود ===");
            foreach ($organized['contracts'] as $group => $perms) {
                $this->info("المجموعة: {$group} ({$perms->count()} صلاحية)");
                if ($group === 'rec_contracts') {
                    $this->info("  الصلاحيات في مجموعة rec_contracts:");
                    foreach ($perms as $perm) {
                        $this->info("    - {$perm->name} - {$perm->display_name}");
                    }
                }
            }
        } else {
            $this->error("❌ قسم العقود غير موجود!");
        }

        // محاكاة تنسيق البيانات للعرض
        $formattedPermissions = [];
        foreach ($organized as $sectionName => $sectionGroups) {
            $formattedPermissions[$sectionName] = [
                'groups' => []
            ];

            foreach ($sectionGroups as $groupName => $permissions) {
                $formattedPermissions[$sectionName]['groups'][$groupName] = [
                    'title' => $this->getGroupTitle($groupName),
                    'permissions' => []
                ];

                foreach ($permissions as $permission) {
                    $formattedPermissions[$sectionName]['groups'][$groupName]['permissions'][] = [
                        'name' => $permission->name,
                        'display_name' => $this->getPermissionDisplayName($permission),
                        'description' => $this->getPermissionDescription($permission)
                    ];
                }
            }
        }

        $this->info("\n=== البيانات المنسقة للعرض ===");
        if (isset($formattedPermissions['contracts'])) {
            $this->info("✅ قسم العقود موجود في البيانات المنسقة");
            if (isset($formattedPermissions['contracts']['groups']['rec_contracts'])) {
                $this->info("✅ مجموعة rec_contracts موجودة في البيانات المنسقة");
                $recCount = count($formattedPermissions['contracts']['groups']['rec_contracts']['permissions']);
                $this->info("عدد الصلاحيات في rec_contracts: {$recCount}");

                $this->info("الصلاحيات في rec_contracts:");
                foreach ($formattedPermissions['contracts']['groups']['rec_contracts']['permissions'] as $perm) {
                    $this->info("  - {$perm['name']} - {$perm['display_name']}");
                }
            } else {
                $this->error("❌ مجموعة rec_contracts غير موجودة في البيانات المنسقة");
                $this->info("المجموعات الموجودة في قسم العقود:");
                foreach ($formattedPermissions['contracts']['groups'] as $group => $data) {
                    $this->info("  - {$group}");
                }
            }
        } else {
            $this->error("❌ قسم العقود غير موجود في البيانات المنسقة");
            $this->info("الأقسام الموجودة:");
            foreach ($formattedPermissions as $section => $data) {
                $this->info("  - {$section}");
            }
        }

        $this->info("\n=== اختبار الترجمات ===");
        $this->info("ترجمة 'contracts': " . __('admin.contracts'));
        $this->info("ترجمة 'rec_contracts': " . __('admin.rec_contracts'));

        $this->info("\n=== انتهى الاختبار ===");
    }

    /**
     * الحصول على عنوان المجموعة
     */
    private function getGroupTitle($groupName)
    {
        $titles = [
            'dashboard' => 'لوحة التحكم',
            'clients' => 'العملاء',
            'maids' => 'السير الذاتية',
            'orders' => 'الطلبات',
            'return_request' => 'طلبات الاسترجاع',
            'rec_contract' => 'عقود التوظيف',
            'rec_contracts' => 'عقود الاستقدام',
            'cancel_contract' => 'عقود الإلغاء',
            'rent_contract' => 'عقود الإيجار',
            'shelter_contract' => 'عقود الإيواء',
            'shelter_workers' => 'عمال الإيواء',
            'shelter_places' => 'أماكن الإيواء',
            'travel' => 'السفر',
            'rec_offices' => 'مكاتب التوظيف',
            'complaint' => 'الشكاوى',
            'complaint_type' => 'أنواع الشكاوى',
            'sponsor_transfer' => 'تحويل الكفالة',
            'settings' => 'الإعدادات',
            'activity_log' => 'سجل النشاط',
            'job_titles' => 'المسميات الوظيفية',
            'packages' => 'الباقات',
            'nationalities' => 'الجنسيات',
            'religions' => 'الأديان',
            'skills' => 'المهارات',
            'countries' => 'الدول',
            'users' => 'المستخدمين',
            'roles' => 'الأدوار',
            'permissions' => 'الصلاحيات',
            'statistics' => 'الإحصائيات',
            'notifications' => 'الإشعارات',
            'sliders' => 'الشرائح',
            'faq' => 'الأسئلة الشائعة',
            'about_us' => 'من نحن',
            'contact' => 'اتصل بنا',
            'steps' => 'الخطوات',
            'services' => 'الخدمات',
            'content' => 'المحتوى',
            'organization' => 'المنظمة',
            'customer_service' => 'خدمة العملاء',
            'reports' => 'التقارير',
            'process' => 'العمليات',
            'communications' => 'الاتصالات',
            'system' => 'النظام',
            'backup' => 'النسخ الاحتياطية',
            'contract_settings' => 'إعدادات العقد',
            'activity' => 'النشاطات',
            'job_title' => 'المسميات الوظيفية',
            'package' => 'الباقات',
            'nationality' => 'الجنسيات',
            'religion' => 'الأديان',
            'skill' => 'المهارات',
            'country' => 'الدول',
            'user' => 'المستخدمين',
            'role' => 'الأدوار',
            'permission' => 'الصلاحيات',
            'statistic' => 'الإحصائيات',
            'notification' => 'الإشعارات',
            'slider' => 'الشرائح',
            'about' => 'من نحن',
            'step' => 'الخطوات',
            'service' => 'الخدمات',
            'report' => 'التقارير',
        ];

        return $titles[$groupName] ?? $groupName;
    }

    /**
     * الحصول على اسم عرض الصلاحية
     */
    private function getPermissionDisplayName($permission)
    {
        // إذا كان هناك display_name محفوظ في قاعدة البيانات، استخدمه
        if (!empty($permission->display_name)) {
            return $permission->display_name;
        }

        // إذا لم يكن هناك display_name، استخدم الترجمات من ملفات الترجمة
        $permissionName = $permission->name;

        // محاولة الحصول على الترجمة من ملف permissions
        $translation = __('permissions.' . $permissionName);

        // إذا كانت الترجمة موجودة ولا تساوي اسم الصلاحية، استخدمها
        if ($translation !== 'permissions.' . $permissionName) {
            return $translation;
        }

        // إذا لم توجد ترجمة، استخدم القيم الافتراضية
        $displayNames = [
            'dashboard_view' => 'عرض لوحة التحكم',
            'DASHBOARD_VIEW_STATISTICS' => 'عرض إحصائيات لوحة التحكم',
            'dashboard_analytics' => 'إحصائيات لوحة التحكم',
            'dashboard_export' => 'تصدير بيانات لوحة التحكم',
            'dashboard_manage' => 'إدارة لوحة التحكم',
            'dashboard_clients_count' => 'عرض عدد العملاء',
            'dashboard_contacts_count' => 'عرض عدد رسائل التواصل',
            'dashboard_current_orders_count' => 'عرض عدد الطلبات الحالية',
            'dashboard_cv_count' => 'عرض عدد السير الذاتية',
            'dashboard_employees_count' => 'عرض عدد الموظفين',
            'dashboard_completed_cv_count' => 'عرض عدد السير الذاتية المكتملة',
            'dashboard_orders_statistics' => 'عرض إحصائيات الطلبات',
            'dashboard_top_customer_services' => 'عرض أفضل خدمة عملاء',
            'dashboard_orders_types' => 'عرض أنواع الطلبات',
        ];

        return $displayNames[$permissionName] ?? $permissionName;
    }

    /**
     * الحصول على وصف الصلاحية
     */
    private function getPermissionDescription($permission)
    {
        // إذا كان هناك description محفوظ في قاعدة البيانات، استخدمه
        if (!empty($permission->description)) {
            return $permission->description;
        }

        // إذا لم يكن هناك description، استخدم القيم الافتراضية
        $permissionName = $permission->name;

        $descriptions = [
            'dashboard_view' => 'إمكانية عرض لوحة التحكم الرئيسية',
            'DASHBOARD_VIEW_STATISTICS' => 'إمكانية عرض إحصائيات لوحة التحكم',
            'dashboard_analytics' => 'إمكانية عرض الإحصائيات والتحليلات',
            'dashboard_export' => 'إمكانية تصدير بيانات لوحة التحكم',
            'dashboard_manage' => 'إمكانية إدارة جميع مكونات لوحة التحكم',
            'dashboard_clients_count' => 'إمكانية عرض عدد العملاء في لوحة التحكم',
            'dashboard_contacts_count' => 'إمكانية عرض عدد رسائل التواصل في لوحة التحكم',
            'dashboard_current_orders_count' => 'إمكانية عرض عدد الطلبات الحالية في لوحة التحكم',
            'dashboard_cv_count' => 'إمكانية عرض عدد السير الذاتية في لوحة التحكم',
            'dashboard_employees_count' => 'إمكانية عرض عدد الموظفين في لوحة التحكم',
            'dashboard_completed_cv_count' => 'إمكانية عرض عدد السير الذاتية المكتملة في لوحة التحكم',
            'dashboard_orders_statistics' => 'إمكانية عرض إحصائيات الطلبات في لوحة التحكم',
            'dashboard_top_customer_services' => 'إمكانية عرض أفضل خدمة عملاء في لوحة التحكم',
            'dashboard_orders_types' => 'إمكانية عرض أنواع الطلبات في لوحة التحكم',
        ];

        return $descriptions[$permissionName] ?? 'صلاحية للوصول إلى هذه الميزة';
    }
}
