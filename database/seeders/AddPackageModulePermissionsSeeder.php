<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AddPackageModulePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'crm_module', 'group' => 'crm'],
            ['name' => 'hr_module', 'group' => 'hr'],
            ['name' => 'finance_module', 'group' => 'finance'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'admin',
                ],
                [
                    'group_name' => $permission['group'],
                    'section_name' => 'modules',
                ]
            );
        }
    }
}
