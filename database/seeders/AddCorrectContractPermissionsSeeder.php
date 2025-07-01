<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Spatie\Permission\Models\Role;

class AddCorrectContractPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // حذف الصلاحيات الخاطئة (rec_contracts_)
        Permission::where('name', 'like', 'rec_contracts_%')->delete();

        // إضافة الصلاحيات الصحيحة (rec_contract_)
        $permissions = [
            [
                'name' => 'rec_contract_view',
                'display_name' => 'عرض عقود الاستقدام',
                'description' => 'إمكانية عرض عقود الاستقدام'
            ],
            [
                'name' => 'rec_contract_create',
                'display_name' => 'إضافة عقد استقدام',
                'description' => 'إمكانية إضافة عقد استقدام جديد'
            ],
            [
                'name' => 'rec_contract_edit',
                'display_name' => 'تعديل عقود الاستقدام',
                'description' => 'إمكانية تعديل عقود الاستقدام'
            ],
            [
                'name' => 'rec_contract_delete',
                'display_name' => 'حذف عقود الاستقدام',
                'description' => 'إمكانية حذف عقود الاستقدام'
            ],
            [
                'name' => 'rec_contract_control',
                'display_name' => 'التحكم في عقود الاستقدام',
                'description' => 'إمكانية التحكم الكامل في عقود الاستقدام'
            ],
        ];

        // إضافة الصلاحيات
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'guard_name' => 'admin'
            ]);
        }

        // إضافة الصلاحيات إلى دور Administrator
        $administratorRole = Role::where('name', 'Administrator')->first();
        if ($administratorRole) {
            $permissionNames = array_column($permissions, 'name');
            $permissionsToAssign = Permission::whereIn('name', $permissionNames)->get();
            $administratorRole->syncPermissions($permissionsToAssign);
        }

        $this->command->info('تم إضافة ' . count($permissions) . ' صلاحية صحيحة لعقود التوظيف بنجاح!');
    }
}