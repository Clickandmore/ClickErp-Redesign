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
        $hasIsActive    = Schema::hasColumn('permissions', 'is_active');

        foreach ($this->permissions() as $permission) {
            $payload = [
                'group_name'   => 'commissions',
                'section_name' => 'commissions',
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
            [
                'name'         => 'commissions_view',
                'display_name' => 'عرض نظام العمولات',
                'description'  => 'عرض قائمة المسوّقين وطلباتهم وأرصدة العمولات.',
            ],
            [
                'name'         => 'commissions_calculate',
                'display_name' => 'احتساب العمولات',
                'description'  => 'تحديد الطلبات واحتساب العمولة واعتماد دفعات الاحتساب.',
            ],
            [
                'name'         => 'commissions_pay',
                'display_name' => 'سداد العمولات',
                'description'  => 'إصدار سندات صرف العمولات وخصمها من رصيد الموظف.',
            ],
            [
                'name'         => 'commissions_settings',
                'display_name' => 'إعدادات العمولات',
                'description'  => 'تعديل نسب ومبالغ العمولة لكل نوع خدمة واستثناءات الموظفين.',
            ],
        ];
    }

    private function assignToAdministrator(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_has_permissions')) {
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
                'role_id'       => $role->id,
            ]);
        }
    }
};
