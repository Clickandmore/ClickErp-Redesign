<?php

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Permission::updateOrCreate(['name' => PermKeys::REC_CONTRACTS_DEPORTATION, 'guard_name' => 'admin'], [
            'display_name' => 'ترحيل العاملة من عقود الاستقدام',
            'description' => 'بدء ومتابعة إجراءات ترحيل العاملة من عقود الاستقدام دون إدخالها للإيواء.',
            'group_name' => 'recruitment_contracts',
            'section_name' => 'operations_management',
            'is_active' => true,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve permission assignments.
    }
};
