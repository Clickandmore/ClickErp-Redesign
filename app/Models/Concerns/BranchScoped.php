<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\Scopes\BranchScope;
use App\Services\BranchDataSharingService;
use App\Support\BranchQueryContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * يضيف عزل الفروع التلقائي في القراءة.
 *
 * يُستعمل مع AssignsBranch (الذي يختم الفرع عند الكتابة): التريت الأول
 * يمنع تسرّب القراءة، والثاني يضمن أن السجل الجديد يحمل فرع منشئه.
 *
 * للتقارير التجميعية أو المهام الإدارية:
 *   Contract::withoutBranchScope()->sum('value')
 *   BranchQueryContext::withoutBranchScope(fn () => ...)
 */
trait BranchScoped
{
    /** مشاركة كل سجلات هذا الكيان بين الفروع، لا السجلات غير المسندة فقط. */
    public function branchScopeSharedAcrossBranches(): bool
    {
        return false;
    }

    /** صلاحية عامة تستثني صاحبها من عزل هذا الكيان وحده. */
    public function branchScopeViewAllPermission(): ?string
    {
        return null;
    }

    /** Entity key used by per-source-branch sharing policies. */
    public function branchScopeSharingEntity(): ?string
    {
        return null;
    }

    /**
     * true للبيانات الأساسية المشتركة (شجرة الحسابات، مراكز التكلفة، العملاء):
     * السجل بلا فرع مرئي لكل الفروع.
     * false للمستندات: السجل بلا فرع يخص الفرع الرئيسي وحده.
     *
     * دالة وليست خاصية لأن PHP يرفض إعادة تعريف خاصية تريت بقيمة مختلفة.
     */
    public function branchScopeSharesUnassigned(): bool
    {
        return false;
    }

    public static function bootBranchScoped(): void
    {
        static::addGlobalScope(new BranchScope);
    }

    /** تجاوز العزل التلقائي لهذا الاستعلام فقط. */
    public function scopeWithoutBranchScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }

    /** تقييد صريح بفرع محدد، بغض النظر عن فرع المستخدم الحالي. */
    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $query;
        }

        return $query->withoutGlobalScope(BranchScope::class)
            ->where($this->getTable() . '.branch_id', $branchId);
    }

    /** كل الفروع، للاستعمال داخل التقارير التجميعية. */
    public function scopeAcrossBranches(Builder $query): Builder
    {
        return $query->withoutGlobalScope(BranchScope::class);
    }

    public function branchRelation(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /** هل هذا السجل مرئي لفرع المستخدم الحالي؟ */
    public function belongsToActiveBranch(): bool
    {
        $activeBranchId = BranchQueryContext::activeBranchId();

        if ($activeBranchId === null) {
            return true;
        }

        $branchId = (int) ($this->branch_id ?? 0);

        if ($branchId === $activeBranchId) {
            return true;
        }

        $sharingEntity = $this->branchScopeSharingEntity();
        if ($branchId > 0
            && $sharingEntity
            && in_array(
                $branchId,
                app(BranchDataSharingService::class)->sharedSourceBranchIds($sharingEntity),
                true
            )) {
            return true;
        }

        return $branchId === 0
            && ($this->branchScopeSharesUnassigned() || BranchQueryContext::activeBranchIsMain());
    }
}
