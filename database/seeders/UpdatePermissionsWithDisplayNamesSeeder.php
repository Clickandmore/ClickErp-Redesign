<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\PermKeys;

class UpdatePermissionsWithDisplayNamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // تحديث الصلاحيات الموجودة مع display_name و description
        $permissionsData = [
            // صلاحيات لوحة التحكم
            [
                'name' => PermKeys::DASHBOARD_VIEW,
                'display_name' => 'عرض لوحة التحكم',
                'description' => 'إمكانية عرض لوحة التحكم الرئيسية',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_VIEW_STATISTICS,
                'display_name' => 'عرض إحصائيات لوحة التحكم',
                'description' => 'إمكانية عرض إحصائيات لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_ANALYTICS,
                'display_name' => 'إحصائيات لوحة التحكم',
                'description' => 'إمكانية عرض الإحصائيات والتحليلات',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_EXPORT,
                'display_name' => 'تصدير بيانات لوحة التحكم',
                'description' => 'إمكانية تصدير بيانات لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],

            // صلاحيات أقسام الصفحة الرئيسية
            [
                'name' => PermKeys::DASHBOARD_CLIENTS_COUNT,
                'display_name' => 'عرض عدد العملاء',
                'description' => 'إمكانية عرض عدد العملاء في لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_CONTACTS_COUNT,
                'display_name' => 'عرض عدد رسائل التواصل',
                'description' => 'إمكانية عرض عدد رسائل التواصل في لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_CURRENT_ORDERS_COUNT,
                'display_name' => 'عرض عدد الطلبات الحالية',
                'description' => 'إمكانية عرض عدد الطلبات الحالية في لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_CV_COUNT,
                'display_name' => 'عرض عدد السير الذاتية',
                'description' => 'إمكانية عرض عدد السير الذاتية في لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_EMPLOYEES_COUNT,
                'display_name' => 'عرض عدد الموظفين',
                'description' => 'إمكانية عرض عدد الموظفين في لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_COMPLETED_CV_COUNT,
                'display_name' => 'عرض عدد السير الذاتية المكتملة',
                'description' => 'إمكانية عرض عدد السير الذاتية المكتملة في لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_ORDERS_STATISTICS,
                'display_name' => 'عرض إحصائيات الطلبات',
                'description' => 'إمكانية عرض إحصائيات الطلبات في لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_TOP_CUSTOMER_SERVICES,
                'display_name' => 'عرض أفضل خدمة عملاء',
                'description' => 'إمكانية عرض أفضل خدمة عملاء في لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],
            [
                'name' => PermKeys::DASHBOARD_ORDERS_TYPES,
                'display_name' => 'عرض أنواع الطلبات',
                'description' => 'إمكانية عرض أنواع الطلبات في لوحة التحكم',
                'group_name' => 'dashboard',
                'section_name' => 'dashboard'
            ],

            // صلاحيات العملاء
            [
                'name' => PermKeys::CLIENTS_VIEW,
                'display_name' => 'عرض العملاء',
                'description' => 'إمكانية عرض قائمة العملاء',
                'group_name' => 'clients',
                'section_name' => 'clients_maids'
            ],
            [
                'name' => PermKeys::CLIENTS_CREATE,
                'display_name' => 'إضافة عميل',
                'description' => 'إمكانية إضافة عميل جديد',
                'group_name' => 'clients',
                'section_name' => 'clients_maids'
            ],
            [
                'name' => PermKeys::CLIENTS_EDIT,
                'display_name' => 'تعديل العملاء',
                'description' => 'إمكانية تعديل بيانات العملاء',
                'group_name' => 'clients',
                'section_name' => 'clients_maids'
            ],
            [
                'name' => PermKeys::CLIENTS_DELETE,
                'display_name' => 'حذف العملاء',
                'description' => 'إمكانية حذف العملاء',
                'group_name' => 'clients',
                'section_name' => 'clients_maids'
            ],
            [
                'name' => PermKeys::CLIENTS_EXPORT,
                'display_name' => 'تصدير بيانات العملاء',
                'description' => 'إمكانية تصدير بيانات العملاء',
                'group_name' => 'clients',
                'section_name' => 'clients_maids'
            ],

            // صلاحيات السير الذاتية
            [
                'name' => PermKeys::MAIDS_VIEW,
                'display_name' => 'عرض السير الذاتية',
                'description' => 'إمكانية عرض قائمة السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'clients_maids'
            ],
            [
                'name' => PermKeys::MAIDS_CREATE,
                'display_name' => 'إضافة سيرة ذاتية',
                'description' => 'إمكانية إضافة سيرة ذاتية جديدة',
                'group_name' => 'maids',
                'section_name' => 'clients_maids'
            ],
            [
                'name' => PermKeys::MAIDS_EDIT,
                'display_name' => 'تعديل السير الذاتية',
                'description' => 'إمكانية تعديل السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'clients_maids'
            ],
            [
                'name' => PermKeys::MAIDS_DELETE,
                'display_name' => 'حذف السير الذاتية',
                'description' => 'إمكانية حذف السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'clients_maids'
            ],
            [
                'name' => PermKeys::MAIDS_EXPORT,
                'display_name' => 'تصدير السير الذاتية',
                'description' => 'إمكانية تصدير السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'clients_maids'
            ],
            [
                'name' => PermKeys::MAIDS_CONTROL,
                'display_name' => 'التحكم في السير الذاتية',
                'description' => 'إمكانية التحكم في جميع عمليات السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'clients_maids'
            ],
        ];

        foreach ($permissionsData as $permissionData) {
            $permission = Permission::where('name', $permissionData['name'])->first();

            if ($permission) {
                $permission->update([
                    'display_name' => $permissionData['display_name'],
                    'description' => $permissionData['description'],
                    'group_name' => $permissionData['group_name'],
                    'section_name' => $permissionData['section_name'],
                ]);
            } else {
                // إنشاء الصلاحية إذا لم تكن موجودة
                Permission::create([
                    'name' => $permissionData['name'],
                    'display_name' => $permissionData['display_name'],
                    'description' => $permissionData['description'],
                    'group_name' => $permissionData['group_name'],
                    'section_name' => $permissionData['section_name'],
                    'guard_name' => 'admin',
                ]);
            }
        }

        $this->command->info('Permissions updated with display names and descriptions successfully!');
    }
}
