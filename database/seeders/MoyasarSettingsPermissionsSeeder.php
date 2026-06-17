<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class MoyasarSettingsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            [
                'name' => PermKeys::MOYASAR_SETTINGS,
                'guard_name' => 'admin',
            ],
            [
                'group_name' => 'moyasar_settings',
                'section_name' => 'settings',
                'display_name' => 'إعدادات ميسر',
            ]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
