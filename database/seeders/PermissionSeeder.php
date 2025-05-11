<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Add English permissions for complaints
        Permission::firstOrCreate([
            'name' => 'complaints-read',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'complaints-create',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'complaints-update',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'complaints-delete',
            'guard_name' => 'admin'
        ]);
    }
}
