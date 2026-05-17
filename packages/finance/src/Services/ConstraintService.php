<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Constraint as Model;

class ConstraintService extends MainService
{
    protected AccountService $accountService;
    protected CostCenterService $costCenterService;
    protected ConstraintDetailsService $constraintDetailsService;
    protected YearService $yearService;
    protected ConstraintFileService $constraintFileService;
    protected ConfirmSettingService $confirmSettingService;

    public function __construct(
        Model                    $model,
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

    public function store($data)
    {
        $constraint = parent::store($data);
        $this->logConstraintActivity($constraint, 'created', __('finance::finance.constraint_created_log_description'));

        return $constraint;
    }

    public function getAccounts($type = null)
    {
        return $this->accountService->getAccountWithNoChild($type);
    }

    public function getAccountsForConstraint(Model|int $constraint)
    {
        $constraint = $constraint instanceof Model
            ? $constraint->loadMissing('constraintDetails.account')
            : $this->find($constraint)->loadMissing('constraintDetails.account');

        $accounts = $this->getAccounts();
        $selectedAccounts = $constraint->constraintDetails
            ->pluck('account')
            ->filter()
            ->reject(fn ($account) => $accounts->contains('id', $account->id));

        return $accounts
            ->concat($selectedAccounts)
            ->unique('id')
            ->sortBy(fn ($account) => $account->code ?? $account->id)
            ->values();
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
        $data['statement'] = $data['statement'] ?? '-';
        $data['created_by_id'] = $data['created_by_id'] ?? auth('admin')->id();
        return $this->store($data);
    }

    public function storeFiles($constraint, $data)
    {
        if (isset($data['file'])) {
            $this->constraintFileService->storeFiles($constraint, $data['file']);
            $this->logConstraintActivity($constraint, 'file_added', __('finance::finance.constraint_file_added_log_description'), [
                'files_count' => count($data['file']),
            ]);
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
                "statement" => $request['statement_account'][$key] ?? '-',
                "account_id" => $accountId,
                "cost_center_id" => $request['cost_center_id'][$key] ?? '',
                "year_id" => $yearId,
                "constraint_id" => $constraintId,
                "date" => $date ?? $this->model->find($constraintId)->date,
            ];

            $this->constraintDetailsService->storeConstraintDetails($data);
        }
    }


    public function updateConstraint($id, array $data, $request,$getActiveYear)
    {
        $year = $getActiveYear;
        $data['year_id'] = $year->id;
        $constraint = $this->find($id);
        $data['type'] = $data['type'] ?? $constraint->getRawOriginal('type') ?? ConstraintTypiesEnum::DailyEntry->value;
        $constraint->update($data);
        return $constraint;
    }

