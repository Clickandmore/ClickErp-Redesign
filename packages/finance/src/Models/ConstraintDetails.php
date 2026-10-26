<?php

namespace Nami\Finance\Models;

use App\Models\Concerns\AssignsBranch;
use App\Services\Finance\BranchChartOfAccountsService;
use App\Models\Concerns\BranchScoped;

use Illuminate\Database\Eloquent\SoftDeletes;

class ConstraintDetails extends Finance
{
    // عزل الفروع: المستند يخص فرع منشئه، والسجلات القديمة للفرع الرئيسي.
    use AssignsBranch, BranchScoped;

    /** فرع سطر القيد: فرع القيد الأب، وإلا الفرع المالك للحساب. */
    protected function resolveBranchIdForCreate(): ?int
    {
        if ($this->branch_id !== null) {
            return (int) $this->branch_id ?: null;
        }

        $constraintId = (int) ($this->constraint_id ?? 0);

        if ($constraintId > 0) {
            $columns = ['branch_id'];
            if (\Illuminate\Support\Facades\Schema::hasColumn('fn_constraints', 'is_multi_branch')) {
                $columns[] = 'is_multi_branch';
            }
            $constraint = \Illuminate\Support\Facades\DB::table('fn_constraints')
                ->where('id', $constraintId)
                ->first($columns);
            $branchId = (int) ($constraint?->branch_id ?? 0);

            if ($branchId > 0 || ($constraint && ($constraint->is_multi_branch ?? false))) {
                return $branchId;
            }
        }

        return $this->resolveBranchIdFromCurrentAdmin()
            ?: app(BranchChartOfAccountsService::class)->branchIdForAccount(
                (int) ($this->account_id ?? 0) ?: null
            );
    }

    use SoftDeletes;

    protected $table = 'fn_constraint_details';

    protected $with = ['constraint', 'account', 'costCenter'];

    protected $fillable = [
        'branch_id','constraint_id', 'date', 'type', 'account_id', 'cost_center_id', 'debtor_value', 'creditor_value', 'statement', 'year_id'];

    public function constraint()
    {
        return $this->belongsTo(Constraint::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function counterAccount($ignore = null)
    {
        return $this->where('constraint_id', $this->constraint_id)
            ->where('account_id', '!=', $ignore)
            ->first()
            ->account
            ->name ?? "";
    }


}
