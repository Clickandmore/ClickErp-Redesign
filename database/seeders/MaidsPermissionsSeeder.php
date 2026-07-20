<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class MaidsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // الصلاحيات الأساسية للسير الذاتية
        $permissions = [
            [
                'name' => 'maids_view',
                'display_name' => 'عرض السير الذاتية',
                'description' => 'إمكانية عرض قائمة السير الذاتية',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_create',
                'display_name' => 'إضافة سيرة ذاتية',
                'description' => 'إمكانية إضافة سيرة ذاتية جديدة',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_edit',
                'display_name' => 'تعديل السير الذاتية',
                'description' => 'إمكانية تعديل بيانات السير الذاتية',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_delete',
                'display_name' => 'حذف السير الذاتية',
                'description' => 'إمكانية حذف السير الذاتية',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_export',
                'display_name' => 'تصدير السير الذاتية',
                'description' => 'إمكانية تصدير بيانات السير الذاتية',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_export_pdf',
                'display_name' => 'تصدير السير الذاتية بصيغة PDF',
                'description' => 'إمكانية تصدير بيانات السير الذاتية بصيغة PDF',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_export_excel',
                'display_name' => 'تصدير السير الذاتية بصيغة Excel',
                'description' => 'إمكانية تصدير بيانات السير الذاتية بصيغة Excel',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_show',
                'display_name' => 'عرض تفاصيل السيرة الذاتية',
                'description' => 'إمكانية عرض تفاصيل السيرة الذاتية الكاملة',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_toggle_status',
                'display_name' => 'تغيير حالة الظهور',
                'description' => 'إمكانية إظهار أو إخفاء السيرة الذاتية',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_bulk_visibility',
                'display_name' => 'إظهار وإخفاء جميع السير',
                'description' => 'إمكانية إظهار أو إخفاء جميع السير الذاتية دفعة واحدة',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'maids_toggle_pin',
                'display_name' => 'تثبيت/إلغاء تثبيت',
                'description' => 'إمكانية تثبيت أو إلغاء تثبيت السيرة الذاتية',
                'guard_name' => 'admin'
            ],
        ];

        // إضافة الصلاحيات إلى قاعدة البيانات
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                [
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description']
                ]
            );
        }

        $this->command->info('تم إضافة صلاحيات السير الذاتية الأساسية بنجاح!');
    }
}
