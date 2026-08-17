<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing('permissions'));
        foreach ($this->permissions() as $name => $meta) {
            $payload = array_intersect_key([
                'display_name' => $meta[0],
                'description' => $meta[1],
                'group_name' => 'invoice_credit_notes',
                'section_name' => 'recruitment_contracts',
                'is_active' => true,
            ], $columns);

            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'admin'],
                $payload
            );
        }

        if (!Schema::hasTable('roles') || !Schema::hasTable('role_has_permissions')) {
            return;
        }

        $roleIds = DB::table('roles')
            ->where('guard_name', 'admin')
            ->whereIn('name', ['Administrator', 'Super Admin'])
            ->pluck('id');
        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_keys($this->permissions()))
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_keys($this->permissions()))
            ->pluck('id');
        foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->whereIn('permission_id', $ids)->delete();
            }
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }

    private function permissions(): array
    {
        return [
            'invoice_credit_notes_view' => ['عرض إشعارات الدائن', 'عرض إشعارات الدائن المرتبطة بعقود الاستقدام أو تفاويض الإنجاز.'],
            'invoice_credit_notes_create' => ['إنشاء إشعارات الدائن', 'إنشاء إشعار دائن كلي أو جزئي وعكس قيده المالي.'],
            'invoice_credit_notes_print' => ['طباعة إشعارات الدائن', 'طباعة إشعار الدائن الضريبي وتنزيله.'],
            'invoice_credit_notes_control' => ['إدارة إشعارات الدائن', 'إدارة جميع عمليات إشعارات الدائن الضريبية.'],
        ];
    }
};
