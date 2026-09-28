<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        $permissions = DB::table('permissions')->where('guard_name', 'admin')->pluck('id', 'name');
        $target = $permissions['hourly_maids_impersonate'] ?? null;
        $driver = $permissions['hourly_drivers_impersonate'] ?? null;
        $view = $permissions['hourly_maids_view'] ?? null;
        if (!$target || !$driver || !$view) return;

        DB::transaction(function () use ($target, $driver, $view) {
            foreach (['role_has_permissions' => ['role_id'],
                'model_has_permissions' => ['model_type', 'model_id'],
                'role_branch_permissions' => ['role_id', 'branch_id']] as $table => $keys) {
                if (!Schema::hasTable($table)) continue;
                foreach (DB::table($table)->where('permission_id', $driver)->get() as $grant) {
                    $scope = array_intersect_key((array) $grant, array_flip($keys));
                    if (!DB::table($table)->where($scope)->where('permission_id', $view)->exists()) continue;
                    $row = (array) $grant;
                    unset($row['id']);
                    $row['permission_id'] = $target;
                    DB::table($table)->insertOrIgnore($row);
                }
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve grants that administrators may have changed since deployment.
    }
};
