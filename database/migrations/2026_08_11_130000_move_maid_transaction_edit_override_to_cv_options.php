<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $settingColumn = 'maid_transaction_edit_override_enabled';

    public function up(): void
    {
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', $this->settingColumn)) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean($this->settingColumn)
                    ->default(false)
                    ->comment('السماح بتجاوز قفل تعديل السيرة المرتبطة بمعاملات');
            });
        }

        $this->deleteLegacyPermission();
    }

    public function down(): void
    {
        $this->restoreLegacyPermission();

        if (Schema::hasTable('settings') && Schema::hasColumn('settings', $this->settingColumn)) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn($this->settingColumn);
            });
        }
    }

    private function deleteLegacyPermission(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', PermKeys::MAIDS_TRANSACTION_EDIT_OVERRIDE)
            ->where('guard_name', 'admin')
            ->value('id');

        if (!$permissionId) {
            return;
        }

        foreach ([
            'role_has_permissions',
            'model_has_permissions',
            'role_branch_permissions',
            'permission_role',
            'permission_user',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) {
                DB::table($table)->where('permission_id', $permissionId)->delete();
            }
        }

        DB::table('permissions')->where('id', $permissionId)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function restoreLegacyPermission(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $payload = ['updated_at' => $now];

        foreach ([
            'group_name' => 'maids',
            'section_name' => 'cv_management',
            'display_name' => 'تعديل السيرة المرتبطة بمعاملات',
            'description' => 'صلاحية إدارية لتجاوز قفل بيانات العاملة المرتبطة بفواتير أو قيود محاسبية أو إرساليات مكاتب خارجية.',
            'is_active' => true,
        ] as $column => $value) {
            if (Schema::hasColumn('permissions', $column)) {
                $payload[$column] = $value;
            }
        }

        DB::table('permissions')->updateOrInsert(
            [
                'name' => PermKeys::MAIDS_TRANSACTION_EDIT_OVERRIDE,
                'guard_name' => 'admin',
            ],
            array_merge($payload, ['created_at' => $now])
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
