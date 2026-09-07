<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\auth\Admin;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class AssignComplaintPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:assign-complaints {--admin=all : Admin ID or "all" for all admins}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign complaint permissions to admins';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $adminOption = $this->option('admin');

        // قائمة الصلاحيات المطلوبة
        $permissions = [
            'complaint_view',
            'complaint_create',
            'complaint_edit',
            'complaint_delete',
            'complaint_response',
            'complaint_type_view',
            'complaint_type_create',
            'complaint_type_edit',
            'complaint_type_delete',
        ];

        // التحقق من وجود الصلاحيات
        $existingPermissions = Permission::where('guard_name', 'admin')
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->toArray();

        if (empty($existingPermissions)) {
            $this->error('لا توجد صلاحيات شكاوى في قاعدة البيانات!');
            $this->info('قم بتشغيل: php artisan db:seed --class=AddComplaintPermissionsSeeder');
            return 1;
        }

        // إعطاء الصلاحيات
        if ($adminOption === 'all') {
            // إعطاء الصلاحيات لجميع المدراء
            $admins = Admin::all();

            foreach ($admins as $admin) {
                foreach ($existingPermissions as $permission) {
                    if (!$admin->hasPermissionTo($permission)) {
                        $admin->givePermissionTo($permission);
                    }
                }
                $this->info("تم منح الصلاحيات للمدير: {$admin->name} (ID: {$admin->id})");
            }

            $this->info('تم منح صلاحيات الشكاوى لجميع المدراء بنجاح!');
        } else {
            // إعطاء الصلاحيات لمدير محدد
            $admin = Admin::find($adminOption);

            if (!$admin) {
                $this->error("لم يتم العثور على المدير برقم: {$adminOption}");
                return 1;
            }

            foreach ($existingPermissions as $permission) {
                if (!$admin->hasPermissionTo($permission)) {
                    $admin->givePermissionTo($permission);
                }
            }

            $this->info("تم منح صلاحيات الشكاوى للمدير: {$admin->name}");
        }

        // إعطاء الصلاحيات لدور admin
        $adminRole = Role::where('name', 'admin')
            ->where('guard_name', 'admin')
            ->first();

        if ($adminRole) {
            foreach ($existingPermissions as $permission) {
                if (!$adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                }
            }
            $this->info('تم منح الصلاحيات لدور المدير (admin role)');
        }

        // عرض ملخص الصلاحيات
        $this->table(
            ['الصلاحية', 'الوصف'],
            [
                ['complaint_view', 'عرض الشكاوى'],
                ['complaint_create', 'إضافة شكوى جديدة'],
                ['complaint_edit', 'تعديل الشكاوى'],
                ['complaint_delete', 'حذف الشكاوى'],
                ['complaint_response', 'الرد على الشكاوى'],
                ['complaint_type_view', 'عرض أنواع الشكاوى'],
                ['complaint_type_create', 'إضافة نوع شكوى'],
                ['complaint_type_edit', 'تعديل أنواع الشكاوى'],
                ['complaint_type_delete', 'حذف أنواع الشكاوى'],
            ]
        );

        return 0;
    }
}
