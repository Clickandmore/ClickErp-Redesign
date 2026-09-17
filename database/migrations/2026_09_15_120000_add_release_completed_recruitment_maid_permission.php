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

        $attributes = ['guard_name' => 'admin'];
        foreach ([
            'display_name' => 'اكتمال العقد وتسفير العاملة وإتاحتها للاستقدام',
            'description' => 'إتاحة العاملة لعقد استقدام جديد بعد اكتمال مدة عقدها وعدم وجودها في الإيواء.',
            'group_name' => 'rec_contracts',
            'section_name' => 'recruitment_contracts',
            'is_active' => true,
            'updated_at' => now(),
            'created_at' => now(),
        ] as $column => $value) {
            if (Schema::hasColumn('permissions', $column)) {
                $attributes[$column] = $value;
            }
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => NewPermKeys::REC_CONTRACTS_RELEASE_MAID_AFTER_COMPLETION, 'guard_name' => 'admin'],
            $attributes
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', NewPermKeys::REC_CONTRACTS_RELEASE_MAID_AFTER_COMPLETION)
            ->where('guard_name', 'admin')->value('id');

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
