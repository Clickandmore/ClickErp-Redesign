<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $permissionName = 'settings_cv_options';

    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (!Schema::hasColumn('settings', 'rental_cv_transfer_enabled')) {
                    $table->boolean('rental_cv_transfer_enabled')
                        ->default(false)
                        ->comment('إتاحة سيرة عاملة الإيجار لنقل الخدمات على الموقع الخارجي');
                }
            });
        }

        $this->upsertPermission();
    }

    public function down(): void
    {
        $this->deletePermission();

        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'rental_cv_transfer_enabled')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('rental_cv_transfer_enabled');
            });
        }
    }

    private function upsertPermission(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $attributes = ['updated_at' => $now];

        foreach ([
            'group_name' => 'settings',
            'section_name' => 'settings',
            'display_name' => 'إعدادات السير الذاتية',
            'description' => 'التحكم في إتاحة سير عاملات الإيجار لنقل الخدمات على الموقع الخارجي',
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

    private function deletePermission(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', $this->permissionName)
            ->where('guard_name', 'admin')
            ->value('id');

        if (!$permissionId) {
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
