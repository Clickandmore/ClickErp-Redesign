<?php

namespace Nami\Finance\Services;

use App\Support\BranchReportFilter;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Constraint;

class AccountCostCenterService extends MainService
{
    protected AccountService $accountService;
    protected CostCenterService $costCenterService;
    protected ConstraintDetailsService $constraintDetailsService;
    protected YearService $yearService;
    protected ConstraintFileService $constraintFileService;
    protected ConfirmSettingService $confirmSettingService;

    public function __construct(
        Constraint               $model,
        AccountService           $accountService,
        CostCenterService        $costCenterService,
        ConstraintDetailsService $constraintDetailsService,
        YearService              $yearService,
        ConstraintFileService    $constraintFileService,
        ConfirmSettingService    $confirmSettingService
    )
    {
        $this->model = $model;
        $this->accountService = $accountService;
        $this->costCenterService = $costCenterService;
        $this->constraintDetailsService = $constraintDetailsService;
        $this->yearService = $yearService;
        $this->constraintFileService = $constraintFileService;
        $this->confirmSettingService = $confirmSettingService;
    }

    public function getAccounts($type = null)
    {
        return $this->accountService->getAccountWithNoChild($type);
    }

    public function getCostCenters()
    {
        return $this->costCenterService->get();
    }

    public function lastCode()
    {
        $row = $this->model->orderBy('code', 'DESC')->first();
        return (isset($row->code)) ? ($row->code + 1) : 1;
    }

    public function storeConstraint($data,$getActiveYear,$type = ConstraintTypiesEnum::DailyEntry)
    {
        $year = $getActiveYear;
        $data['year_id'] = $year->id;
        $data['type'] = $type;
        return $this->store($data);
    }

    public function storeFiles($constraint, $data)
    {
        if (isset($data['file'])) {
            $this->constraintFileService->storeFiles($constraint, $data['file']);
        }
        return true;
    }

    public function storeConstraintDetails(array $accountIds, int $constraintId, $request,$year_id = null,$statement = null,$date = null)
    {
        $yearId = $year_id ?? $this->yearService->getActiveYear()->id;

        foreach ($accountIds as $key => $accountId) {
            $data = [
                "type" => $request['account_type'][$key],
                "debtor_value" => $request['debtor_value'][$key],
                "creditor_value" => $request['creditor_value'][$key],
                "statement" => $statement ?? $request['statement_account'][$key],
                "account_id" => $accountId,
                "cost_center_id" => $request['cost_center_id'][$key] ?? '',
                "year_id" => $yearId,
                "constraint_id" => $constraintId,
                "date" => $date,
            ];

            $this->constraintDetailsService->storeConstraintDetails($data);
        }
    }


    public function updateConstraint($id, array $data, $request,$getActiveYear)
    {
        $year = $getActiveYear;
        $data['year_id'] = $year->id;
        $data['type'] = ConstraintTypiesEnum::DailyEntry;
        $constraint = $this->find($id);
        $constraint->update($data);
        return $constraint;
    }

    public function updateConstraintDetails(int $constraintId, array $accountIds, $request)
    {
        $yearId = $this->yearService->getActiveYear()->id;

        foreach ($accountIds as $key => $accountId) {
            $data = [
                "type" => $request['account_type'][$key],
                "debtor_value" => $request['debtor_value'][$key],
                "creditor_value" => $request['creditor_value'][$key],
                "statement" => $request['statement_account'][$key],
                "account_id" => $accountId,
                "cost_center_id" => $request['cost_center_id'][$key],
                "year_id" => $yearId,
                "constraint_id" => $constraintId,
            ];

            if (isset($request['old_id'][$key])) {
                $this->constraintDetailsService->updateConstraintDetails($request['old_id'][$key], $data);
            } else {
                $this->constraintDetailsService->storeConstraintDetails($data);
            }
        }
    }

    public function calculateTotal($constraintDetails, $type): string
    {
        $accounts = '';
        $total = 0;
        foreach ($constraintDetails as $con) {
            if ($con->type === $type) {
                $accounts .= $con->account->name . " <br> ";
                $total += $con->type === 'debtor' ? $con->debtor_value : $con->creditor_value;
            }
        }
        return $accounts . " بأجمالي " . ($total ?? 0);
    }

    public function getConfirmSetting($type)
    {
        return $this->confirmSettingService->getConfirmSetting($type);
    }

    public function storeFirstBalanceConstraint($data)
    {
        $data['code'] = $this->lastCode();
        $data['type'] = ConstraintTypiesEnum::FirstBalance;
        return $this->store($data);
    }

    public function findByYearAndType($year_id,$type)
    {
        return $this->model->query()->where('year_id',$year_id)->where('type',$type)->first();
    }

