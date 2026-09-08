<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\auth\Admin;

class AddSponsorshipTransferPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الصلاحيات
        $permissions = [
            'sponsor_transfer_view',
            'sponsor_transfer_create',
            'sponsor_transfer_edit',
            'sponsor_transfer_delete',
            'sponsor_transfer_control'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }

        // إضافة الصلاحيات لجميع المشرفين
        $admins = Admin::all();
        $role = Role::where('name', 'super_admin')->first();

        if ($role) {
            foreach ($permissions as $permission) {
                $role->givePermissionTo($permission);
            }
        }

        // إضافة الصلاحيات للمشرفين الفرديين
        foreach ($admins as $admin) {
            foreach ($permissions as $permission) {
                $admin->givePermissionTo($permission);
            }
        }

        $this->command->info('تم إضافة صلاحيات نقل الخدمات بنجاح!');
    }
}
