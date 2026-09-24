<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $values = array_intersect_key([
            'group_name' => 'settings',
            'section_name' => 'settings',
            'display_name' => 'إدارة إعدادات الموقع الخارجي',
            'description' => 'عرض وتعديل إعدادات الموقع الخارجي وSEO دون إعدادات النظام الداخلية',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], array_flip(Schema::getColumnListing('permissions')));

        DB::table('permissions')->updateOrInsert([
            'name' => PermKeys::WEBSITE_SETTINGS_CONTROL,
            'guard_name' => 'admin',
        ], $values);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $ids = DB::table('permissions')->where('name', PermKeys::WEBSITE_SETTINGS_CONTROL)
            ->where('guard_name', 'admin')->pluck('id');
        foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) {
                DB::table($table)->whereIn('permission_id', $ids)->delete();
            }
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
