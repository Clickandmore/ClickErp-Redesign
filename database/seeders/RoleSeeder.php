<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);

        // Create basic permissions
        $permissions = [
            'complaint_view',
            'complaint_delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'admin',
                'group_name' => 'complaints',
                'section_name' => 'customer_service'
            ]);
        }

        // Assign all permissions to admin role
        $adminRole->givePermissionTo($permissions);
    }
}
