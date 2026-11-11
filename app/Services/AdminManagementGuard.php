<?php

namespace App\Services;

use App\Models\auth\Admin;
use App\Models\Role;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\SystemModuleManager;

class AdminManagementGuard
{
    public function assertCanManage(Admin $target, ?Admin $actor = null, bool $allowSelf = false): void
    {
        $actor ??= auth('admin')->user();
        abort_unless($actor, 403);

        if (! $allowSelf && (int) $actor->id === (int) $target->id) {
            abort(403, 'لا يمكنك تنفيذ هذا الإجراء على حسابك.');
        }

        if (! SystemModuleManager::isEnabled('branches')) {
            return;
        }

        $actorBranchId = (int) ($actor->branch_id ?? 0);
        if ($actorBranchId <= 0) {
            return;
        }

        $targetBranchId = (int) ($target->branch_id ?? 0);
        abort_unless($targetBranchId === $actorBranchId, 403);
        abort_if($this->isProtected($target), 403);

        $actorPermissions = $actor->getAllPermissions()->pluck('name');
        $hasPermissionsOutsideActor = $target->getAllPermissions()->pluck('name')->diff($actorPermissions)->isNotEmpty();
        abort_if($hasPermissionsOutsideActor, 403);
    }

    public function assertCanUseRole(?Role $role, ?Admin $actor = null): void
    {
        $actor ??= auth('admin')->user();
        abort_unless($actor, 403);

        if (! $role || ! SystemModuleManager::isEnabled('branches') || empty($actor->branch_id)) {
            return;
        }

        $actorPermissions = $actor->getAllPermissions()->pluck('name');
        $rolePermissions = $role->permissions()->pluck('name');

        abort_if($rolePermissions->contains(PermKeys::BRANCHES_VIEW_ALL), 403);
        abort_if($rolePermissions->diff($actorPermissions)->isNotEmpty(), 403);
    }

    public function assertBranchAssignmentAllowed(?int $branchId, ?Admin $actor = null): void
    {
        $actor ??= auth('admin')->user();
        abort_unless($actor, 403);

        if (! SystemModuleManager::isEnabled('branches') || empty($actor->branch_id)) {
            return;
        }

        abort_unless($branchId !== null && $branchId === (int) $actor->branch_id, 403);
    }

    public function assertGeneralAdministration(?Admin $actor = null): void
    {
        $actor ??= auth('admin')->user();
        abort_unless($actor, 403);

        if (SystemModuleManager::isEnabled('branches')) {
            abort_if(! empty($actor->branch_id), 403);
        }
    }

    private function isProtected(Admin $admin): bool
    {
        return in_array($admin->admin_type, ['super_admin'], true)
            || AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::BRANCHES_VIEW_ALL]);
    }
}
