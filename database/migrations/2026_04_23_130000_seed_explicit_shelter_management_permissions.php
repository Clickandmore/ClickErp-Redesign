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
            ->whereIn('name', [
                'shelter_contract_addition_create',
                'shelter_contract_inside_view',
                'shelter_contract_available_for_transfer_view',
                'shelter_contract_deportation_stage_view',
                'shelter_contract_deported_view',
                'shelter_contract_deportation_procedures_view',
                'shelter_contract_deportation_procedures_edit',
                'shelter_contract_work_desire_edit',
                'shelter_contract_quick_tags_edit',
                'shelter_contract_instant_receipt_edit',
                'shelter_contract_remove_from_shelter_edit',
            ])
            ->delete();
    }

    private function permissions(): array
    {
        return [
            [
                'name' => 'shelter_contract_view',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'عرض عقود الإيواء',
                'description' => 'إمكانية عرض شاشات وبيانات عقود الإيواء.',
            ],
            [
                'name' => 'shelter_contract_create',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'إضافة عقد إيواء',
                'description' => 'إمكانية إنشاء عقد جديد داخل الإيواء.',
            ],
            [
                'name' => 'shelter_contract_edit',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'تعديل عقود الإيواء',
                'description' => 'إمكانية تعديل بيانات عقود الإيواء الحالية.',
            ],
            [
                'name' => 'shelter_contract_delete',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'حذف عقود الإيواء',
                'description' => 'إمكانية حذف سجلات عقود الإيواء.',
            ],
            [
                'name' => 'shelter_contract_control',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'إدارة عقود الإيواء',
                'description' => 'إمكانية إدارة جميع عمليات الإيواء والتحكم بها.',
            ],
            [
                'name' => 'shelter_workers_view',
                'group' => 'shelter_workers',
                'section' => 'shelter_management',
                'display_name' => 'عرض عمال الإيواء',
                'description' => 'إمكانية عرض عمال الإيواء وبياناتهم.',
            ],
            [
                'name' => 'shelter_workers_create',
                'group' => 'shelter_workers',
                'section' => 'shelter_management',
                'display_name' => 'إضافة عامل إيواء',
                'description' => 'إمكانية إضافة عامل جديد إلى الإيواء.',
            ],
            [
                'name' => 'shelter_workers_edit',
                'group' => 'shelter_workers',
                'section' => 'shelter_management',
                'display_name' => 'تعديل عمال الإيواء',
                'description' => 'إمكانية تعديل بيانات عمال الإيواء.',
            ],
            [
                'name' => 'shelter_workers_delete',
                'group' => 'shelter_workers',
                'section' => 'shelter_management',
                'display_name' => 'حذف عمال الإيواء',
                'description' => 'إمكانية حذف سجلات عمال الإيواء.',
            ],
            [
                'name' => 'shelter_workers_control',
                'group' => 'shelter_workers',
                'section' => 'shelter_management',
                'display_name' => 'إدارة عمال الإيواء',
                'description' => 'إمكانية إدارة جميع عمليات عمال الإيواء.',
            ],
            [
                'name' => 'shelter_places_view',
                'group' => 'shelter_places',
                'section' => 'shelter_management',
                'display_name' => 'عرض مقرات الإيواء',
                'description' => 'إمكانية عرض مقرات الإيواء المسجلة.',
            ],
            [
                'name' => 'shelter_places_create',
                'group' => 'shelter_places',
                'section' => 'shelter_management',
                'display_name' => 'إضافة مقر إيواء',
                'description' => 'إمكانية إضافة مقر إيواء جديد.',
            ],
            [
                'name' => 'shelter_places_edit',
                'group' => 'shelter_places',
                'section' => 'shelter_management',
                'display_name' => 'تعديل مقرات الإيواء',
                'description' => 'إمكانية تعديل بيانات مقرات الإيواء.',
            ],
            [
                'name' => 'shelter_places_delete',
                'group' => 'shelter_places',
                'section' => 'shelter_management',
                'display_name' => 'حذف مقرات الإيواء',
                'description' => 'إمكانية حذف سجلات مقرات الإيواء.',
            ],
            [
                'name' => 'shelter_places_control',
                'group' => 'shelter_places',
                'section' => 'shelter_management',
                'display_name' => 'إدارة مقرات الإيواء',
                'description' => 'إمكانية إدارة مقرات الإيواء والتحكم بها.',
            ],
            [
                'name' => 'shelter_contract_addition_create',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'الوصول إلى شاشة إضافة الإيواء',
                'description' => 'إمكانية الدخول إلى شاشة إضافة عقد جديد داخل الإيواء.',
            ],
            [
                'name' => 'shelter_contract_inside_view',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'عرض داخل الإيواء',
                'description' => 'إمكانية عرض قائمة العقود الموجودة حالياً داخل الإيواء.',
            ],
            [
                'name' => 'shelter_contract_available_for_transfer_view',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'عرض المتاح للنقل',
                'description' => 'إمكانية عرض قائمة العقود المتاحة للنقل من داخل الإيواء.',
            ],
            [
                'name' => 'shelter_contract_deportation_stage_view',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'عرض مرحلة الترحيل',
                'description' => 'إمكانية عرض قائمة عقود مرحلة الترحيل.',
            ],
            [
                'name' => 'shelter_contract_deported_view',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'عرض تم الترحيل',
                'description' => 'إمكانية عرض قائمة العقود التي اكتمل ترحيلها.',
            ],
            [
                'name' => 'shelter_contract_deportation_procedures_view',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'عرض إجراءات الترحيل',
                'description' => 'إمكانية عرض تفاصيل وخطوات إجراءات الترحيل لكل عقد.',
            ],
            [
                'name' => 'shelter_contract_deportation_procedures_edit',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'إدارة إجراءات الترحيل',
                'description' => 'إمكانية بدء وتحديث واستكمال إجراءات الترحيل.',
            ],
            [
                'name' => 'shelter_contract_work_desire_edit',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'تغيير رغبة العمل',
                'description' => 'إمكانية تحديث رغبة العاملة في العمل من شاشات الإيواء.',
            ],
            [
                'name' => 'shelter_contract_quick_tags_edit',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'تعديل التصنيفات السريعة',
                'description' => 'إمكانية تعديل التصنيفات السريعة لعقود الإيواء.',
            ],
            [
                'name' => 'shelter_contract_instant_receipt_edit',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'تعديل الاستلام الفوري',
                'description' => 'إمكانية تغيير إتاحة العقد للاستلام الفوري.',
            ],
            [
                'name' => 'shelter_contract_remove_from_shelter_edit',
                'group' => 'shelter_contract',
                'section' => 'shelter_management',
                'display_name' => 'حذف من الإيواء',
                'description' => 'إمكانية إخراج العقد من الإيواء وإرجاعه إلى مرحلته السابقة.',
            ],
        ];
    }
};
