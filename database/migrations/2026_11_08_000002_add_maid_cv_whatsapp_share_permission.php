<?php

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate([
            'name' => PermKeys::MAIDS_SHARE_WHATSAPP, 'guard_name' => 'admin',
        ], [
            'display_name' => 'مشاركة السير عبر واتساب',
            'description' => 'مشاركة السير المولدة مع العملاء عبر واتساب وعرض سجل المشاركات.',
            'group_name' => 'maids', 'section_name' => 'cv_management', 'is_active' => true,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep existing role assignments intact on rollback.
    }
};
