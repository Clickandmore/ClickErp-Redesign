<?php

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
            'group_name' => 'musaned_transfers',
            'section_name' => 'recruitment_contracts',
        ];

        if (Schema::hasColumn('permissions', 'display_name')) {
            $payload['display_name'] = 'حذف تحويل مساند';
        }

        if (Schema::hasColumn('permissions', 'description')) {
            $payload['description'] = 'إمكانية حذف تحويل مساند والقيود المحاسبية المرتبطة به.';
        }

        if (Schema::hasColumn('permissions', 'is_active')) {
            $payload['is_active'] = true;
        }

        DB::table('permissions')->updateOrInsert(
            ['name' => 'musaned_transfers_delete', 'guard_name' => 'admin'],
            $payload
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('name', 'musaned_transfers_delete')
            ->where('guard_name', 'admin')
            ->delete();
    }
};
