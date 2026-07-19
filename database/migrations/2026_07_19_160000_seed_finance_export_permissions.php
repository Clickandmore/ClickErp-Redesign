<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $hasDisplayName = Schema::hasColumn('permissions', 'display_name');
        $hasDescription = Schema::hasColumn('permissions', 'description');
        $hasIsActive = Schema::hasColumn('permissions', 'is_active');

        foreach ($this->permissions() as $permission) {
            $payload = [
                'group_name' => 'finance_exports',
                'section_name' => 'finance',
            ];

            if ($hasDisplayName) {
                $payload['display_name'] = $permission['display_name'];
            }

            if ($hasDescription) {
                $payload['description'] = $permission['description'];
            }

            if ($hasIsActive) {
                $payload['is_active'] = true;
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name'], 'guard_name' => 'admin'],
                $payload
            );
        }

        $this->assignToAdministrator();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_column($this->permissions(), 'name'))
            ->delete();
    }

    private function permissions(): array
    {
        return [
            ['name' => 'finance_constraints_export', 'display_name' => 'تصدير القيود المحاسبية', 'description' => 'تصدير القيود المحاسبية بصيغ PDF وExcel.'],
            ['name' => 'finance_accounting_guide_management_export', 'display_name' => 'تصدير الدليل المحاسبي', 'description' => 'تصدير بيانات الدليل المحاسبي.'],
            ['name' => 'finance_bill_sales_export', 'display_name' => 'تصدير فواتير المبيعات', 'description' => 'تصدير فواتير المبيعات بصيغ PDF وExcel.'],
            ['name' => 'finance_account_statement_export', 'display_name' => 'تصدير كشف الحساب', 'description' => 'طباعة وتصدير كشف الحساب العام.'],
            ['name' => 'finance_account_statement_clients_export', 'display_name' => 'تصدير كشف حساب العملاء', 'description' => 'طباعة وتصدير كشوف حسابات العملاء.'],
            ['name' => 'finance_account_statement_suppliers_export', 'display_name' => 'تصدير كشف حساب الموردين', 'description' => 'طباعة وتصدير كشوف حسابات الموردين.'],
            ['name' => 'finance_bank_account_statement_export', 'display_name' => 'تصدير كشف الحساب البنكي', 'description' => 'طباعة وتصدير كشف الحساب البنكي.'],
            ['name' => 'finance_double_account_statement_export', 'display_name' => 'تصدير كشف الحساب المزدوج', 'description' => 'طباعة وتصدير كشف الحساب المزدوج.'],
            ['name' => 'finance_trail_balance_export', 'display_name' => 'تصدير ميزان المراجعة', 'description' => 'طباعة وتصدير ميزان المراجعة إلى Excel.'],
            ['name' => 'finance_bonds_export', 'display_name' => 'تصدير وطباعة السندات', 'description' => 'تصدير وطباعة سندات القبض والصرف.'],
        ];
    }

    private function assignToAdministrator(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $role = DB::table('roles')
            ->where('guard_name', 'admin')
            ->where('name', 'Administrator')
            ->first();

        if (! $role) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_column($this->permissions(), 'name'))
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $role->id,
            ]);
        }
    }
};
