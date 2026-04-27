<?php

namespace Nami\AccessControl\Database\Seeders;

use App\Enums\AdminTypeisEnum;
use App\Models\Nami\Admin;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LaratrustSeeder extends Seeder
{
    public function run()
    {
        $this->truncateLaratrustTables(false);

        $config = Config::get('laratrust_seeder.roles_structure');

        if ($config === null) {
            $this->command->error('The configuration has not been published. Did you run `php artisan vendor:publish --tag="laratrust-seeder"`?');
            $this->command->line('');

            return false;
        }

        $mapPermission = collect(config('laratrust_seeder.permissions_map'));

        foreach ($config as $roleName => $modules) {
            $role = \App\Models\Role::firstOrCreate([
                'name' => $roleName,
            ]);

            $permissions = [];

            $this->command->info('Processing Role: ' . strtoupper($roleName));

            foreach ($modules as $module => $value) {
                foreach ($value as $list => $list_per) {
                    foreach (explode(',', $list_per) as $perm) {
                        $permissionValue = $mapPermission->get($perm);

                        $permission = \App\Models\Permission::firstOrCreate([
                            'name' => $list . '-' . $permissionValue,
                        ], [
                            'display_name' => ucfirst($permissionValue) . ' ' . ucfirst($list),
                            'description' => ucfirst($permissionValue) . ' ' . ucfirst($list),
                        ]);

                        $permissions[] = $permission->id;
                    }
                }
            }

            if (!empty($permissions)) {
                $role->permissions()->syncWithoutDetaching($permissions);
            }
        }

        $adminUser = Admin::where('id', 1)->orWhere('admin_type', AdminTypeisEnum::Developer->value)->get();
        $allPermissions = Permission::pluck('id')->toArray();

        foreach ($adminUser as $admin) {
            $admin->permissions()->syncWithoutDetaching($allPermissions);
        }

        if (!DB::table('role_user')->where('user_id', 1)->where('role_id', 1)->exists()) {
            DB::table('role_user')->insert([
                [
                    'role_id' => 1,
                    'user_id' => 1,
                    'user_type' => 'App\Models\Nami\Admin',
                ],
            ]);
        }
    }

    public function truncateLaratrustTables($truncatePermissions = true): void
    {
        $this->command->info('Truncating Role and Permission tables (excluding user assignments)');
        Schema::disableForeignKeyConstraints();

        if (Config::get('laratrust_seeder.truncate_tables')) {
            DB::table('roles')->truncate();
            DB::table('permission_role')->truncate();
            DB::table('role_user')->truncate();

            if ($truncatePermissions) {
                DB::table('permissions')->truncate();
            }

            if (Config::get('laratrust_seeder.create_users')) {
                $usersTable = (new \App\Models\User())->getTable();
                DB::table($usersTable)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