    public function updateConstraintDetails(int $constraintId, array $accountIds, $request)
    {
        $yearId = $this->yearService->getActiveYear()->id;
        $submittedOldIds = collect($request['old_id'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->constraintDetailsService->model
            ->where('constraint_id', $constraintId)
            ->when(!empty($submittedOldIds), function ($query) use ($submittedOldIds) {
                $query->whereNotIn('id', $submittedOldIds);
            })
            ->delete();

        foreach ($accountIds as $key => $accountId) {
            $data = [
                "type" => $request['account_type'][$key],
                "debtor_value" => $request['debtor_value'][$key],
                "creditor_value" => $request['creditor_value'][$key],
                "statement" => $request['statement_account'][$key] ?? '-',
                "account_id" => $accountId,
                "cost_center_id" => $request['cost_center_id'][$key] ?? null,
                "year_id" => $yearId,
                "constraint_id" => $constraintId,
                "date" => $request['date'] ?? $this->model->find($constraintId)->date,
            ];

            if (isset($request['old_id'][$key]) && $request['old_id'][$key]) {
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
        // return $accounts . " بأجمالي " . ($total ?? 0);
        return $accounts;
    }

    public function getConfirmSetting($type)
    {
        return $this->confirmSettingService->getConfirmSetting($type);
    }

    public function storeFirstBalanceConstraint($data)
    {
        $data['code'] = $this->lastCode();
        $data['type'] = ConstraintTypiesEnum::FirstBalance;
        $data['created_by_id'] = auth('admin')->user()->id;
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
    public function getFirstBalance($dataSearch)
    {
        if($dataSearch["from_date"] != ""){
            $accountId = $dataSearch["account_id"];  // Replace with your desired account_id
            $customDate = $dataSearch["from_date"];  // Replace with your desired date
            return  DB::table('fn_constraint_details')->select(\DB::raw('SUM(fn_constraint_details.debtor_value) as sum_debtor_value, fn_accounts.status ,
                                           SUM(fn_constraint_details.creditor_value) as sum_creditor_value'))
                                        ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
                                        ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
                                        ->whereNull('fn_constraint_details.deleted_at')
                                        ->whereNull('fn_constraints.deleted_at')
                                        ->where('fn_constraint_details.account_id', $accountId)
                                        ->where('fn_constraint_details.date', '<', $customDate)
                                        ->first();
        }
        return null;
    }
    public function filterAccount($dataSearch)
    {
        $accountStatment = DB::table('fn_constraint_details')
            ->select([
                'fn_constraint_details.date',
                'fn_constraint_details.statement',
                'fn_constraint_details.debtor_value',
                'fn_constraint_details.creditor_value',
                'fn_accounts.status',
                'fn_constraints.type as constraints_type',
                'fn_constraint_details.constraint_id',
                'admins.name',
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->leftJoin('admins', 'admins.id', '=', 'fn_constraints.created_by_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
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
            ->orderBy('fn_constraint_details.date')
            ->orderBy('fn_constraints.id')
            ->get();

        return $accountStatment;
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

    public function getIndexData() {
        return $this->model->query()
            ->with(['constraintDetails.account', 'bond', 'transfer_entry'])
            ->approved()
            ->when(request('type_filter'), function ($q) {
                $q->where('fn_constraints.type', request('type_filter'));
            })
            ->latest('fn_constraints.created_at');
    }

    public function getPendingApprovalData()
    {
        return $this->model->query()
            ->with(['constraintDetails.account', 'bond', 'transfer_entry', 'created_by'])
            ->pendingApproval()
            ->when(request('type_filter'), function ($q) {
                $q->where('fn_constraints.type', request('type_filter'));
            })
            ->latest('fn_constraints.created_at');
    }

    public function approveConstraint(int $id, ?int $adminId = null): Model
    {
        $constraint = $this->find($id);
        $constraint->approve($adminId);
        $this->logConstraintActivity($constraint, 'approved', __('finance::finance.constraint_approved_log_description'));

        return $constraint->refresh();
    }

    public function deleteConstraint(int $id): Model
    {
        return DB::transaction(function () use ($id) {
            $constraint = $this->find($id);
            $snapshot = $this->constraintSnapshot($constraint);

            $this->logConstraintActivity($constraint, 'deleted', __('finance::finance.constraint_deleted_log_description'), [
                'before' => $snapshot,
            ]);
            $constraint->constraintDetails()->delete();
            $constraint->delete();

            return $constraint;
        });
    }

    public function logConstraintActivity(Model $constraint, string $action, ?string $description = null, array $metadata = []): void
    {
        $request = request();

        $constraint->logs()->create([
            'admin_id' => auth('admin')->id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
        ]);
    }

    public function constraintSnapshot(Model $constraint): array
    {
        $constraint->loadMissing('constraintDetails');

        return [
            'id' => $constraint->id,
            'code' => $constraint->code,
            'type' => $constraint->getRawOriginal('type'),
            'value' => $constraint->value,
            'date' => $constraint->date,
            'statement' => $constraint->statement,
            'details' => $constraint->constraintDetails->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'type' => $detail->type,
                    'account_id' => $detail->account_id,
                    'cost_center_id' => $detail->cost_center_id,
                    'debtor_value' => $detail->debtor_value,
                    'creditor_value' => $detail->creditor_value,
                    'statement' => $detail->statement,
                ];
            })->values()->all(),
        ];
    }

    public function getIndexDataAll($type= null) {
        return $this->model->query()->latest();
    }

    public function getTypes()
    {
       return ConstraintTypiesEnum::cases();
    }
    public function getTypeRoutes()
    {
        return ConstraintTypiesEnum::cases();
    }
}
