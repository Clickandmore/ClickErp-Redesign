<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $permissionName = 'maids_bulk_visibility';

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $attributes = ['updated_at' => $now];

        foreach ([
            'group_name' => 'maids',
            'section_name' => 'cv_management',
            'display_name' => 'إظهار وإخفاء جميع السير',
            'description' => 'إمكانية إظهار أو إخفاء جميع السير الذاتية دفعة واحدة',
            'is_active' => true,
        ] as $column => $value) {
            if (Schema::hasColumn('permissions', $column)) {
                $attributes[$column] = $value;
            }
        }

        $query = DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', 'admin');

        if ($query->exists()) {
            $query->update($attributes);
        } else {
            DB::table('permissions')->insert(array_merge($attributes, [
                'name' => $this->permissionName,
                'guard_name' => 'admin',
                'created_at' => $now,
            ]));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', 'admin')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) {
                DB::table($table)->where('permission_id', $permissionId)->delete();
            }
        }

        DB::table('permissions')->where('id', $permissionId)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
