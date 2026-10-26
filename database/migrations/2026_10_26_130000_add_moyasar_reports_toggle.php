<?php

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * مفتاح إظهار تقارير مدفوعات ميسر من لوحة Super Admin (معطّل افتراضياً)،
 * ونقل صلاحيتي التقرير إلى قسم المالية لأن الصفحة صارت ضمن موديول المالية.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('moyasar_settings') && !Schema::hasColumn('moyasar_settings', 'reports_enabled')) {
            Schema::table('moyasar_settings', fn (Blueprint $table) => $table->boolean('reports_enabled')->default(false));
        }

        if (Schema::hasTable('permissions')) {
            Permission::withoutGlobalScopes()->where('guard_name', 'admin')
                ->whereIn('name', [PermKeys::PAYMENTS_MOYASAR_VIEW, PermKeys::PAYMENTS_MOYASAR_EXPORT])
                ->update(['section_name' => 'finance']);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('moyasar_settings', 'reports_enabled')) {
            Schema::table('moyasar_settings', fn (Blueprint $table) => $table->dropColumn('reports_enabled'));
        }

        if (Schema::hasTable('permissions')) {
            Permission::withoutGlobalScopes()->where('guard_name', 'admin')
                ->whereIn('name', [PermKeys::PAYMENTS_MOYASAR_VIEW, PermKeys::PAYMENTS_MOYASAR_EXPORT])
                ->update(['section_name' => 'settings']);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
