<?php

namespace Nami\Finance\Services;

use App\Support\BranchSharingSettings;
use App\Support\BranchReportFilter;
use App\Support\ConstraintReferenceGuard;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint as Model;

class ConstraintService extends MainService
{
    protected AccountService $accountService;
    protected CostCenterService $costCenterService;
    protected ConstraintDetailsService $constraintDetailsService;
    protected YearService $yearService;
    protected ConstraintFileService $constraintFileService;
    protected ConfirmSettingService $confirmSettingService;
    protected ConstraintNumberService $constraintNumberService;

    public function __construct(
        Model                    $model,
        AccountService           $accountService,
        CostCenterService        $costCenterService,
        ConstraintDetailsService $constraintDetailsService,
        YearService              $yearService,
        ConstraintFileService    $constraintFileService,
        ConfirmSettingService    $confirmSettingService,
        ConstraintNumberService  $constraintNumberService
    )
    {
        $this->model = $model;
        $this->accountService = $accountService;
        $this->costCenterService = $costCenterService;
        $this->constraintDetailsService = $constraintDetailsService;
        $this->yearService = $yearService;
        $this->constraintFileService = $constraintFileService;
        $this->confirmSettingService = $confirmSettingService;
        $this->constraintNumberService = $constraintNumberService;
    }

    public function store($data)
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    // Never trust the preview number posted by a form. Reserve the
                    // definitive number at the last possible moment on the server.
                    $data['code'] = $this->constraintNumberService->nextCode();
                    $constraint = parent::store($data);
                    $this->logConstraintActivity($constraint, 'created', __('finance::finance.constraint_created_log_description'));

