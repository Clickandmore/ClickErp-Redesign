<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Account as Model;

class OpeningBalanceService extends MainService
{

    function __construct(Model $model,protected AccountService $accountService
        ,protected ConstraintDetailsService $constraintDetailsService
        ,protected ConstraintService $constraintService
    )
    {
        $this->model = $model;
    }

    public function getOpeningBalances($data)
    {
        $data['accounts_with_no_children'] = $this->getAccountsWithoutChildren($data);
        $data['constraint_details'] = $this->assignConstraintDetail($data['year_id']);
        $data['constraint'] = $this->checkHasFirstBalance($data['year_id']);
        $data['check_previous_constraint'] = $this->hasPreviousConstraint($data['constraint']);
        $this->checkAccountCanUpdate($data['accounts_with_no_children'],$data['constraint_details']);
        return $data;
    }

    public function getAccountsWithoutChildren(array $data = [])
    {
        return $this->model
            ->query()
            ->select(['id', 'code', 'name', 'status', 'level', 'parent_id'])
            ->with('parent:id,name,code,type')
            ->doesntHave('children')
            ->when(!empty($data['level']), function ($query) use ($data) {
                $query->where('level', $data['level']);
            })
            ->when(!empty($data['keyword']), function ($query) use ($data) {
                $keyword = trim((string) $data['keyword']);

                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%")
                        ->orWhereHas('parent', function ($parentQuery) use ($keyword) {
                            $parentQuery->where('name', 'like', "%{$keyword}%")
                                ->orWhere('code', 'like', "%{$keyword}%");
                        });
                });
            })
            ->orderBy('code')
            ->get();
    }

    public function getAccountLevels(): array
    {
        return $this->accountService->getLevels();
    }

    public function assignConstraintDetail($year_id)
    {
        return $this->constraintDetailsService->constraintOfYear($year_id);
    }

    public function checkAccountCanUpdate($accounts,$constraint_details)
    {
        foreach ($accounts as $account){
            $account->can_updated = true;
            $account->constraint_id = null;
            $check = $constraint_details->where('account_id',$account->id)->first();
            if ($check){
                $account->constraint_id = $check['constraint_id'];
                $account->can_updated = false;
            }
        }
        return $accounts;
    }


    public function checkHasFirstBalance($year_id)
    {
        return $this->constraintService->findByYearAndType($year_id,ConstraintTypiesEnum::FirstBalance);
    }

    public function hasPreviousConstraint($constraint)
    {
        return $this->constraintService->checkHasAnyConstraint($constraint);
    }

    public function storeFirstBalanceConstraint($data)
    {
        return DB::transaction(function () use ($data) {
            $constraint = $this->checkHasFirstBalance($data['year_id']);

            if ($constraint) {
                $constraint->update([
                    'value' => $data['value'],
                    'date' => $data['date'],
                    'statement' => $data['statement'],
                ]);

                $visibleAccountIds = $data['visible_account_id'] ?? [];

                $this->constraintDetailsService->model
                    ->where('constraint_id', $constraint->id)
                    ->when(!empty($visibleAccountIds), function ($query) use ($visibleAccountIds) {
                        $query->whereIn('account_id', $visibleAccountIds);
                    })
                    ->delete();

                $this->constraintDetailsService->model
                    ->where('constraint_id', $constraint->id)
                    ->update([
                        'date' => $constraint->date,
                    ]);
            } else {
                $constraint = $this->constraintService->storeFirstBalanceConstraint($data);
            }

            $this->constraintService->storeConstraintDetails(
                $data['account_id'],
                $constraint->id,
                $data,
                $constraint->year_id,
                $constraint->statement,
                $constraint->date
            );

            $constraint->update([
                'value' => $this->constraintDetailsService->model
                    ->where('constraint_id', $constraint->id)
                    ->sum('debtor_value'),
            ]);

            return $constraint;
        });
    }



}
