<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        $attributes = [
            'group_name' => 'sponsor_transfer', 'section_name' => 'additional_services',
            'display_name' => 'إنشاء نقل خدمات لعاملات الفروع المتاحة', 'is_active' => true,
        ];
        $attributes = array_filter($attributes, fn ($column) => Schema::hasColumn('permissions', $column), ARRAY_FILTER_USE_KEY);
        DB::table('permissions')->updateOrInsert([
            'name' => PermKeys::SPONSOR_TRANSFER_CROSS_BRANCH, 'guard_name' => 'admin',
        ], $attributes);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve operational permission assignments.
    }
};
