<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            [
                'name' => 'financial_requests_request_closing',
                'display_name' => 'طلب إقفال الطلب المالي',
                'description' => 'نقل الطلب المالي المسدد إلى مرحلة طلب الإقفال.',
            ],
            [
                'name' => 'financial_requests_close',
                'display_name' => 'إغلاق الطلب المالي',
                'description' => 'إقفال الطلب المالي وإغلاقه نهائيًا دون اشتراط إرفاق إيصال رسمي.',
            ],
        ];

        foreach ($permissions as $permission) {
            $payload = [
                'group_name' => 'financial_requests',
                'section_name' => 'operations',
            ];

            foreach (['display_name', 'description'] as $column) {
                if (Schema::hasColumn('permissions', $column)) {
                    $payload[$column] = $permission[$column];
                }
            }

            if (Schema::hasColumn('permissions', 'is_active')) {
                $payload['is_active'] = true;
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name'], 'guard_name' => 'admin'],
                $payload
            );
        }

        $administratorRoleId = Schema::hasTable('roles')
            ? DB::table('roles')->where('guard_name', 'admin')->where('name', 'Administrator')->value('id')
            : null;

        if ($administratorRoleId && Schema::hasTable('role_has_permissions')) {
            $permissionIds = DB::table('permissions')
                ->where('guard_name', 'admin')
                ->whereIn('name', array_column($permissions, 'name'))
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $administratorRoleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', ['financial_requests_request_closing', 'financial_requests_close'])
            ->delete();
    }
};
