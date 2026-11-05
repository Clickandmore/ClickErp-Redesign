<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }
        $payload = ['group_name' => 'finance', 'section_name' => 'modules'];
        if (Schema::hasColumn('permissions', 'display_name')) {
            $payload['display_name'] = 'عرض مستحقات العملاء وعقود الإيجار';
        }
        if (Schema::hasColumn('permissions', 'is_active')) {
            $payload['is_active'] = true;
        }
        DB::table('permissions')->updateOrInsert(['name' => 'finance_client_receivables_view', 'guard_name' => 'admin'], $payload);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('name', 'finance_client_receivables_view')->where('guard_name', 'admin')->delete();
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
