<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $payload = [
            'group_name' => 'shelter_contract',
            'section_name' => 'shelter_management',
        ];

        if (Schema::hasColumn('permissions', 'display_name')) {
            $payload['display_name'] = 'إتاحة عاملات الإيجار للاستلام الفوري';
        }

        if (Schema::hasColumn('permissions', 'description')) {
            $payload['description'] = 'السماح بإتاحة أو إلغاء إتاحة عاملة الإيجار للاستلام الفوري وظهورها في نقل الخدمات.';
        }

        if (Schema::hasColumn('permissions', 'is_active')) {
            $payload['is_active'] = true;
        }

        DB::table('permissions')->updateOrInsert(
            [
                'name' => PermKeys::SHELTER_CONTRACT_RENTAL_INSTANT_RECEIPT_EDIT,
                'guard_name' => 'admin',
            ],
            $payload
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('name', PermKeys::SHELTER_CONTRACT_RENTAL_INSTANT_RECEIPT_EDIT)
            ->where('guard_name', 'admin')
            ->delete();
    }
};
