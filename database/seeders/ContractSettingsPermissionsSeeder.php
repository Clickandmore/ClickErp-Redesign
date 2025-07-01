<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ContractSettingsPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Add contract settings permission
        Permission::firstOrCreate([
            'name' => 'contract_settings',
            'guard_name' => 'admin'
        ]);

        $this->command->info('Contract settings permission created successfully.');
    }
}
