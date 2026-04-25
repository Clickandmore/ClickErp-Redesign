<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\NewPermKeys;

class AssignRecruitmentContractsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // الحصول على دور Administrator
        $adminRole = Role::where('name', 'Administrator')->first();

        if (!$adminRole) {
            $this->command->error('دور Administrator غير موجود!');
            return;
        }

        // قائمة الصلاحيات الجديدة لعقود الاستقدام
        $newPermissions = [
            NewPermKeys::REC_CONTRACTS_VIEW,
            NewPermKeys::REC_CONTRACTS_CREATE,
            NewPermKeys::REC_CONTRACTS_EDIT,
            NewPermKeys::REC_CONTRACTS_UPDATE,
            NewPermKeys::REC_CONTRACTS_DELETE,
            NewPermKeys::REC_CONTRACTS_EXPORT,
            NewPermKeys::REC_CONTRACTS_PRINT,
            NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED,
            NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED,
            NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ,
            NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER,
            NewPermKeys::REC_CONTRACTS_ADD_TRAVEL,
            NewPermKeys::REC_CONTRACTS_EDIT_TRAVEL,
            NewPermKeys::REC_CONTRACTS_DELETE_TRAVEL,
            NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL,
            NewPermKeys::REC_CONTRACTS_VIEW_TRAVEL,
            NewPermKeys::REC_CONTRACTS_WARRANTY_VIEW,
            NewPermKeys::REC_CONTRACTS_WARRANTY_MANAGE,
            NewPermKeys::REC_CONTRACTS_CLOSE_AFTER_WARRANTY,
            NewPermKeys::REC_CONTRACTS_RETURN,
            NewPermKeys::REC_CONTRACTS_RETURN_VIEW,
            NewPermKeys::REC_CONTRACTS_RETURN_MANAGE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
            NewPermKeys::REC_CONTRACTS_REPORTS,
            NewPermKeys::REC_CONTRACTS_ANALYTICS,
            NewPermKeys::REC_CONTRACTS_CANCEL_VIEW,
            NewPermKeys::REC_CONTRACTS_CANCEL_CREATE,
            NewPermKeys::REC_CONTRACTS_CANCEL_EDIT,
            NewPermKeys::REC_CONTRACTS_CANCEL_DELETE,
            NewPermKeys::REC_CONTRACTS_CANCEL_APPROVE,
            NewPermKeys::REC_CONTRACTS_CANCEL_REJECT,
        ];

        $assignedCount = 0;
        $notFoundCount = 0;

        foreach ($newPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();

            if ($permission) {
                if (!$adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                    $assignedCount++;
                    $this->command->info("تم تعيين الصلاحية: {$permissionName}");
                } else {
                    $this->command->line("الصلاحية موجودة مسبقاً: {$permissionName}");
                }
            } else {
                $notFoundCount++;
                $this->command->error("الصلاحية غير موجودة: {$permissionName}");
            }
        }

        $this->command->info("=== ملخص العملية ===");
        $this->command->info("تم تعيين {$assignedCount} صلاحية جديدة");
        $this->command->info("الصلاحيات غير الموجودة: {$notFoundCount}");
        $this->command->info("إجمالي صلاحيات الدور: " . $adminRole->permissions()->count());
    }
}
