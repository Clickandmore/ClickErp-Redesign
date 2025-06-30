<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Permissions\PermissionsController;

class TestCvPermissionsCommand extends Command
{
    protected $signature = 'test:cv-permissions';
    protected $description = 'اختبار صلاحيات السير الذاتية';

    public function handle()
    {
        $this->info('=== اختبار صلاحيات السير الذاتية ===');

        // التحقق من وجود الصلاحيات في قاعدة البيانات
        $permissions = Permission::where('name', 'like', 'cv_%')->get();
        $this->info('عدد صلاحيات السير الذاتية في قاعدة البيانات: ' . $permissions->count());

        // عرض تفاصيل الصلاحيات
        foreach ($permissions as $permission) {
            $this->line("- {$permission->name} (القسم: {$permission->section_name}, المجموعة: {$permission->group_name})");
        }

        // التحقق من تنظيم الصلاحيات
        $this->info('\n=== تنظيم الصلاحيات ===');
        $controller = new PermissionsController();

        // استخدام Reflection للوصول للدوال الخاصة
        $reflection = new \ReflectionClass($controller);

        // اختبار دالة organizePermissions
        $organizeMethod = $reflection->getMethod('organizePermissions');
        $organizeMethod->setAccessible(true);
        $organized = $organizeMethod->invoke($controller, $permissions);

        foreach ($organized as $section => $groups) {
            $this->info("القسم: {$section}");
            if (is_array($groups)) {
                foreach ($groups as $group => $groupPermissions) {
                    if (is_array($groupPermissions)) {
                        $this->line("  المجموعة: {$group} (" . count($groupPermissions) . " صلاحية)");
                        foreach ($groupPermissions as $perm) {
                            $this->line("    - {$perm->name}");
                        }
                    } else {
                        $this->line("  المجموعة: {$group} => {$groupPermissions}");
                    }
                }
            } else {
                $this->line("  القسم: {$section} => {$groups}");
            }
        }

        // اختبار أسماء الصلاحيات
        $this->info('\n=== أسماء الصلاحيات ===');
        $displayMethod = $reflection->getMethod('getPermissionDisplayName');
        $displayMethod->setAccessible(true);

        foreach ($permissions as $permission) {
            $displayName = $displayMethod->invoke($controller, $permission->name);
            $this->line("- {$permission->name} => {$displayName}");
        }

        $this->info('\n=== انتهى الاختبار ===');
    }
}
