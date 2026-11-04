<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** صلاحيات ميزان مراجعة العملاء: العرض، والتصدير (حصرية مثل بقية صلاحيات تصدير المالية). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        $hasDisplayName = Schema::hasColumn('permissions', 'display_name');
        $hasIsActive = Schema::hasColumn('permissions', 'is_active');

        foreach ($this->permissions() as $name => $displayName) {
            $payload = ['group_name' => 'finance', 'section_name' => 'modules'];
            if ($hasDisplayName) {
                $payload['display_name'] = $displayName;
            }
            if ($hasIsActive) {
                $payload['is_active'] = true;
            }
            DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'admin'], $payload);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('guard_name', 'admin')->whereIn('name', array_keys($this->permissions()))->delete();
        }
    }

    private function permissions(): array
    {
        return [
            'finance_client_trial_balance_view' => 'عرض ميزان مراجعة العملاء',
            'finance_client_trial_balance_export' => 'تصدير ميزان مراجعة العملاء',
        ];
    }
};
