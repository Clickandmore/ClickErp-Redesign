<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'shelter_agent_cost_recovery' => ['استرداد تكلفة الوكيل من الإيواء', 'إثبات مطالبة المكتب بتكلفته الأصلية فقط دون الإعاشة، مع بقاء العاملة في الإيواء.'],
            'shelter_agent_cost_recovery_reverse' => ['عكس استرداد تكلفة الوكيل', 'عكس المطالبة بقيد مقابل قبل وجود حركة خروج لاحقة للعاملة.'],
        ] as $name => [$label, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'admin'], [
                'display_name' => $label, 'description' => $description,
                'group_name' => 'shelter_contract', 'section_name' => 'shelter_management',
                'is_active' => true,
            ]);
        }
        // Register selectable permissions only; leave all user/role assignments untouched.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // These permissions predate this metadata update. Preserve them and their assignments.
    }
};
