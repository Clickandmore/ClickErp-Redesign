<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class TravelPermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'travel_view' => [
                'display_name' => 'عرض رحلات الاستقدام والترحيل',
                'description' => 'إمكانية عرض صفحات وقوائم رحلات الاستقدام والترحيل ضمن الخدمات اللوجستية.',
            ],
            'travel_create' => [
                'display_name' => 'جدولة رحلة استقدام أو ترحيل',
                'description' => 'إمكانية إنشاء وجدولة رحلات الاستقدام والترحيل ضمن الخدمات اللوجستية.',
            ],
            'travel_edit' => [
                'display_name' => 'تعديل رحلات الاستقدام والترحيل',
                'description' => 'إمكانية تعديل بيانات الرحلات اللوجستية قبل اكتمالها.',
            ],
            'travel_delete' => [
                'display_name' => 'حذف رحلات الاستقدام والترحيل',
                'description' => 'إمكانية حذف الرحلات اللوجستية غير المكتملة ومرفقاتها.',
            ],
            'travel_control' => [
                'display_name' => 'إدارة رحلات الاستقدام والترحيل',
                'description' => 'إمكانية إسناد الرحلات، تأكيد اكتمالها، وإدارة الإجراءات التشغيلية لرحلات الخدمات اللوجستية.',
            ],
        ];

        foreach ($permissions as $name => $meta) {
            Permission::updateOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'admin',
                ],
                [
                    'display_name' => $meta['display_name'],
                    'description' => $meta['description'],
                    'group_name' => 'travel',
                    'section_name' => 'travel_management',
                ]
            );
        }

        $this->command->info('Travel permissions created successfully.');
    }
}
