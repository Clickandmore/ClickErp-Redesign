<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddOrderChangePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // إضافة الصلاحيات الجديدة
        $permissions = [
            [
                'name' => \App\PermKeys::ORDERS_CHANGE_MAID,
                'display_name' => 'تغيير السيرة الذاتية للطلب',
                'description' => 'إمكانية تغيير السيرة الذاتية المرتبطة بالطلب',
                'guard_name' => 'admin'
            ],
            [
                'name' => \App\PermKeys::ORDERS_CHANGE_RESPONSIBLE,
                'display_name' => 'تغيير مسئول الطلب',
                'description' => 'إمكانية تغيير الموظف المسؤول عن الطلب',
                'guard_name' => 'admin'
            ]
        ];

        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionData['name'],
                'guard_name' => $permissionData['guard_name']
            ], [
                'display_name' => $permissionData['display_name'],
                'description' => $permissionData['description']
            ]);

            // إضافة الصلاحية لجميع الأدوار الموجودة
            $roles = Role::where('guard_name', 'admin')->get();
            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        $this->command->info('تم إضافة صلاحيات تغيير بيانات الطلب بنجاح!');
    }
}
