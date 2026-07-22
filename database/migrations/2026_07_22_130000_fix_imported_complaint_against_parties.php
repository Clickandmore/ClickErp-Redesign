<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('complaints') || !Schema::hasTable('complaint_types')) {
            return;
        }

        DB::table('complaint_types')
            ->where('name', 'like', '%عميل%ضد%عامل%')
            ->update([
                'complainant_type' => 'client',
                'against_type' => 'worker',
                'updated_at' => now(),
            ]);

        if (!Schema::hasTable('orders') || !Schema::hasTable('maids')) {
            return;
        }

        DB::table('complaints as complaints')
            ->join('complaint_types as complaint_types', 'complaint_types.id', '=', 'complaints.complaint_type_id')
            ->join('orders as orders', 'orders.id', '=', 'complaints.order_id')
            ->join('maids as maids', 'maids.id', '=', 'orders.maid_id')
            ->where('complaints.relation_type', 'order')
            ->where('complaint_types.against_type', 'worker')
            ->where(function ($query) {
                $query->whereNull('complaints.against_type')
                    ->orWhere('complaints.against_type', '');
            })
            ->update([
                'complaints.against_type' => 'worker',
                'complaints.against_name' => DB::raw('maids.name'),
                'complaints.against_phone' => null,
                'complaints.maid_passport' => DB::raw('COALESCE(complaints.maid_passport, maids.passport_num)'),
                'complaints.maid_nationality_id' => DB::raw('COALESCE(complaints.maid_nationality_id, maids.nationality_id)'),
                'complaints.updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // تصحيح بيانات مرحّلة؛ لا يمكن استعادة القيم الخاطئة بأمان.
    }
};
