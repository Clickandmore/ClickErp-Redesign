<?php

namespace App\Models\Scopes;

use App\Services\BranchAccessService;
use App\Services\BranchDataSharingService;
use App\Support\BranchQueryContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * عزل الفروع التلقائي على مستوى الموديل.
 *
 * يعمل فقط عندما يقرر BranchQueryContext وجود فرع نشط، فيبقى النظام
 * بلا أي تغيير عند تعطيل موديول الفروع أو للإدارة العامة أو في الـ console.
 *
 * موظف الفرع الرئيسي يرى أيضاً السجلات القديمة التي branch_id فيها NULL،
 * وهو نفس سلوك BranchAccessService في الفلترة اليدوية.
 */
class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $branchId = BranchQueryContext::activeBranchId();

        if ($branchId === null) {
            return;
        }

        if ($this->sharedAcrossBranches($model) || $this->adminCanViewAll($model)) {
            return;
        }

        $table = $model->getTable();

        if (! app(BranchAccessService::class)->tableHasBranchColumn($table)) {
            return;
        }

        $column = $table . '.branch_id';
        $visibleBranchIds = array_values(array_unique(array_merge(
            [$branchId],
            $this->sharedSourceBranchIds($model)
        )));

        if ($this->includesUnassigned($model, $branchId)) {
            $builder->where(function (Builder $query) use ($column, $visibleBranchIds) {
                $query->whereIn($column, $visibleBranchIds)->orWhereNull($column);
            });

            return;
        }

        $builder->whereIn($column, $visibleBranchIds);
    }

    private function sharedAcrossBranches(Model $model): bool
    {
        return method_exists($model, 'branchScopeSharedAcrossBranches')
            && $model->branchScopeSharedAcrossBranches();
    }

    /**
     * صلاحيات الاستثناء كيانية وعامة، لذلك تُقرأ من الدور مباشرة ولا تُنسخ
     * إلى role_branch_permissions لكل فرع.
     */
    private function adminCanViewAll(Model $model): bool
    {
        if (! method_exists($model, 'branchScopeViewAllPermission')) {
            return false;
        }

        $permission = $model->branchScopeViewAllPermission();
        $admin = auth('admin')->user();

        if (! is_string($permission) || $permission === '' || ! $admin || ! method_exists($admin, 'hasPermissionTo')) {
            return false;
        }

        try {
            return $admin->hasPermissionTo($permission, 'admin');
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            // أثناء النشر قد يسبق الكود migration إضافة الصلاحية؛ يبقى العزل آمناً.
            return false;
        }
    }

    /** @return array<int, int> */
    private function sharedSourceBranchIds(Model $model): array
    {
        if (! method_exists($model, 'branchScopeSharingEntity')) {
            return [];
        }

        $entityType = $model->branchScopeSharingEntity();

        return is_string($entityType) && $entityType !== ''
            ? app(BranchDataSharingService::class)->sharedSourceBranchIds($entityType)
            : [];
    }

    /**
     * متى تظهر السجلات التي branch_id فيها NULL؟
     *
     *  - بيانات أساسية مشتركة (شجرة الحسابات، مراكز التكلفة): دائماً، لأن
     *    الحساب غير المملوك لفرع حساب شركة تستعمله كل الفروع.
     *  - المستندات (قيود، سندات، فواتير): لموظف الفرع الرئيسي فقط، لأن
     *    السجلات القديمة قبل تفعيل الفروع تخصّه.
     */
    private function includesUnassigned(Model $model, int $branchId): bool
    {
        if (method_exists($model, 'branchScopeSharesUnassigned') && $model->branchScopeSharesUnassigned()) {
            return true;
        }

        return BranchQueryContext::activeBranchIsMain();
    }
}
