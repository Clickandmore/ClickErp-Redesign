<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\NewPermKeys;

class CvPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createCvPermissions();
    }

    /**
     * إنشاء صلاحيات السير الذاتية
     */
    private function createCvPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::CV_VIEW,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_CREATE,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_EDIT,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_DELETE,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_PIN,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_UNPIN,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_APPROVE,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_REJECT,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_EXPORT,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_IMPORT,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_MANAGE,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_DOWNLOAD,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_UPLOAD,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_CONVERT,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_ANALYTICS,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CV_REPORTS,
                'group_name' => 'cv',
                'section_name' => 'cv_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        $this->command->info('تم إنشاء صلاحيات السير الذاتية بنجاح!');
    }
}
