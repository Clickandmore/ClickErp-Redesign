<?php

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate([
            'name' => PermKeys::RENT_CONTRACT_SEND_OVERDUE_SMS,
            'guard_name' => 'admin',
        ], [
            'display_name' => 'إرسال رسالة للعميل بعد انتهاء عقد التأجير',
            'group_name' => 'rent_contract',
            'section_name' => 'rent_contracts',
            'is_active' => true,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve existing permission assignments.
    }
};