                    return $constraint;
                });
            } catch (QueryException $exception) {
                if (!$this->isActiveCodeCollision($exception) || $attempt === 5) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to reserve a unique constraint number.');
    }

    private function isActiveCodeCollision(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1062
            && str_contains($exception->getMessage(), 'fn_constraints_active_code_unique');
    }

    public function getAccounts($type = null, ?int $branchId = null)
    {
        return $this->accountService->getAccountWithNoChild($type, $branchId);
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

    public function getCostCenters(?int $branchId = null)
    {
        return $this->costCenterService->getForBranch($branchId);
    }

    public function lastCode()
    {
        return $this->constraintNumberService->previewNextCode();
    }

    public function storeConstraint($data,$getActiveYear,$type = ConstraintTypiesEnum::DailyEntry)
    {
        $this->assertAccountsAssignableToBranch($data['account_id'] ?? [], $data['branch_id'] ?? null);

        $year = $getActiveYear;
        $data['year_id'] = $year->id;
        $data['type'] = $type;
        $data['statement'] = $data['statement'] ?? '-';
        $data['created_by_id'] = $data['created_by_id'] ?? auth('admin')->id();
        return $this->store($data);
    }

    private function assertAccountsAssignableToBranch(array $accountIds, ?int $branchId): void
    {
        if ($branchId === null || ! BranchSharingSettings::accountsPerBranch() || empty($accountIds)) {
            return;
        }

        $invalid = Account::query()
            ->whereIn('id', collect($accountIds)->filter()->map(fn ($id) => (int) $id))
            ->whereHas('branches')
            ->whereDoesntHave('branches', fn ($branches) => $branches->whereKey($branchId))
            ->pluck('code');

        if ($invalid->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'account_id' => 'حسابات غير متاحة لفرع القيد: ' . $invalid->implode(', '),
        ]);
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
            $detailBranchIds = $request['branch_id'] ?? null;
            $data = [
                "type" => $request['account_type'][$key],
                "debtor_value" => $request['debtor_value'][$key],
                "creditor_value" => $request['creditor_value'][$key],
                "statement" => $request['statement_account'][$key] ?? '-',
                "account_id" => $accountId,
                "cost_center_id" => $request['cost_center_id'][$key] ?? '',
                "branch_id" => is_array($detailBranchIds) ? ($detailBranchIds[$key] ?? null) : null,
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
        $data['branch_id'] = $constraint->branch_id;
        $this->assertAccountsAssignableToBranch($data['account_id'] ?? [], $constraint->branch_id);
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
            $accountIds = $this->accountStatementAccountIds((int) $dataSearch["account_id"]);
            $customDate = $dataSearch["from_date"];  // Replace with your desired date
            return  BranchReportFilter::apply(DB::table('fn_constraint_details'), 'fn_constraint_details')->select(\DB::raw('SUM(fn_constraint_details.debtor_value) as sum_debtor_value, fn_accounts.status ,
                                           SUM(fn_constraint_details.creditor_value) as sum_creditor_value'))
                                        ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
                                        ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
                                        ->whereNull('fn_constraint_details.deleted_at')
                                        ->whereNull('fn_constraints.deleted_at')
                                        ->whereIn('fn_constraint_details.account_id', $accountIds)
                                        ->where('fn_constraint_details.date', '<', $customDate)
                                        ->when(!empty($dataSearch['supplier_workers']) && filled($dataSearch['worker_search'] ?? null), function ($query) use ($dataSearch) {
                                            $query->addWhereExistsQuery($this->supplierWorkerQuery($dataSearch['worker_search']));
                                        })
                                        ->first();
        }
        return null;
    }
    public function filterAccount($dataSearch, bool $paginate = false)
    {
        $accountIds = $this->accountStatementAccountIds((int) $dataSearch['account_id']);

        $accountStatment = BranchReportFilter::apply(DB::table('fn_constraint_details'), 'fn_constraint_details')
            ->select([
                'fn_constraint_details.account_id',
                'fn_accounts.name as account_name',
                'fn_accounts.code as account_code',
                'fn_constraint_details.date',
                'fn_constraint_details.statement',
                'fn_constraint_details.debtor_value',
                'fn_constraint_details.creditor_value',
                'fn_accounts.status',
                'fn_constraints.type as constraints_type',
                'fn_constraint_details.constraint_id',
                'fn_constraint_details.id as constraint_detail_id',
                'fn_constraint_details.type as movement_type',
                'fn_constraints.code as constraint_code',
                'admins.name',
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->leftJoin('admins', 'admins.id', '=', 'fn_constraints.created_by_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->whereIn('fn_constraint_details.account_id', $accountIds)
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
            ->orderByRaw('COALESCE(fn_constraints.code, fn_constraints.id)')
            ->orderBy('fn_constraints.id')
            ->orderBy('fn_constraint_details.id');

        if (!empty($dataSearch['supplier_workers'])) {
            $accountStatment
                ->selectSub($this->supplierWorkerQuery()->selectRaw("COALESCE(NULLIF(statement_maid.name, ''), statement_info.manual_maid_name)")->limit(1), 'worker_name')
                ->selectSub($this->supplierWorkerQuery()->selectRaw("COALESCE(NULLIF(statement_maid.passport_num, ''), statement_info.manual_maid_passport_num)")->limit(1), 'worker_passport');
            if (filled($dataSearch['worker_search'] ?? null)) {
                $accountStatment->addWhereExistsQuery($this->supplierWorkerQuery($dataSearch['worker_search']));
            }
        }

        return $paginate ? StatementPaginator::fromQuery($accountStatment) : $accountStatment->get();
    }

    /** Correlated lookups preserve accounting row counts and totals. */
    private function supplierWorkerQuery(?string $search = null)
    {
        $query = DB::table('contracts as statement_contract')
            ->leftJoin('maids as statement_maid', 'statement_maid.id', '=', 'statement_contract.maid_id')
            ->leftJoin('contract_infos as statement_info', 'statement_info.contract_id', '=', 'statement_contract.id')
            ->where(function ($query) {
                $query->whereColumn('statement_contract.external_office_cost_constraint_id', 'fn_constraints.id')
                    ->orWhereExists(function ($invoice) {
                        $invoice->selectRaw('1')->from('invoices as statement_invoice')
                            ->whereColumn('statement_invoice.contract_id', 'statement_contract.id')
                            ->whereColumn('statement_invoice.external_office_cost_constraint_id', 'fn_constraints.id');
                    });
            })
            ->orderBy('statement_contract.id');

        if (filled($search)) {
            $query->where(function ($query) use ($search) {
                $term = '%' . trim($search) . '%';
                $query->whereRaw("COALESCE(NULLIF(statement_maid.name, ''), statement_info.manual_maid_name) LIKE ?", [$term])
                    ->orWhereRaw("COALESCE(NULLIF(statement_maid.passport_num, ''), statement_info.manual_maid_passport_num) LIKE ?", [$term]);
            });
        }

        return $query;
    }

    public function filterConstraints($dataSearch, ?array $constraintIds = null)
    {
        $accountIds = $this->accountStatementAccountIds((int) $dataSearch['account_id']);
        $otherAccounts = $this->model->select([
            'fn_constraints.id',
        ])
        ->join('fn_constraint_details', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
        ->whereNull('fn_constraint_details.deleted_at')
        ->whereIn('fn_constraint_details.account_id', $accountIds)
        ->when($dataSearch['type'] != 'both', function ($q) use ($dataSearch) {
            return $q->where('fn_constraint_details.type', $dataSearch['type']);
        })
        ->when($dataSearch['from_date'] != '', function ($q) use ($dataSearch) {
            return $q->whereDate('fn_constraint_details.date', '>=', $dataSearch['from_date']);
        })
        ->when($dataSearch['to_date'] != '', function ($q) use ($dataSearch) {
            return $q->whereDate('fn_constraint_details.date', '<=', $dataSearch['to_date']);
        })
        ->when($constraintIds !== null, fn ($q) => $q->whereIn('fn_constraints.id', $constraintIds))
        ->distinct()
        ->with(['accounts' => function ($query) use ($accountIds) {
            return $query->whereNotIn('account_id', $accountIds);
        }])
        ->get();
        $result = [];
        foreach ($otherAccounts as $one) {
            $result[$one->id]['accounts'] = $one->accounts;
        }
        return $result;
    }

    /**
     * Resolve counter accounts for each statement row rather than for the
     * entire journal entry. In a compound entry only the lines on the opposite
     * side are accounting counterparts to the selected movement.
     */
    public function filterCounterAccountsForStatement($statementRows): array
    {
        $rows = $statementRows instanceof StatementPaginator
            ? $statementRows->getCollection()
            : collect($statementRows);

        $rows = $rows
            ->filter(fn ($row) => filled($row->constraint_id ?? null) && filled($row->constraint_detail_id ?? null))
            ->values();

        if ($rows->isEmpty()) {
            return [];
        }

        $details = BranchReportFilter::apply(DB::table('fn_constraint_details'), 'fn_constraint_details')
            ->select([
                'fn_constraint_details.id',
                'fn_constraint_details.constraint_id',
                'fn_constraint_details.account_id',
                'fn_constraint_details.type',
                'fn_constraint_details.debtor_value',
                'fn_constraint_details.creditor_value',
                'fn_accounts.code',
                'fn_accounts.name',
            ])
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->whereNull('fn_constraint_details.deleted_at')
            ->whereNull('fn_constraints.deleted_at')
            ->whereIn('fn_constraint_details.constraint_id', $rows->pluck('constraint_id')->unique()->all())
            ->orderBy('fn_constraint_details.id')
            ->get()
            ->groupBy('constraint_id');

        $result = [];

        foreach ($rows as $row) {
            $rowIsDebtor = round((float) ($row->debtor_value ?? 0), 2) > 0
                || (
                    round((float) ($row->creditor_value ?? 0), 2) === 0.0
                    && ($row->movement_type ?? null) === 'debtor'
                );

            $counterAccounts = collect($details->get($row->constraint_id, []))
                ->filter(function ($detail) use ($row, $rowIsDebtor) {
                    if ((int) $detail->id === (int) $row->constraint_detail_id) {
                        return false;
                    }

                    return $rowIsDebtor
                        ? round((float) $detail->creditor_value, 2) > 0
                        : round((float) $detail->debtor_value, 2) > 0;
                })
                ->groupBy('account_id')
                ->map(function ($accountLines) use ($rowIsDebtor) {
                    $account = $accountLines->first();

                    return (object) [
                        'id' => (int) $account->account_id,
                        'code' => $account->code,
                        'name' => $account->name,
                        'amount' => round((float) $accountLines->sum(
                            $rowIsDebtor ? 'creditor_value' : 'debtor_value'
                        ), 2),
                    ];
                })
                ->values();

            $result['details'][(int) $row->constraint_detail_id]['accounts'] = $counterAccounts;
        }

        return $result;
    }

    private function accountStatementAccountIds(int $accountId): array
    {
        if ($accountId <= 0) {
            return [];
        }

        $ids = [$accountId];
        $frontier = [$accountId];

        while (!empty($frontier)) {
            $children = Account::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->diff($ids)
                ->values()
                ->all();

            if (empty($children)) {
                break;
            }

            $ids = array_values(array_unique(array_merge($ids, $children)));
            $frontier = $children;
        }

        return $ids;
    }

    public function getIndexData() {
        return $this->model->query()
            ->without('admin')
            ->with([
                'constraintDetails.account',
                'bond',
                'transfer_entry',
                'rentalInvoice',
                'recruitmentInvoice',
                'rentalInvoiceConstraint',
                'rentalReceiptInvoice',
                'arrivalRevenueInvoice',
                'externalOfficeCostInvoice',
                'ingazDelegationCostInvoice',
            ])
            ->approved()
            ->orderByDesc('fn_constraints.date')
            ->orderByRaw('CASE WHEN fn_constraints.code IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('fn_constraints.code')
            ->orderByDesc('fn_constraints.id');
    }

    public function getPendingApprovalData()
    {
        return $this->model->query()
            ->without('admin')
            ->with([
                'constraintDetails.account',
                'bond',
                'transfer_entry',
                'rentalInvoice',
                'recruitmentInvoice',
                'rentalInvoiceConstraint',
                'rentalReceiptInvoice',
                'arrivalRevenueInvoice',
                'externalOfficeCostInvoice',
                'ingazDelegationCostInvoice',
                'created_by',
            ])
            ->pendingApproval()
            ->orderByDesc('fn_constraints.date')
            ->orderByRaw('CASE WHEN fn_constraints.code IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('fn_constraints.code')
            ->orderByDesc('fn_constraints.id');
    }

    public function approveConstraint(int $id, ?int $adminId = null): Model
    {
        $constraint = $this->find($id);
        $constraint->approve($adminId);
        $this->logConstraintActivity($constraint, 'approved', __('finance::finance.constraint_approved_log_description'));

        return $constraint->refresh();
    }

    /**
     * حذف قيد يدوي. القيود الآلية (المرتبطة بفاتورة/تحويل/استرداد/...) تُرفض
     * برسالة عربية؛ `$force = true` مخصص لمسارات العكس الداخلية التي تملك القيد.
     */
    public function deleteConstraint(int $id, bool $force = false): Model
    {
        if (! $force && ConstraintReferenceGuard::isReferenced($id)) {
            throw ValidationException::withMessages([
                'constraint' => ConstraintReferenceGuard::describe($id),
            ]);
        }

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
