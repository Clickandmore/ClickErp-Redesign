<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $permissionName = 'settings_cv_template';

    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (!Schema::hasColumn('settings', 'cv_template_enabled')) {
                    $table->boolean('cv_template_enabled')->default(false);
                }
                if (!Schema::hasColumn('settings', 'cv_template_blade')) {
                    $table->longText('cv_template_blade')->nullable();
                }
                if (!Schema::hasColumn('settings', 'cv_template_filename')) {
                    $table->string('cv_template_filename')->nullable();
                }
            });
        }

        $this->upsertPermission();
    }

    public function down(): void
    {
        $this->deletePermission();

        if (!Schema::hasTable('settings')) {
            return;
        }

        $columns = array_values(array_filter([
            'cv_template_enabled',
            'cv_template_blade',
            'cv_template_filename',
        ], fn (string $column) => Schema::hasColumn('settings', $column)));

        if ($columns !== []) {
            Schema::table('settings', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
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
            'display_name' => 'إعداد قالب السيرة الذاتية',
            'description' => 'رفع قالب Blade وتفعيل توليد ملفات PDF للسير الذاتية',
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
