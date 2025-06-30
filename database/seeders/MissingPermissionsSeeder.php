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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => $permission['guard_name']
            ]);
        }

        $this->command->info('تم إضافة الصلاحيات المفقودة بنجاح!');
    }
}