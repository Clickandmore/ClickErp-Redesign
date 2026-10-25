<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * صلاحيات حذف/نقل/دمج حسابات الدليل المحاسبي. حصرية: لا تكفي صلاحية finance_module لها.
 */
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
                'group_name' => 'finance_account_restructure',
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
            ['name' => 'finance_accounting_guide_management_delete', 'display_name' => 'حذف حساب من الدليل المحاسبي', 'description' => 'حذف حساب لا توجد عليه قيود ولا يرتبط بأي شاشة.'],
            ['name' => 'finance_accounting_guide_management_move', 'display_name' => 'نقل حساب في الدليل المحاسبي', 'description' => 'نقل حساب (مع فروعه) تحت حساب رئيسي آخر وإعادة ترقيمه.'],
            ['name' => 'finance_accounting_guide_management_merge', 'display_name' => 'دمج حسابين في الدليل المحاسبي', 'description' => 'نقل كل قيود وارتباطات حساب إلى حساب آخر ثم حذفه.'],
        ];
    }
};
