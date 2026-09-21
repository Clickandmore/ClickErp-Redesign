<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $values = array_intersect_key([
            'display_name' => 'تعديل مبلغ الطلب المالي',
            'description' => 'تعديل مبلغ الطلب المالي بصلاحية مستقلة.',
            'group_name' => 'financial_requests',
            'section_name' => 'operations',
            'is_active' => true,
        ], array_flip(Schema::getColumnListing('permissions')));

        DB::table('permissions')->updateOrInsert([
            'name' => 'financial_requests_edit_amount',
            'guard_name' => 'admin',
        ], $values);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve role assignments.
    }
};
