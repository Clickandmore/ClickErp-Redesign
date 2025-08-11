<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\auth\Admin;

class AddComplaintPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء الصلاحيات الخاصة بالشكاوى
        $permissions = [
            // صلاحيات الشكاوى الأساسية
            [
                'name' => 'complaint_view',
                'guard_name' => 'admin',
                'group_name' => 'complaints',
                'section_name' => 'customer_service',
                'display_name' => 'عرض الشكاوى'
            ],
            [
                'name' => 'complaint_create',
                'guard_name' => 'admin',
                'group_name' => 'complaints',
                'section_name' => 'customer_service',
                'display_name' => 'إضافة شكوى'
            ],
            [
                'name' => 'complaint_edit',
                'guard_name' => 'admin',
                'group_name' => 'complaints',
                'section_name' => 'customer_service',
                'display_name' => 'تعديل الشكاوى'
            ],
            [
                'name' => 'complaint_delete',
                'guard_name' => 'admin',
                'group_name' => 'complaints',
                'section_name' => 'customer_service',
                'display_name' => 'حذف الشكاوى'
            ],
            [
                'name' => 'complaint_response',
                'guard_name' => 'admin',
                'group_name' => 'complaints',
                'section_name' => 'customer_service',
                'display_name' => 'الرد على الشكاوى'
            ],

            // صلاحيات أنواع الشكاوى
            [
                'name' => 'complaint_type_view',
                'guard_name' => 'admin',
                'group_name' => 'complaint_types',
                'section_name' => 'customer_service',
                'display_name' => 'عرض أنواع الشكاوى'
            ],
            [
                'name' => 'complaint_type_create',
                'guard_name' => 'admin',
                'group_name' => 'complaint_types',
                'section_name' => 'customer_service',
                'display_name' => 'إضافة نوع شكوى'
            ],
            [
                'name' => 'complaint_type_edit',
                'guard_name' => 'admin',
                'group_name' => 'complaint_types',
                'section_name' => 'customer_service',
                'display_name' => 'تعديل أنواع الشكاوى'
            ],
            [
                'name' => 'complaint_type_delete',
                'guard_name' => 'admin',
                'group_name' => 'complaint_types',
                'section_name' => 'customer_service',
                'display_name' => 'حذف أنواع الشكاوى'
            ],
        ];

        // إنشاء الصلاحيات
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name']
                ],
                [
                    'group_name' => $permission['group_name'] ?? null,
                    'section_name' => $permission['section_name'] ?? null,
                    'display_name' => $permission['display_name'] ?? null
                ]
            );
        }

        $this->command->info('تم إضافة صلاحيات الشكاوى بنجاح!');

        // إعطاء الصلاحيات للمدير الأول (Super Admin)
        $firstAdmin = Admin::first();
        if ($firstAdmin) {
            $allPermissions = Permission::where('guard_name', 'admin')
                ->whereIn('name', array_column($permissions, 'name'))
                ->pluck('name')
                ->toArray();

            foreach ($allPermissions as $permission) {
                if (!$firstAdmin->hasPermissionTo($permission)) {
                    $firstAdmin->givePermissionTo($permission);
                }
            }

            $this->command->info('تم منح الصلاحيات للمدير الأول!');
        }

        // إعطاء الصلاحيات لدور admin إذا كان موجود
        $adminRole = Role::where('name', 'admin')
            ->where('guard_name', 'admin')
            ->first();

        if ($adminRole) {
            $allPermissions = Permission::where('guard_name', 'admin')
                ->whereIn('name', array_column($permissions, 'name'))
                ->pluck('name')
                ->toArray();

            foreach ($allPermissions as $permission) {
                if (!$adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                }
            }

            $this->command->info('تم منح الصلاحيات لدور المدير!');
        }
    }
}
