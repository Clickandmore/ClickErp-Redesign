<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddRecContractsUpdatePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الصلاحية الجديدة أو تحديث بياناتها
        $permission = Permission::updateOrCreate(
            [
                'name' => 'rec_contracts_update',
                'guard_name' => 'admin',
            ],
            [
                'display_name' => 'تحديث مراحل وبيانات عقد الاستقدام',
                'description' => 'إمكانية تحديث مراحل العقد، اختيار العاملة، إضافة الملاحظات، وإدارة الإرسالية.',
                'group_name' => 'rec_contracts',
                'section_name' => 'recruitment_contracts',
            ]
        );

        // إضافة الصلاحية لجميع الأدوار التي لديها صلاحيات عقود الاستقدام
        $roles = Role::whereHas('permissions', function($query) {
            $query->where('name', 'like', 'rec_contracts_%');
        })->get();

        foreach ($roles as $role) {
            if (!$role->hasPermissionTo('rec_contracts_update')) {
                $role->givePermissionTo('rec_contracts_update');
            }
        }

        $this->command->info('تم إضافة صلاحية rec_contracts_update بنجاح');
    }
}
