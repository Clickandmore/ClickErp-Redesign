<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AssignAllPermissionsToAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:all-permissions-to-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إضافة جميع الصلاحيات الموجودة في النظام إلى دور Administrator (role_id = 1)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 جاري إضافة جميع الصلاحيات إلى دور Administrator...');

        // الحصول على دور Administrator مع تحديد الـ guard
        $adminRole = Role::where('id', 1)->orWhere('name', 'Administrator')->first();

        if (!$adminRole) {
            $this->error('❌ لم يتم العثور على دور Administrator');
            return Command::FAILURE;
        }

        $this->info("✅ تم العثور على دور: {$adminRole->name} (ID: {$adminRole->id})");

        // الحصول على جميع الصلاحيات مع نفس الـ guard
        $allPermissions = Permission::withoutGlobalScopes()
            ->where('guard_name', $adminRole->guard_name)
            ->get();
        $this->info("📊 عدد الصلاحيات الموجودة: {$allPermissions->count()}");

        // الحصول على الصلاحيات المخصصة حالياً للدور
        $currentPermissions = $adminRole->permissions;
        $this->info("📋 عدد الصلاحيات المخصصة حالياً: {$currentPermissions->count()}");

        // إضافة جميع الصلاحيات للدور
        $adminRole->syncPermissions($allPermissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // التحقق من النتيجة
        $newPermissions = $adminRole->fresh()->permissions;
        $this->info("✅ تم إضافة الصلاحيات بنجاح! عدد الصلاحيات الجديدة: {$newPermissions->count()}");

        // عرض تفاصيل إضافية
        $this->newLine();
        $this->info('📋 تفاصيل الصلاحيات المضافة:');

        $permissionsByGroup = $allPermissions->groupBy(function($permission) {
            if (str_starts_with($permission->name, 'clients_')) return 'العملاء';
            if (str_starts_with($permission->name, 'cvs_')) return 'السير الذاتية';
            if (str_starts_with($permission->name, 'orders_')) return 'الطلبات';
            if (str_starts_with($permission->name, 'rec_contracts_')) return 'عقود الاستقدام';
            return 'أخرى';
        });

        foreach ($permissionsByGroup as $group => $permissions) {
            $this->info("• {$group}: {$permissions->count()} صلاحية");
        }

        $this->newLine();
        $this->info('🎉 تم إضافة جميع الصلاحيات إلى دور Administrator بنجاح!');

        return Command::SUCCESS;
    }
}
