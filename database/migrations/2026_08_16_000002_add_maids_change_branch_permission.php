<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $permissionName = 'maids_change_branch';

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $payload = ['updated_at' => $now];
        foreach ([
            'group_name' => 'maids_rental',
            'section_name' => 'cv_management',
            'display_name' => 'تغيير فرع عاملة الإيجار',
            'description' => 'السماح بنقل سيرة عاملة الإيجار من فرع إلى فرع آخر.',
            'is_active' => true,
        ] as $column => $value) {
            if (Schema::hasColumn('permissions', $column)) {
                $payload[$column] = $value;
            }
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => $this->permissionName, 'guard_name' => 'admin'],
            $payload + ['created_at' => $now]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', 'admin')
            ->value('id');

        if ($permissionId) {
            foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) {
                    DB::table($table)->where('permission_id', $permissionId)->delete();
                }
            }
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
