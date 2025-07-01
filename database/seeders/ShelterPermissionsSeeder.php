<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ShelterPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Shelter Contract permissions
        Permission::firstOrCreate([
            'name' => 'shelter_contract_view',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_contract_create',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_contract_edit',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_contract_delete',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_contract_control',
            'guard_name' => 'admin'
        ]);

        // Shelter Workers permissions
        Permission::firstOrCreate([
            'name' => 'shelter_workers_view',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_workers_create',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_workers_edit',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_workers_delete',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_workers_control',
            'guard_name' => 'admin'
        ]);

        // Shelter Places permissions
        Permission::firstOrCreate([
            'name' => 'shelter_places_view',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_places_create',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_places_edit',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_places_delete',
            'guard_name' => 'admin'
        ]);

        Permission::firstOrCreate([
            'name' => 'shelter_places_control',
            'guard_name' => 'admin'
        ]);

        $this->command->info('Shelter permissions created successfully.');
    }
}
