<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $attributes = [
            'group_name' => 'sponsor_transfer',
            'section_name' => 'organization_management',
            'display_name' => 'إضافة دفعة لطلب نقل الخدمات',
            'is_active' => true,
        ];
        $attributes = array_filter($attributes, fn ($column) => Schema::hasColumn('permissions', $column), ARRAY_FILTER_USE_KEY);
        DB::table('permissions')->updateOrInsert(
            ['name' => 'sponsor_transfer_add_payment', 'guard_name' => 'admin'], $attributes
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'sponsor_transfer_add_payment')->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
