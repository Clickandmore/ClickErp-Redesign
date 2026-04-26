<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\PermKeys;

class MissingPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إضافة الصلاحيات المفقودة للقائمة الجانبية
        $this->createMissingPermissions();
    }

    /**
     * إنشاء الصلاحيات المفقودة
     */
    private function createMissingPermissions(): void
    {
        $permissions = [
            // صلاحيات إدارة الشكاوى
            [
                'name' => PermKeys::COMPLAINT_TYPE_VIEW,
                'guard_name' => 'admin'
            ],
            [
                'name' => PermKeys::COMPLAINT_TYPE_CREATE,
                'guard_name' => 'admin'
            ],
            [
                'name' => PermKeys::COMPLAINT_TYPE_EDIT,
                'guard_name' => 'admin'
            ],
            [
                'name' => PermKeys::COMPLAINT_TYPE_DELETE,
                'guard_name' => 'admin'
            ],

            // صلاحيات مكاتب التوظيف
            [
                'name' => PermKeys::RECRUITMENT_OFFICES_VIEW,
                'guard_name' => 'admin'
            ],
            [
                'name' => PermKeys::RECRUITMENT_OFFICES_CREATE,
                'guard_name' => 'admin'
            ],
            [
                'name' => PermKeys::RECRUITMENT_OFFICES_EDIT,
                'guard_name' => 'admin'
            ],
            [
                'name' => PermKeys::RECRUITMENT_OFFICES_DELETE,
                'guard_name' => 'admin'
            ],

            // صلاحيات الوكلاء الخارجيين المستخدمة فعلياً في القائمة الجانبية ومسار admin/offices
            [
                'name' => PermKeys::OFFICE_VIEW,
                'guard_name' => 'admin',
                'group_name' => 'external_agents',
                'section_name' => 'maids_settings',
                'display_name' => 'عرض الوكلاء الخارجيين',
                'description' => 'إمكانية عرض قائمة الوكلاء الخارجيين.',
            ],
            [
                'name' => PermKeys::OFFICE_CREATE,
                'guard_name' => 'admin',
                'group_name' => 'external_agents',
                'section_name' => 'maids_settings',
                'display_name' => 'إضافة وكيل خارجي',
                'description' => 'إمكانية إضافة وكيل خارجي جديد.',
            ],
            [
                'name' => PermKeys::OFFICE_EDIT,
                'guard_name' => 'admin',
                'group_name' => 'external_agents',
                'section_name' => 'maids_settings',
                'display_name' => 'تعديل الوكلاء الخارجيين',
                'description' => 'إمكانية تعديل بيانات الوكلاء الخارجيين.',
            ],
            [
                'name' => PermKeys::OFFICE_DELETE,
                'guard_name' => 'admin',
                'group_name' => 'external_agents',
                'section_name' => 'maids_settings',
                'display_name' => 'حذف الوكلاء الخارجيين',
                'description' => 'إمكانية حذف سجلات الوكلاء الخارجيين.',
            ],
            [
                'name' => PermKeys::OFFICE_CONTROL,
                'guard_name' => 'admin',
                'group_name' => 'external_agents',
                'section_name' => 'maids_settings',
                'display_name' => 'إدارة الوكلاء الخارجيين',
                'description' => 'إمكانية إدارة جميع عمليات الوكلاء الخارجيين.',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => $permission['guard_name']
            ], array_diff_key($permission, array_flip(['name', 'guard_name'])));
        }

        $this->command->info('تم إضافة الصلاحيات المفقودة بنجاح!');
    }
}
