<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) return;
        $columns = Schema::getColumnListing('permissions');
        $payload = ['display_name' => 'تعديل مسودة استرداد بعد الوصول', 'updated_at' => now()];
        foreach (['guard_name' => 'admin', 'group_name' => 'post_arrival_refund', 'section_name' => 'finance_management', 'is_active' => true] as $column => $value) {
            if (in_array($column, $columns, true)) $payload[$column] = $value;
        }
        if (! DB::table('permissions')->where('name', 'post_arrival_refund_edit')->exists()) $payload['created_at'] = now();
        DB::table('permissions')->updateOrInsert(['name' => 'post_arrival_refund_edit'], $payload);
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) DB::table('permissions')->where('name', 'post_arrival_refund_edit')->delete();
    }
};
