<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\PermKeys;

class DashboardPermissionsWithTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // صلاحيات الصفحة الرئيسية مع الترجمات
        $dashboardPermissions = [
            [
                'name' => PermKeys::DASHBOARD_CLIENTS_COUNT,
                'ar_name' => 'عرض عدد العملاء',
                'en_name' => 'View Clients Count'
            ],
            [
                'name' => PermKeys::DASHBOARD_CONTACTS_COUNT,
                'ar_name' => 'عرض عدد رسائل التواصل',
                'en_name' => 'View Contacts Count'
            ],
            [
                'name' => PermKeys::DASHBOARD_CURRENT_ORDERS_COUNT,
                'ar_name' => 'عرض عدد الطلبات الحالية',
                'en_name' => 'View Current Orders Count'
            ],
            [
                'name' => PermKeys::DASHBOARD_CV_COUNT,
                'ar_name' => 'عرض عدد السير الذاتية',
                'en_name' => 'View CV Count'
            ],
            [
                'name' => PermKeys::DASHBOARD_EMPLOYEES_COUNT,
                'ar_name' => 'عرض عدد الموظفين',
                'en_name' => 'View Employees Count'
            ],
            [
                'name' => PermKeys::DASHBOARD_COMPLETED_CV_COUNT,
                'ar_name' => 'عرض عدد السير الذاتية المكتملة',
                'en_name' => 'View Completed CV Count'
            ],
            [
                'name' => PermKeys::DASHBOARD_ORDERS_STATISTICS,
                'ar_name' => 'عرض إحصائيات الطلبات',
                'en_name' => 'View Orders Statistics'
            ],
            [
                'name' => PermKeys::DASHBOARD_TOP_CUSTOMER_SERVICES,
                'ar_name' => 'عرض أفضل خدمة عملاء',
                'en_name' => 'View Top Customer Services'
            ],
            [
                'name' => PermKeys::DASHBOARD_ORDERS_TYPES,
                'ar_name' => 'عرض أنواع الطلبات',
                'en_name' => 'View Orders Types'
            ],
        ];

        foreach ($dashboardPermissions as $permissionData) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionData['name'],
                'guard_name' => 'admin'
            ]);

            // إضافة الترجمات إذا كان هناك جدول للترجمات
            if (method_exists($permission, 'setTranslation')) {
                $permission->setTranslation('display_name', 'ar', $permissionData['ar_name']);
                $permission->setTranslation('display_name', 'en', $permissionData['en_name']);
                $permission->save();
            }
        }

        $this->command->info('Dashboard permissions with translations seeded successfully!');
    }
}
