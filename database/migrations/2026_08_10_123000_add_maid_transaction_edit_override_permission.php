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
            'group_name' => 'maids',
            'section_name' => 'cv_management',
        ];

        if (Schema::hasColumn('permissions', 'display_name')) {
            $payload['display_name'] = 'تعديل السيرة المرتبطة بمعاملات';
        }

        if (Schema::hasColumn('permissions', 'description')) {
            $payload['description'] = 'صلاحية إدارية لتجاوز قفل بيانات العاملة المرتبطة بفواتير أو قيود محاسبية أو إرساليات مكاتب خارجية.';
        }

        if (Schema::hasColumn('permissions', 'is_active')) {
            $payload['is_active'] = true;
        }

        DB::table('permissions')->updateOrInsert(
            [
                'name' => PermKeys::MAIDS_TRANSACTION_EDIT_OVERRIDE,
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
            ->where('name', PermKeys::MAIDS_TRANSACTION_EDIT_OVERRIDE)
            ->where('guard_name', 'admin')
            ->delete();
    }
};
