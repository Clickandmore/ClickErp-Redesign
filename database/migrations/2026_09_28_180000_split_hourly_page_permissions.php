<?php

use App\Support\HourlyPermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) return;
        $legacy = require database_path('hourly_permission_legacy.php');
        $sources = [];
        foreach (config('hourly_permissions', []) as $route => $permissions) {
            foreach ($permissions as $permission) {
                $sources[$permission] = array_unique(array_merge($sources[$permission] ?? [], $legacy[$route] ?? []));
            }
        }
        $sources['hourly_visits_price_override'] = ['hourly_orders_price_override'];
        $sources['hourly_visits_collect'] = ['hourly_orders_collect'];
        $sources['hourly_visits_edit'] = ['hourly_orders_create'];
        $sources['hourly_orders_print'] = ['hourly_orders_view'];

        DB::transaction(function () use ($sources) {
            // Snapshot all grants before inserting anything. New grants must never
            // become a source for further expansion within this migration.
            $ids = DB::table('permissions')->where('guard_name', 'admin')->pluck('id', 'name');
            $grants = [];
            foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
                if (Schema::hasTable($table)) $grants[$table] = DB::table($table)->get()->groupBy('permission_id');
            }
            foreach ($sources as $name => $oldNames) {
                $page = HourlyPermissionCatalog::page($name);
                $label = HourlyPermissionCatalog::label($name);
                $values = ['name' => $name, 'guard_name' => 'admin'];
                foreach (['section_name' => 'hourly_rental', 'group_name' => $page,
                    'display_name' => $label, 'description' => $label,
                    'updated_at' => now()] as $column => $value) {
                    if (Schema::hasColumn('permissions', $column)) $values[$column] = $value;
                }
                DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'admin'], $values);
                $newId = DB::table('permissions')->where('name', $name)->where('guard_name', 'admin')->value('id');
                // Existing permission assignments retain their precise meaning.
                if ($ids->has($name)) continue;
                foreach ($grants as $table => $byPermission) {
                    foreach ($oldNames as $oldName) {
                        foreach ($byPermission->get($ids[$oldName] ?? 0, collect()) as $grant) {
                            $row = (array) $grant;
                            unset($row['id']);
                            $row['permission_id'] = $newId;
                            DB::table($table)->insertOrIgnore($row);
                        }
                    }
                }
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Permission grants may have been edited since deployment; preserve them.
    }
};
