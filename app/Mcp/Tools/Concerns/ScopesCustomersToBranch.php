<?php

namespace App\Mcp\Tools\Concerns;

use App\Models\auth\Admin;
use App\PermKeys;
use App\Services\BranchAccessService;
use App\Services\BranchDataSharingService;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\SystemModuleManager;

/**
 * موديل User (العملاء) بلا BranchScoped، فيُطبَّق هنا نفس عزل UserController::applyBranchScope
 * (مع مشاركة العملاء بين الفروع). أي أداة تستعلم من users يجب أن تمر به.
 */
trait ScopesCustomersToBranch
{
    protected function applyCustomerBranchScope($query, Admin $admin): void
    {
        $enabled = SystemModuleManager::isEnabled('branches');
        if (!$enabled || AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::BRANCHES_VIEW_ALL, PermKeys::CLIENTS_VIEW_ALL_BRANCHES])) {
            return;
        }

        if ($admin->branch_id) {
            app(BranchAccessService::class)->applyBranchIdToQuery(
                $query,
                (int) $admin->branch_id,
                'users',
                null,
                app(BranchDataSharingService::class)->sharedSourceBranchIds(BranchDataSharingService::CLIENTS)
            );

            return;
        }

        $query->whereRaw('1 = 0');
    }
}
