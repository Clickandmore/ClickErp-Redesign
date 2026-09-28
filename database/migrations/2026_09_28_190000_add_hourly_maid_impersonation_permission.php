<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) return;

        $key = ['name' => 'hourly_maids_impersonate', 'guard_name' => 'admin'];
        $values = [];
        foreach (['section_name' => 'hourly_rental', 'group_name' => 'hourly_maids',
            'display_name' => 'الدخول إلى بوابة العاملة نيابة عنها',
            'description' => 'الدخول إلى بوابة العاملة نيابة عنها', 'updated_at' => now()] as $column => $value) {
            if (Schema::hasColumn('permissions', $column)) $values[$column] = $value;
        }
        DB::table('permissions')->updateOrInsert($key, $values);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve permission assignments made after deployment.
    }
};
