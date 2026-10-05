<?php

use App\NewPermKeys;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Permission::query()->updateOrCreate(
            ['name' => NewPermKeys::REC_CONTRACTS_LATE_FINANCE, 'guard_name' => 'admin'],
            ['display_name' => 'استكمال العمليات المالية لعقد الاستقدام بعد الوصول', 'is_active' => true]
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', NewPermKeys::REC_CONTRACTS_LATE_FINANCE)->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
