<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\auth\Admin;

class GrantAllPermissionsToAdminSeeder extends Seeder
{
    public function run()
    {
        // اجلب جميع الصلاحيات
        $permissions = Permission::pluck('name')->toArray();

        // اجلب جميع المدراء (يمكنك تخصيصه لمستخدم معين)
        $admins = Admin::all();

        foreach ($admins as $admin) {
            // أنشئ دور خاص إذا لم يكن موجود
            $role = Role::firstOrCreate([
                'name' => 'super_admin',
                'guard_name' => 'admin',
            ]);
            // أعطِ الدور جميع الصلاحيات
            $role->syncPermissions($permissions);
            // اربط الدور بالمستخدم
            $admin->syncRoles([$role->name]);
        }
    }
}