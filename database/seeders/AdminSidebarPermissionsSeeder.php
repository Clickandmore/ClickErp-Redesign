<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Database\Seeder;

class AdminSidebarPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (AdminRoutePermissionMatrix::allPermissions() as $permissionName) {
            Permission::withoutGlobalScopes()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'admin',
            ]);
        }
    }
}
