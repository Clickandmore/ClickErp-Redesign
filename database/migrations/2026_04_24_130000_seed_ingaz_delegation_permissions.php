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

        $hasDisplayName = Schema::hasColumn('permissions', 'display_name');
        $hasDescription = Schema::hasColumn('permissions', 'description');
        $hasIsActive = Schema::hasColumn('permissions', 'is_active');

        foreach ($this->permissions() as $permission) {
            $payload = [
                'group_name' => $permission['group'],
                'section_name' => $permission['section'],
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
                [
                    'name' => $permission['name'],
                    'guard_name' => 'admin',
                ],
                $payload
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
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
                'name' => 'ingaz_delegations_view',
                'group' => 'ingaz_delegations',
                'section' => 'recruitment_contracts',
                'display_name' => 'عرض تفاويض الإنجاز',
                'description' => 'إمكانية عرض قائمة تفاويض الإنجاز وفتح تفاصيلها.',
            ],
            [
                'name' => 'ingaz_delegations_create',
                'group' => 'ingaz_delegations',
                'section' => 'recruitment_contracts',
                'display_name' => 'إضافة تفويض إنجاز',
                'description' => 'إمكانية إنشاء تفويض إنجاز جديد وربطه بالعميل والعاملة.',
            ],
            [
                'name' => 'ingaz_delegations_edit',
                'group' => 'ingaz_delegations',
                'section' => 'recruitment_contracts',
                'display_name' => 'تعديل تفاويض الإنجاز',
                'description' => 'إمكانية تعديل بيانات تفاويض الإنجاز الحالية.',
            ],
            [
                'name' => 'ingaz_delegations_delete',
                'group' => 'ingaz_delegations',
                'section' => 'recruitment_contracts',
                'display_name' => 'حذف تفاويض الإنجاز',
                'description' => 'إمكانية حذف سجلات تفاويض الإنجاز ومرفقاتها.',
            ],
            [
                'name' => 'ingaz_delegations_print',
                'group' => 'ingaz_delegations',
                'section' => 'recruitment_contracts',
                'display_name' => 'طباعة تفويض الإنجاز',
                'description' => 'إمكانية طباعة نموذج تفويض الإنجاز.',
            ],
            [
                'name' => 'ingaz_delegations_change_status',
                'group' => 'ingaz_delegations',
                'section' => 'recruitment_contracts',
                'display_name' => 'تغيير حالة تفويض الإنجاز',
                'description' => 'إمكانية تحديث حالة تنفيذ تفويض الإنجاز من شاشة الإدارة.',
            ],
            [
                'name' => 'ingaz_delegations_control',
                'group' => 'ingaz_delegations',
                'section' => 'recruitment_contracts',
                'display_name' => 'إدارة تفاويض الإنجاز',
                'description' => 'إمكانية إدارة جميع عمليات تفاويض الإنجاز.',
            ],
        ];
    }
};
