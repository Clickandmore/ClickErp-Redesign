<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedBranchPermissions extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            [PermKeys::BRANCHES_VIEW, 'عرض الفروع'],
            [PermKeys::BRANCHES_CREATE, 'إضافة فرع'],
            [PermKeys::BRANCHES_EDIT, 'تعديل الفروع'],
            [PermKeys::BRANCHES_DELETE, 'حذف الفروع'],
            [PermKeys::BRANCHES_CONTROL, 'إدارة الفروع'],
        ];

        $hasDisplayName = Schema::hasColumn('permissions', 'display_name');
        $hasGroupName = Schema::hasColumn('permissions', 'group_name');
        $hasSectionName = Schema::hasColumn('permissions', 'section_name');
        $hasIsActive = Schema::hasColumn('permissions', 'is_active');

        foreach ($permissions as [$name, $displayName]) {
            $payload = [
                'guard_name' => 'admin',
                'updated_at' => now(),
                'created_at' => now(),
            ];

            if ($hasDisplayName) {
                $payload['display_name'] = $displayName;
            }

            if ($hasGroupName) {
                $payload['group_name'] = 'branches';
            }

            if ($hasSectionName) {
                $payload['section_name'] = 'system';
            }

            if ($hasIsActive) {
                $payload['is_active'] = true;
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'admin'],
                $payload
            );
        }
    }

    public function down()
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->whereIn('name', [
                PermKeys::BRANCHES_VIEW,
                PermKeys::BRANCHES_CREATE,
                PermKeys::BRANCHES_EDIT,
                PermKeys::BRANCHES_DELETE,
                PermKeys::BRANCHES_CONTROL,
            ])
            ->delete();
    }
}
