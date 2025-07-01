<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TravelPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Travel permissions
        Permission::firstOrCreate([
            'name' => 'travel_view',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'travel_create',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'travel_edit',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'travel_delete',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'travel_control',
            'guard_name' => 'admin'
        ]);

        $this->command->info('Travel permissions created successfully.');
    }
}
