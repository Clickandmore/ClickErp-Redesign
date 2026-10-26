<?php

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/** صلاحيتا تقارير مدفوعات ميسر (عرض / تصدير)، وتُمنحان للأدوار التي تملك إعدادات ميسر. */
return new class extends Migration {
    private const PERMISSIONS = [
        PermKeys::PAYMENTS_MOYASAR_VIEW => [
            'display_name' => 'عرض تقارير مدفوعات ميسر',
            'description' => 'إحصائيات وتفاصيل روابط ومدفوعات ميسر لعقود التأجير ونقل الخدمات وطلبات الساعات والتطبيق.',
        ],
        PermKeys::PAYMENTS_MOYASAR_EXPORT => [
            'display_name' => 'تصدير تقارير مدفوعات ميسر',
            'description' => 'تنزيل تقرير مدفوعات ميسر كملف إكسل أو PDF بنفس الفترة والفلاتر.',
        ],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) return;

        $ids = [];
        foreach (self::PERMISSIONS as $name => $meta) {
            $ids[] = Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'admin'],
                ['group_name' => 'moyasar_reports', 'section_name' => 'finance'] + $meta
            )->id;
        }

        $settings = Permission::withoutGlobalScopes()->where('name', PermKeys::MOYASAR_SETTINGS)->where('guard_name', 'admin')->value('id');
        if ($settings && Schema::hasTable('role_has_permissions')) {
            $roles = DB::table('role_has_permissions')->where('permission_id', $settings)->pluck('role_id');
            foreach ($roles as $roleId) {
                foreach ($ids as $permissionId) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) return;
        Permission::withoutGlobalScopes()->where('guard_name', 'admin')->whereIn('name', array_keys(self::PERMISSIONS))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
