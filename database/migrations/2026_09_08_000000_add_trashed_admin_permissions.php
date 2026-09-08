<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * صلاحيات صفحة الموظفين المحذوفين: العرض، الاستعادة، الحذف النهائي.
 * الأدوار التي كانت تملك users_delete (وهي التي كانت تستطيع الاستعادة والحذف النهائي من
 * فلتر المحذوفين القديم) تُمنح الصلاحيات الجديدة حتى لا تفقد قدرة كانت لديها.
 */
return new class extends Migration {
    private const DEFINITIONS = [
        PermKeys::USERS_TRASHED_VIEW => 'عرض الموظفين المحذوفين',
        PermKeys::USERS_RESTORE => 'استعادة موظف محذوف',
        PermKeys::USERS_FORCE_DELETE => 'الحذف النهائي للموظف',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $hasDisplayName = Schema::hasColumn('permissions', 'display_name');
        $created = [];

        foreach (self::DEFINITIONS as $name => $label) {
            $created[] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'admin'],
                $hasDisplayName ? ['display_name' => $label] : []
            );
        }

        if (Schema::hasTable('roles') && Schema::hasTable('role_has_permissions')) {
            $legacyDelete = Permission::where('name', PermKeys::USERS_DELETE)->where('guard_name', 'admin')->first();

            if ($legacyDelete) {
                Role::where('guard_name', 'admin')
                    ->whereHas('permissions', fn ($query) => $query->whereKey($legacyDelete->id))
                    ->get()
                    ->each(fn (Role $role) => $role->givePermissionTo($created));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // نحافظ على التعيينات وسجل التدقيق عند التراجع عن كود التطبيق.
    }
};
