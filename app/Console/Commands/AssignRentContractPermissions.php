<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\auth\Admin;
use App\PermKeys;

class AssignRentContractPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rent:assign-permissions {--admin-id= : ID of specific admin to assign permissions to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign rent contract permissions to admin users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🏠 بدء إضافة صلاحيات عقود التأجير...');

        // صلاحيات عقود التأجير
        $rentPermissions = [
            PermKeys::RENT_CONTRACT_VIEW => 'عرض عقود التأجير',
            PermKeys::RENT_CONTRACT_CREATE => 'إنشاء عقود التأجير',
            PermKeys::RENT_CONTRACT_EDIT => 'تعديل عقود التأجير',
            PermKeys::RENT_CONTRACT_DELETE => 'حذف عقود التأجير',
            PermKeys::RENT_CONTRACT_CONTROL => 'التحكم في عقود التأجير',
        ];

        // إنشاء الصلاحيات إذا لم تكن موجودة
        $this->info('📋 إنشاء الصلاحيات...');
        foreach ($rentPermissions as $permission => $description) {
            $perm = Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);

            if ($perm->wasRecentlyCreated) {
                $this->info("✅ تم إنشاء صلاحية: {$description}");
            } else {
                $this->comment("ℹ️  الصلاحية موجودة مسبقاً: {$description}");
            }
        }

        // تحديد المستخدمين
        if ($this->option('admin-id')) {
            $admins = Admin::where('id', $this->option('admin-id'))->get();
            if ($admins->isEmpty()) {
                $this->error('❌ لم يتم العثور على مستخدم بهذا ID');
                return 1;
            }
        } else {
            $admins = Admin::all();
        }

        if ($admins->isEmpty()) {
            $this->error('❌ لم يتم العثور على أي مستخدمين');
            return 1;
        }

        // إضافة الصلاحيات للمستخدمين
        $this->info('👥 إضافة الصلاحيات للمستخدمين...');

        foreach ($admins as $admin) {
            $addedPermissions = 0;

            foreach (array_keys($rentPermissions) as $permission) {
                if (!$admin->hasPermissionTo($permission)) {
                    $admin->givePermissionTo($permission);
                    $addedPermissions++;
                }
            }

            if ($addedPermissions > 0) {
                $this->info("✅ تم إضافة {$addedPermissions} صلاحيات للمستخدم: {$admin->name} (ID: {$admin->id})");
            } else {
                $this->comment("ℹ️  المستخدم {$admin->name} لديه جميع الصلاحيات مسبقاً");
            }
        }

        $this->info('🎉 تم الانتهاء من إضافة صلاحيات عقود التأجير بنجاح!');

        // عرض ملخص
        $this->table(
            ['الصلاحية', 'الوصف'],
            collect($rentPermissions)->map(function ($description, $permission) {
                return [$permission, $description];
            })->toArray()
        );

        return 0;
    }
}
