<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use App\Support\BranchReportFilter;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConstraintDetails as Model;

class AccountStatementService extends MainService
{

    function __construct(Model $model, protected DoubleAccountService $doubleAccountService
    , protected ConstraintService $constraintService)
    {
        $this->model = $model;
    }

    /* ---------NEW--------- */
    public function getDoubleAccounts()
    {
        return $this->doubleAccountService->get();
    }

    public function getDoubleAccountSubAccountsArr($id)
    {
        $doubleAccount = $this->doubleAccountService->find($id);
        if (!$doubleAccount) {
            return [];
        }
        return $doubleAccount->details()->pluck("account_id");
    }

    public function filterDoubleAccount($dataSearch, $acoounts, bool $paginate = false)
    {
        $doubleAccountStatment = DB::table('fn_constraint_details')
            ->select([
                'fn_accounts.name  as account_name',
                'fn_constraint_details.date',
                'fn_constraint_details.statement',
                'fn_constraint_details.debtor_value',
                'fn_constraint_details.creditor_value',
                "fn_accounts.status",
                'fn_constraints.type as constraints_type',
                'fn_constraint_details.constraint_id',
                'admins.name',
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->join('admins', 'admins.id', '=', 'fn_constraints.created_by_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->whereIn('fn_constraint_details.account_id',$acoounts )
            // استعلام خام لا يراه الـ global scope؛ بدون هذا السطر يعرض كشف
            // الحساب حركة كل الفروع لموظف فرع واحد.
            ->tap(fn ($q) => BranchReportFilter::apply($q, 'fn_constraint_details'))
            ->when($dataSearch["type"] != "both", function ($q) use ($dataSearch) {
                return $q->where("fn_constraint_details.type", $dataSearch["type"]);
            })
            ->when($dataSearch["from_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("fn_constraint_details.date", ">=", $dataSearch["from_date"]);
            })
            ->when($dataSearch["to_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("fn_constraint_details.date", "<=", $dataSearch["to_date"]);
            })
            ->orderBy('fn_constraint_details.date') // Order by fn_constraint_details.date
            ->orderBy('fn_constraints.id')->orderBy('fn_constraint_details.id'); // Then order by constraints.id
        return $paginate ? StatementPaginator::fromQuery($doubleAccountStatment, false) : $doubleAccountStatment->get();
    }

    public function filterConstraints($dataSearch, $acoounts, ?array $constraintIds = null)
    {
        $otherAccounts = $this->constraintService->model->select([
            'fn_constraints.id',
        ])
            ->join('fn_constraint_details', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereIn('fn_constraint_details.account_id',$acoounts )
            ->when($dataSearch["type"] != "both", function ($q) use ($dataSearch) {
                return $q->where("fn_constraint_details.type", $dataSearch["type"]);
            })
            ->when($dataSearch["from_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("fn_constraint_details.date", ">=", $dataSearch["from_date"]);
            })
            ->when($dataSearch["to_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("fn_constraint_details.date", "<=", $dataSearch["to_date"]);
            })
            ->when($constraintIds !== null, fn ($q) => $q->whereIn('fn_constraints.id', $constraintIds))
            ->distinct()
            ->with(['accounts'=> function ($query) use ($acoounts) {
                return $query->whereNotIn("account_id", $acoounts);
            }])
            ->get();
        $result = [];
        foreach ($otherAccounts as $one) {
            $result[$one->id]["accounts"] = $one->accounts;
        }
        return $result;
    }

}