    public function checkHasAnyConstraint($constraint) // لو في اي قيد تم اضافته من قبل لتلك السنة
    {
        if ($constraint) {
            return $this->model->query()
                ->where('year_id', $constraint->year_id)
                ->where('type', '!=', ConstraintTypiesEnum::FirstBalance)
                ->exists();
        } else {
            return false;
        }
    }

    /* ----------NEW------------ */
    /**
     * المركز وكل ما تحته (أبناء وأحفاد)، فكشف عقدة الدولة أو أي مركز أب يجمع حركات عقوده.
     */
    public function costCenterWithDescendantIds(?int $costCenterId): array
    {
        if (!$costCenterId) {
            return [];
        }

        $ids = [$costCenterId];
        $level = [$costCenterId];
        while ($level) {
            $level = DB::table('fn_cost_centers')
                ->whereIn('parent_id', $level)
                ->whereNotIn('id', $ids)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $ids = array_merge($ids, $level);
        }

        return $ids;
    }

    public function filterAccount($dataSearch)
    {
        $centerIds = $this->costCenterWithDescendantIds(
            !empty($dataSearch['cost_center_id']) ? (int) $dataSearch['cost_center_id'] : null
        );

        $accountStatment = BranchReportFilter::apply(DB::table('fn_constraint_details'), 'fn_constraint_details')
            ->select([
                'fn_constraint_details.cost_center_id',
                'fn_cost_centers.title as cost_center_title',
                'fn_constraint_details.date',
                'fn_constraint_details.statement',
                'fn_constraint_details.debtor_value',
                'fn_constraint_details.creditor_value',
                'fn_accounts.status',
                'fn_constraints.type as constraints_type',
                'fn_accounts.name as account_name',
                'fn_constraint_details.constraint_id',
                DB::raw("COALESCE(admins.name, 'النظام') as name"),
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->leftJoin('admins', 'admins.id', '=', 'fn_constraints.created_by_id')
            ->leftJoin('fn_cost_centers', 'fn_cost_centers.id', '=', 'fn_constraint_details.cost_center_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->whereIn('fn_constraint_details.cost_center_id', $centerIds ?: [0])
            ->when($dataSearch['account_id'] != '', function ($q) use ($dataSearch) {
                return $q->where('fn_constraint_details.account_id', $dataSearch['account_id']);
            })
            ->when($dataSearch['type'] != 'both', function ($q) use ($dataSearch) {
                return $q->where('fn_constraint_details.type', $dataSearch['type']);
            })
            ->when($dataSearch['from_date'] != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '>=', $dataSearch['from_date']);
            })
            ->when($dataSearch['to_date'] != '', function ($q) use ($dataSearch) {
                return $q->whereDate('fn_constraint_details.date', '<=', $dataSearch['to_date']);
            })
            ->orderBy('fn_constraint_details.date')
            ->orderBy('fn_constraints.id')
            ->get();

        return $accountStatment;
    }

    public function getCostCenterLinkSummary(?int $costCenterId): array
    {
        if (!$costCenterId) {
            return [];
        }

        $center = DB::table('fn_cost_centers')->where('id', $costCenterId)->first();
        if (!$center) {
            return [];
        }

        $centerIds = $this->costCenterWithDescendantIds($costCenterId);
        $linked = BranchReportFilter::apply(DB::table('fn_constraint_details'), 'fn_constraint_details')
            ->whereNull('deleted_at')
            ->whereIn('cost_center_id', $centerIds);

        return [
            'center' => $center,
            'descendant_centers' => count($centerIds) - 1,
            'linked_details' => (clone $linked)->count(),
            'linked_constraints' => (clone $linked)->distinct()->count('constraint_id'),
            'all_constraints' => DB::table('fn_constraints')->whereNull('deleted_at')->count(),
        ];
    }

    public function filterConstraints($dataSearch)
    {
        $account_id = $dataSearch['account_id'];
        $otherAccounts = $this->model->select([
            'fn_constraints.id',
        ])
        ->join('fn_constraint_details', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
        ->whereNull('fn_constraint_details.deleted_at')
        ->where('fn_constraint_details.account_id', '=', $dataSearch['account_id'])
        ->when($dataSearch['type'] != 'both', function ($q) use ($dataSearch) {
            return $q->where('fn_constraint_details.type', $dataSearch['type']);
        })
        ->when($dataSearch['from_date'] != '', function ($q) use ($dataSearch) {
            return $q->whereDate('fn_constraint_details.date', '>=', $dataSearch['from_date']);
        })
        ->when($dataSearch['to_date'] != '', function ($q) use ($dataSearch) {
            return $q->whereDate('fn_constraint_details.date', '<=', $dataSearch['to_date']);
        })
        ->with(['accounts' => function ($query) use ($account_id) {
            return $query->where('account_id', '!=', $account_id);
        }])
        ->get();
        $result = [];
        foreach ($otherAccounts as $one) {
            $result[$one->id]['accounts'] = $one->accounts;
        }
        return $result;
    }
}
