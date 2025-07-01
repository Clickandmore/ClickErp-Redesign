<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddPermissionsManagementPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'permissions_view' => 'عرض الصلاحيات',
            'permissions_assign' => 'تعيين الصلاحيات',
            'permissions_create' => 'إنشاء صلاحيات',
            'permissions_delete' => 'حذف صلاحيات',
        ];

        foreach ($permissions as $name => $display_name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'admin',
                'display_name' => $display_name,
            ]);
        }

        // إعطاء هذه الصلاحيات لجميع الأدوار الموجودة
        $roles = Role::where('guard_name', 'admin')->get();
        $newPermissions = Permission::whereIn('name', array_keys($permissions))->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($newPermissions);
        }

        $this->command->info('✅ تم إضافة صلاحيات إدارة الصلاحيات بنجاح');
        $this->command->info('✅ تم تعيين الصلاحيات لجميع الأدوار');
    }
}