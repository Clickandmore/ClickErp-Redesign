<?php

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        $permissions = [
            [PermKeys::REC_CONTRACTS_ARCHIVE_VIEW, 'عرض أرشيف عقود الاستقدام', 'فتح شاشة العقود المؤرشفة لعقود الاستقدام والبحث فيها.', 'rec_contracts', 'recruitment_contracts'],
            [PermKeys::REC_CONTRACTS_ARCHIVE, 'أرشفة واسترجاع عقود الاستقدام', 'أرشفة عقد استقدام منتهٍ أو أكثر، واسترجاع العقود المؤرشفة.', 'rec_contracts', 'recruitment_contracts'],
            [PermKeys::RENT_CONTRACT_ARCHIVE_VIEW, 'عرض أرشيف عقود الإيجار', 'فتح شاشة العقود المؤرشفة لعقود الإيجار والبحث فيها.', 'rent_contract', 'rent_contracts'],
            [PermKeys::RENT_CONTRACT_ARCHIVE, 'أرشفة واسترجاع عقود الإيجار', 'أرشفة عقد إيجار منتهٍ أو أكثر، واسترجاع العقود المؤرشفة.', 'rent_contract', 'rent_contracts'],
        ];

        foreach ($permissions as [$name, $displayName, $description, $group, $section]) {
            Permission::withoutGlobalScopes()->updateOrCreate([
                'name' => $name, 'guard_name' => 'admin',
            ], [
                'display_name' => $displayName,
                'description' => $description,
                'group_name' => $group, 'section_name' => $section, 'is_active' => true,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep existing role assignments intact on rollback.
    }
};
