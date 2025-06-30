<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\NewPermKeys;

class CleanPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('بدء إعادة تهيئة الصلاحيات...');

        // حذف جميع الصلاحيات الموجودة
        Permission::truncate();
        $this->command->info('تم حذف جميع الصلاحيات الموجودة');

        // إنشاء الصلاحيات الجديدة
        $this->createGeneralPermissions();
        $this->createUsersManagementPermissions();
        $this->createCvManagementPermissions();

        $this->command->info('تم إعادة تهيئة الصلاحيات بنجاح!');
    }

    /**
     * إنشاء صلاحيات القسم العام
     */
    private function createGeneralPermissions(): void
    {
        $permissions = [
            [
                'name' => 'dashboard_view',
                'group_name' => 'dashboard',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'dashboard_analytics',
                'group_name' => 'dashboard',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'dashboard_export',
                'group_name' => 'dashboard',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'dashboard_manage',
                'group_name' => 'dashboard',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'settings_view',
                'group_name' => 'settings',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'settings_edit',
                'group_name' => 'settings',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'settings_manage',
                'group_name' => 'settings',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'activity_log_view',
                'group_name' => 'activity',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'activity_log_export',
                'group_name' => 'activity',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'activity_log_manage',
                'group_name' => 'activity',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $this->command->info('تم إنشاء صلاحيات القسم العام: ' . count($permissions) . ' صلاحية');
    }

    /**
     * إنشاء صلاحيات إدارة المستخدمين
     */
    private function createUsersManagementPermissions(): void
    {
        $permissions = [
            // صلاحيات المستخدمين الأساسية
            [
                'name' => 'users_view',
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'users_create',
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'users_edit',
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'users_delete',
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'users_export',
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'users_import',
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'users_activate',
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'users_deactivate',
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'users_manage',
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],

            // صلاحيات العملاء
            [
                'name' => 'clients_view',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_create',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_edit',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_delete',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_export',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_import',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_activate',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_deactivate',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_manage',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],

            // صلاحيات العملاء الإضافية
            [
                'name' => 'clients_approve',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_reject',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_block',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_unblock',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_verify',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_assign',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_transfer',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_backup',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_restore',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_analytics',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_reports',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_notifications',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'clients_settings',
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],

            // صلاحيات الأدوار
            [
                'name' => 'roles_view',
                'group_name' => 'roles',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'roles_create',
                'group_name' => 'roles',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'roles_edit',
                'group_name' => 'roles',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'roles_delete',
                'group_name' => 'roles',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'roles_assign_permissions',
                'group_name' => 'roles',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'roles_manage',
                'group_name' => 'roles',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],

            // صلاحيات الصلاحيات
            [
                'name' => 'permissions_view',
                'group_name' => 'permissions',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'permissions_create',
                'group_name' => 'permissions',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'permissions_edit',
                'group_name' => 'permissions',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'permissions_delete',
                'group_name' => 'permissions',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'permissions_assign',
                'group_name' => 'permissions',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'permissions_manage',
                'group_name' => 'permissions',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $this->command->info('تم إنشاء صلاحيات إدارة المستخدمين: ' . count($permissions) . ' صلاحية');
    }

    /**
     * إنشاء صلاحيات إدارة السير الذاتية
     */
    private function createCvManagementPermissions(): void
    {
        $permissions = [
            [
                'name' => 'cv_view',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_create',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_edit',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_delete',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_pin',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_unpin',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_approve',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_reject',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_export',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_import',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_manage',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_download',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_upload',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_convert',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_analytics',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => 'cv_reports',
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $this->command->info('تم إنشاء صلاحيات إدارة السير الذاتية: ' . count($permissions) . ' صلاحية');
    }
}
