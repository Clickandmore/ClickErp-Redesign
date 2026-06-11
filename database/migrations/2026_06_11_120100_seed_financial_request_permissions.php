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

        $hasDisplayName = Schema::hasColumn('permissions', 'display_name');
        $hasDescription = Schema::hasColumn('permissions', 'description');
        $hasIsActive = Schema::hasColumn('permissions', 'is_active');

        foreach ($this->permissions() as $permission) {
            $payload = [
                'group_name' => 'financial_requests',
                'section_name' => 'operations',
            ];

            if ($hasDisplayName) {
                $payload['display_name'] = $permission['display_name'];
            }

            if ($hasDescription) {
                $payload['description'] = $permission['description'];
            }

            if ($hasIsActive) {
                $payload['is_active'] = true;
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name'], 'guard_name' => 'admin'],
                $payload
            );
        }

        $this->assignToAdministrator();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_column($this->permissions(), 'name'))
            ->delete();
    }

    private function permissions(): array
    {
        return [
            ['name' => 'financial_requests_view', 'display_name' => 'عرض الطلبات المالية', 'description' => 'عرض قائمة الطلبات المالية والتفاصيل.'],
            ['name' => 'financial_requests_create', 'display_name' => 'إنشاء طلب مالي', 'description' => 'إنشاء طلب مالي جديد.'],
            ['name' => 'financial_requests_edit', 'display_name' => 'تعديل الطلبات المالية', 'description' => 'تعديل بيانات الطلبات المالية.'],
            ['name' => 'financial_requests_delete', 'display_name' => 'حذف الطلبات المالية', 'description' => 'حذف الطلبات المالية.'],
            ['name' => 'financial_requests_change_status', 'display_name' => 'تغيير حالة الطلب المالي', 'description' => 'تحديث حالة الطلب المالي.'],
            ['name' => 'financial_requests_escalate', 'display_name' => 'تصعيد الطلب المالي', 'description' => 'تغيير أولوية وتصعيد الطلب المالي.'],
            ['name' => 'financial_requests_approve', 'display_name' => 'اعتماد الطلب المالي', 'description' => 'اعتماد الطلب وإنشاء السند المالي عند السداد.'],
            ['name' => 'financial_requests_export', 'display_name' => 'تصدير الطلبات المالية', 'description' => 'تصدير وطباعة الطلبات المالية.'],
            ['name' => 'financial_requests_control', 'display_name' => 'إدارة الطلبات المالية', 'description' => 'صلاحية كاملة لإدارة الطلبات المالية.'],
        ];
    }

    private function assignToAdministrator(): void
    {
        if (
            ! Schema::hasTable('roles') ||
            ! Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $role = DB::table('roles')
            ->where('guard_name', 'admin')
            ->where('name', 'Administrator')
            ->first();

        if (! $role) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_column($this->permissions(), 'name'))
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $permissionId,
                'role_id' => $role->id,
            ]);
        }
    }
};
