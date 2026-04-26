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
                'group_name' => 'external_agents',
                'section_name' => 'maids_settings',
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
        // Keep seeded permissions to avoid breaking existing role assignments.
    }

    private function permissions(): array
    {
        return [
            [
                'name' => 'office_view',
                'display_name' => 'عرض الوكلاء الخارجيين',
                'description' => 'إمكانية عرض قائمة الوكلاء الخارجيين.',
            ],
            [
                'name' => 'office_create',
                'display_name' => 'إضافة وكيل خارجي',
                'description' => 'إمكانية إضافة وكيل خارجي جديد.',
            ],
            [
                'name' => 'office_edit',
                'display_name' => 'تعديل الوكلاء الخارجيين',
                'description' => 'إمكانية تعديل بيانات الوكلاء الخارجيين.',
            ],
            [
                'name' => 'office_delete',
                'display_name' => 'حذف الوكلاء الخارجيين',
                'description' => 'إمكانية حذف سجلات الوكلاء الخارجيين.',
            ],
            [
                'name' => 'office_control',
                'display_name' => 'إدارة الوكلاء الخارجيين',
                'description' => 'إمكانية إدارة جميع عمليات الوكلاء الخارجيين.',
            ],
        ];
    }
};
