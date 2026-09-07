<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeFinanceProcurementPermissions extends Migration
{
    private array $names = [
        'finance_procurement_view',
        'finance_procurement_create',
        'finance_procurement_edit',
        'finance_procurement_approve',
        'finance_procurement_pay',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $payload = ['updated_at' => now()];

        if (Schema::hasColumn('permissions', 'group_name')) {
            $payload['group_name'] = 'finance_procurement';
        }

        if (Schema::hasColumn('permissions', 'section_name')) {
            $payload['section_name'] = 'finance';
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', $this->names)
            ->update($payload);

        if (Schema::hasColumn('permissions', 'created_at')) {
            DB::table('permissions')
                ->where('guard_name', 'admin')
                ->whereIn('name', $this->names)
                ->whereNull('created_at')
                ->update(['created_at' => now()]);
        }
    }

    public function down(): void
    {
        // تصحيح بيانات وصفي؛ لا نعيد التواريخ إلى NULL عند rollback.
    }
}
