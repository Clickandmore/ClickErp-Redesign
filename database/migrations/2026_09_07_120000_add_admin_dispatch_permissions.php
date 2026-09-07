<?php

use App\Support\AdminDispatchPermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        foreach (AdminDispatchPermissions::definitions() as $name => $label) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin'], ['display_name' => $label]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve assignments and audit history when rolling back application code.
    }
};
