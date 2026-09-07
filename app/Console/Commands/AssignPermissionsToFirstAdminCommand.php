<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\auth\Admin;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignPermissionsToFirstAdminCommand extends Command
{
    protected $signature = 'admin:assign-permissions-to-first';
    protected $description = 'إعطاء جميع الصلاحيات للمستخدم الأول في جدول admins';

    public function handle()
    {
        // البحث عن أول مستخدم admin
        $admin = Admin::first();

        if (!$admin) {
            $this->error('لا يوجد مستخدمين في جدول admins');
            return;
        }

        $this->info("المستخدم: {$admin->name} ({$admin->email})");

        // البحث عن دور Administrator أو إنشاؤه
        $role = Role::firstOrCreate([
            'name' => 'Administrator',
            'guard_name' => 'admin'
        ]);

        // جلب جميع الصلاحيات
        $permissions = Permission::where('guard_name', 'admin')->get();

        if ($permissions->isEmpty()) {
            $this->error('لا توجد صلاحيات في قاعدة البيانات');
            return;
        }

        // إعطاء جميع الصلاحيات للدور
        $role->syncPermissions($permissions);

        // ربط الدور بالمستخدم
        $admin->syncRoles([$role->name]);

        $this->info("✅ تم إعطاء {$permissions->count()} صلاحية للمستخدم {$admin->name}");
        $this->info("✅ تم ربط دور {$role->name} بالمستخدم");

        // عرض بعض الصلاحيات للتأكد
        $this->info("بعض الصلاحيات المضافة:");
        $permissions->take(10)->each(function($permission) {
            $this->line("  - {$permission->name}");
        });
    }
}