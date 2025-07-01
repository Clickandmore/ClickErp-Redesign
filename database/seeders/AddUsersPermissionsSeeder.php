<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddUsersPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'users_create' => 'إضافة مستخدم',
            'users_edit' => 'تعديل المستخدمين',
            'users_delete' => 'حذف المستخدمين',
            'users_control' => 'التحكم في المستخدمين',
            'users_export' => 'تصدير المستخدمين',
            'users_import' => 'استيراد المستخدمين',
            'users_manage' => 'إدارة المستخدمين',
            'users_activate' => 'تفعيل المستخدمين',
            'users_deactivate' => 'إلغاء تفعيل المستخدمين',
        ];

        $addedCount = 0;
        foreach ($permissions as $name => $display_name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'admin',
            ], [
                'display_name' => $display_name,
            ]);

            if ($permission->wasRecentlyCreated) {
                $addedCount++;
            }
        }

        // إعطاء هذه الصلاحيات لجميع الأدوار الموجودة
        $roles = Role::where('guard_name', 'admin')->get();
        $newPermissions = Permission::whereIn('name', array_keys($permissions))->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($newPermissions);
        }

        $this->command->info("✅ تم إضافة {$addedCount} صلاحية جديدة لإدارة المستخدمين");
        $this->command->info('✅ تم تعيين الصلاحيات لجميع الأدوار');

        // عرض الصلاحيات المضافة
        if ($addedCount > 0) {
            $this->command->info('الصلاحيات المضافة:');
            foreach ($permissions as $name => $display_name) {
                $permission = Permission::where('name', $name)->first();
                if ($permission && $permission->wasRecentlyCreated) {
                    $this->command->line("  - {$name} => {$display_name}");
                }
            }
        }
    }
}