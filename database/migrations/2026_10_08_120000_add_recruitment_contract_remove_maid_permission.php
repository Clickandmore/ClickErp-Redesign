<?php

use App\NewPermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $attributes = ['guard_name' => 'admin'];

        foreach ([
            'group_name' => 'rec_contracts',
            'section_name' => 'recruitment_contracts',
            'display_name' => 'حذف العاملة من عقد الاستقدام',
            'description' => 'فصل العاملة عن عقد الاستقدام وإعادتها متاحة أو جعلها باك أوت وتحويل العقد إلى بانتظار اختيار العاملة.',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ] as $column => $value) {
            if (Schema::hasColumn('permissions', $column)) {
                $attributes[$column] = $value;
            }
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => NewPermKeys::REC_CONTRACTS_REMOVE_MAID, 'guard_name' => 'admin'],
            $attributes
        );

        // تُنشأ الصلاحية فقط، ولا تُسند لأي دور إلا يدوياً من شاشة الصلاحيات.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', NewPermKeys::REC_CONTRACTS_REMOVE_MAID)
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
