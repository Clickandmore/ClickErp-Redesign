<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\NewPermKeys;

class NewRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الأدوار الأساسية
        $this->createSuperAdminRole();
        $this->createAdminRole();
        $this->createManagerRole();
        $this->createSupervisorRole();
        $this->createOperatorRole();
        $this->createViewerRole();
    }

    /**
     * إنشاء دور مدير النظام (Super Admin)
     */
    private function createSuperAdminRole(): void
    {
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'admin'
        ], [
            'display_name' => 'مدير النظام',
            'description' => 'لديه جميع الصلاحيات في النظام'
        ]);

        // منح جميع الصلاحيات
        $allPermissions = NewPermKeys::getAllPermissions();
        $superAdmin->syncPermissions($allPermissions);
    }

    /**
     * إنشاء دور المدير (Admin)
     */
    private function createAdminRole(): void
    {
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'admin'
        ], [
            'display_name' => 'مدير',
            'description' => 'مدير النظام مع صلاحيات إدارية شاملة'
        ]);

        // صلاحيات المدير
        $adminPermissions = [
            // لوحة التحكم
            NewPermKeys::DASHBOARD_VIEW,
            NewPermKeys::DASHBOARD_ANALYTICS,
            NewPermKeys::DASHBOARD_EXPORT,

            // إدارة المستخدمين
            NewPermKeys::USERS_VIEW,
            NewPermKeys::USERS_CREATE,
            NewPermKeys::USERS_EDIT,
            NewPermKeys::USERS_DELETE,
            NewPermKeys::USERS_EXPORT,
            NewPermKeys::USERS_MANAGE,

            // إدارة العملاء
            NewPermKeys::CLIENTS_VIEW,
            NewPermKeys::CLIENTS_CREATE,
            NewPermKeys::CLIENTS_EDIT,
            NewPermKeys::CLIENTS_DELETE,
            NewPermKeys::CLIENTS_EXPORT,
            NewPermKeys::CLIENTS_MANAGE,

            // إدارة العقود
            NewPermKeys::CONTRACTS_VIEW,
            NewPermKeys::CONTRACTS_CREATE,
            NewPermKeys::CONTRACTS_EDIT,
            NewPermKeys::CONTRACTS_DELETE,
            NewPermKeys::CONTRACTS_APPROVE,
            NewPermKeys::CONTRACTS_REJECT,
            NewPermKeys::CONTRACTS_ASSIGN,
            NewPermKeys::CONTRACTS_EXPORT,
            NewPermKeys::CONTRACTS_MANAGE,

            // إدارة الطلبات
            NewPermKeys::ORDERS_VIEW,
            NewPermKeys::ORDERS_CREATE,
            NewPermKeys::ORDERS_EDIT,
            NewPermKeys::ORDERS_DELETE,
            NewPermKeys::ORDERS_APPROVE,
            NewPermKeys::ORDERS_REJECT,
            NewPermKeys::ORDERS_ASSIGN,
            NewPermKeys::ORDERS_EXPORT,
            NewPermKeys::ORDERS_MANAGE,

            // إدارة الخادمات
            NewPermKeys::MAIDS_VIEW,
            NewPermKeys::MAIDS_CREATE,
            NewPermKeys::MAIDS_EDIT,
            NewPermKeys::MAIDS_DELETE,
            NewPermKeys::MAIDS_APPROVE,
            NewPermKeys::MAIDS_REJECT,
            NewPermKeys::MAIDS_ASSIGN,
            NewPermKeys::MAIDS_EXPORT,
            NewPermKeys::MAIDS_MANAGE,

            // إدارة المحتوى
            NewPermKeys::CONTENT_VIEW,
            NewPermKeys::CONTENT_CREATE,
            NewPermKeys::CONTENT_EDIT,
            NewPermKeys::CONTENT_DELETE,
            NewPermKeys::CONTENT_PUBLISH,
            NewPermKeys::CONTENT_MANAGE,

            // التقارير والإحصائيات
            NewPermKeys::REPORTS_VIEW,
            NewPermKeys::REPORTS_CREATE,
            NewPermKeys::REPORTS_EDIT,
            NewPermKeys::REPORTS_EXPORT,
            NewPermKeys::REPORTS_GENERATE,
            NewPermKeys::REPORTS_MANAGE,

            NewPermKeys::STATISTICS_VIEW,
            NewPermKeys::STATISTICS_CREATE,
            NewPermKeys::STATISTICS_EDIT,
            NewPermKeys::STATISTICS_EXPORT,
            NewPermKeys::STATISTICS_MANAGE,

            // إدارة النظام (محدودة)
            NewPermKeys::SETTINGS_VIEW,
            NewPermKeys::SETTINGS_EDIT,
            NewPermKeys::ACTIVITY_LOG_VIEW,
        ];

        $admin->syncPermissions($adminPermissions);
    }

    /**
     * إنشاء دور مدير القسم (Manager)
     */
    private function createManagerRole(): void
    {
        $manager = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'admin'
        ], [
            'display_name' => 'مدير قسم',
            'description' => 'مدير قسم مع صلاحيات إدارية محدودة'
        ]);

        // صلاحيات مدير القسم
        $managerPermissions = [
            // لوحة التحكم
            NewPermKeys::DASHBOARD_VIEW,
            NewPermKeys::DASHBOARD_ANALYTICS,

            // إدارة المستخدمين (محدودة)
            NewPermKeys::USERS_VIEW,
            NewPermKeys::USERS_CREATE,
            NewPermKeys::USERS_EDIT,

            // إدارة العملاء (محدودة)
            NewPermKeys::CLIENTS_VIEW,
            NewPermKeys::CLIENTS_CREATE,
            NewPermKeys::CLIENTS_EDIT,

            // إدارة العقود (محدودة)
            NewPermKeys::CONTRACTS_VIEW,
            NewPermKeys::CONTRACTS_CREATE,
            NewPermKeys::CONTRACTS_EDIT,
            NewPermKeys::CONTRACTS_APPROVE,
            NewPermKeys::CONTRACTS_REJECT,

            // إدارة الطلبات (محدودة)
            NewPermKeys::ORDERS_VIEW,
            NewPermKeys::ORDERS_CREATE,
            NewPermKeys::ORDERS_EDIT,
            NewPermKeys::ORDERS_APPROVE,
            NewPermKeys::ORDERS_REJECT,

            // إدارة الخادمات (محدودة)
            NewPermKeys::MAIDS_VIEW,
            NewPermKeys::MAIDS_CREATE,
            NewPermKeys::MAIDS_EDIT,
            NewPermKeys::MAIDS_APPROVE,
            NewPermKeys::MAIDS_REJECT,

            // إدارة المحتوى (محدودة)
            NewPermKeys::CONTENT_VIEW,
            NewPermKeys::CONTENT_CREATE,
            NewPermKeys::CONTENT_EDIT,
            NewPermKeys::CONTENT_PUBLISH,

            // التقارير (محدودة)
            NewPermKeys::REPORTS_VIEW,
            NewPermKeys::REPORTS_EXPORT,
            NewPermKeys::STATISTICS_VIEW,
            NewPermKeys::STATISTICS_EXPORT,
        ];

        $manager->syncPermissions($managerPermissions);
    }

    /**
     * إنشاء دور المشرف (Supervisor)
     */
    private function createSupervisorRole(): void
    {
        $supervisor = Role::firstOrCreate([
            'name' => 'supervisor',
            'guard_name' => 'admin'
        ], [
            'display_name' => 'مشرف',
            'description' => 'مشرف مع صلاحيات مراجعة وإدارة محدودة'
        ]);

        // صلاحيات المشرف
        $supervisorPermissions = [
            // لوحة التحكم
            NewPermKeys::DASHBOARD_VIEW,

            // عرض البيانات
            NewPermKeys::USERS_VIEW,
            NewPermKeys::CLIENTS_VIEW,
            NewPermKeys::CONTRACTS_VIEW,
            NewPermKeys::ORDERS_VIEW,
            NewPermKeys::MAIDS_VIEW,
            NewPermKeys::CONTENT_VIEW,

            // إدارة محدودة
            NewPermKeys::USERS_CREATE,
            NewPermKeys::CLIENTS_CREATE,
            NewPermKeys::CONTRACTS_CREATE,
            NewPermKeys::ORDERS_CREATE,
            NewPermKeys::MAIDS_CREATE,
            NewPermKeys::CONTENT_CREATE,

            // مراجعة وموافقة
            NewPermKeys::CONTRACTS_APPROVE,
            NewPermKeys::CONTRACTS_REJECT,
            NewPermKeys::ORDERS_APPROVE,
            NewPermKeys::ORDERS_REJECT,
            NewPermKeys::MAIDS_APPROVE,
            NewPermKeys::MAIDS_REJECT,

            // التقارير
            NewPermKeys::REPORTS_VIEW,
            NewPermKeys::STATISTICS_VIEW,
        ];

        $supervisor->syncPermissions($supervisorPermissions);
    }

    /**
     * إنشاء دور المشغل (Operator)
     */
    private function createOperatorRole(): void
    {
        $operator = Role::firstOrCreate([
            'name' => 'operator',
            'guard_name' => 'admin'
        ], [
            'display_name' => 'مشغل',
            'description' => 'مشغل مع صلاحيات إدخال البيانات الأساسية'
        ]);

        // صلاحيات المشغل
        $operatorPermissions = [
            // لوحة التحكم
            NewPermKeys::DASHBOARD_VIEW,

            // عرض البيانات
            NewPermKeys::USERS_VIEW,
            NewPermKeys::CLIENTS_VIEW,
            NewPermKeys::CONTRACTS_VIEW,
            NewPermKeys::ORDERS_VIEW,
            NewPermKeys::MAIDS_VIEW,
            NewPermKeys::CONTENT_VIEW,

            // إدخال البيانات الأساسية
            NewPermKeys::USERS_CREATE,
            NewPermKeys::CLIENTS_CREATE,
            NewPermKeys::CONTRACTS_CREATE,
            NewPermKeys::ORDERS_CREATE,
            NewPermKeys::MAIDS_CREATE,
            NewPermKeys::CONTENT_CREATE,

            // تعديل محدود
            NewPermKeys::USERS_EDIT,
            NewPermKeys::CLIENTS_EDIT,
            NewPermKeys::CONTRACTS_EDIT,
            NewPermKeys::ORDERS_EDIT,
            NewPermKeys::MAIDS_EDIT,
            NewPermKeys::CONTENT_EDIT,
        ];

        $operator->syncPermissions($operatorPermissions);
    }

    /**
     * إنشاء دور المستعرض (Viewer)
     */
    private function createViewerRole(): void
    {
        $viewer = Role::firstOrCreate([
            'name' => 'viewer',
            'guard_name' => 'admin'
        ], [
            'display_name' => 'مستعرض',
            'description' => 'مستعرض مع صلاحيات عرض البيانات فقط'
        ]);

        // صلاحيات المستعرض
        $viewerPermissions = [
            // لوحة التحكم
            NewPermKeys::DASHBOARD_VIEW,

            // عرض البيانات فقط
            NewPermKeys::USERS_VIEW,
            NewPermKeys::CLIENTS_VIEW,
            NewPermKeys::CONTRACTS_VIEW,
            NewPermKeys::ORDERS_VIEW,
            NewPermKeys::MAIDS_VIEW,
            NewPermKeys::CONTENT_VIEW,
            NewPermKeys::REPORTS_VIEW,
            NewPermKeys::STATISTICS_VIEW,
        ];

        $viewer->syncPermissions($viewerPermissions);
    }
}
