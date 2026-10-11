<?php

namespace Nami\Finance\Services;

use App\Services\BranchAccessService;
use App\Support\BranchReportFilter;
use App\Support\BranchSharingSettings;
use App\Support\BranchQueryContext;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\OperationTypeEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConstraintDetails;

class AccountService extends MainService
{

    function __construct(
        Account $model,
        protected BankAccountService $bankAccountService,
        private BranchAccessService $branchAccess,
    )
    {
        $this->model = $model;
    }


    public function getAccountTypes(): array
    {
        return [
            (object)["id" => 'debtor', 'name' => "مدين"],// debtor
            (object)["id" => 'creditor', 'name' => "دائن"],// creditor
        ];
    }

    public function getAccountTypesList()
    {
        return AccountTypesEnum::cases();
    }

    public function getChildType($parent): AccountTypesEnum
    {
        $typeMap = AccountTypesEnum::cases();
        return (isset($typeMap[$parent->type->value])) ? $typeMap[$parent->type->value] : AccountTypesEnum::NormalAccount;
    }


    public function storeAccount($data)
    {
        return DB::transaction(function () use ($data) {
            [$branchScopePresent, $branchIds] = $this->extractBranchAssignmentInput($data);

            if ($data["parent_id"] == 'without') {
                $data["level"] = 1;
                $data["parent_id"] = null;
                $data["code"] = $this->nextAvailableRootCodeForUpdate();
            } else {
                $parent = $this->model->newQuery()
                    ->whereKey($data["parent_id"])
                    ->lockForUpdate()
                    ->firstOrFail();

                $data["level"] = $parent->level + 1;
                $data["code"] = $this->nextAvailableChildCodeForUpdate($parent);
                $data["type"] = $data["type"] ?? $this->getChildType($parent);
                $data["statement_type"] = $data["statement_type"] ?? $parent->statement_type;
            }

            $account = $this->store($data);
            $this->syncBranchAssignmentsFromInput($account, $branchScopePresent, $branchIds, $account->branch_id ?? null);

            return $account;
        });
    }


    public function getLevelParentById($id)
    {
        return $this->model->where('parent_id', null)->where('id', '<>', $id)->latest()->first()->level ?? 0;
    }

    public function getLastChildByParentID($id)
    {
        return $this->model->where('parent_id', $id)->latest()->first();
    }

    public function updateAccount(int $id, $data, $getLevelParentById, $getLastChildByParentID)
    {
        return DB::transaction(function () use ($id, $data) {
            [$branchScopePresent, $branchIds] = $this->extractBranchAssignmentInput($data);

            $obj = $this->model->newQuery()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldParentId = $obj->parent_id;
            $oldCode = (int) $obj->code;
            $newParentId = $data["parent_id"] == 'without' ? null : (int) $data["parent_id"];

            if ($newParentId === null) {
                $data["level"] = 1;
                $data["parent_id"] = null;
                $data["statement_type"] = $data["statement_type"] ?? $obj->statement_type;

                if ($oldParentId !== null) {
                    $data["code"] = $this->nextAvailableRootCodeForUpdate($obj->id);
                }
            } else {
                $parent = $this->model->newQuery()
                    ->whereKey($newParentId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $data["level"] = $parent->level + 1;
                $data["statement_type"] = $data["statement_type"] ?? $parent->statement_type;

                if ((int) $oldParentId !== $newParentId) {
                    $data["code"] = $this->nextAvailableChildCodeForUpdate($parent, $obj->id);
                }
            }

            $data["type"] = $data["type"] ?? $obj->type;
            $obj->update($data);
            $this->syncBranchAssignmentsFromInput($obj, $branchScopePresent, $branchIds, $data['branch_id'] ?? $obj->branch_id ?? null);

            if ((int) $obj->code !== $oldCode) {
                $this->updateChildrenCode($obj->fresh(['children']));
            }

            return true;
        });
    }

    public function updateChildrenCode($account)
    {
        if ($account->children) {
            $nextCode = ((int) $account->code) * 100;

            foreach ($account->children->sortBy('code')->values() as $child) {
                $child->update(['code' => $nextCode++]);
                $this->updateChildrenCode($child->fresh(['children']));
            }
        }
    }

    public function updateAccountGuideDetails(int $id, array $data): void
    {
        DB::transaction(function () use ($id, $data) {
            [$branchScopePresent, $branchIds] = $this->extractBranchAssignmentInput($data);
            $account = $this->model->newQuery()->whereKey($id)->lockForUpdate()->firstOrFail();
            $account->update(['name' => $data['name']]);
            if (BranchSharingSettings::accountsPerBranch() && $branchScopePresent) {
                $account->branches()->sync($branchIds);
            }
        });
    }

    public function getAccountTree()
    {
        // فرع التقرير لا العزل التلقائي فقط: المحاسب الذي يرى كل الفروع يختار
        // الفرع من رأس الشاشة، فتنفصل الشجرة وأرصدتها مثل بقية التقارير المالية.
        $branchId = BranchQueryContext::reportBranchId();

        if ($branchId === null && $this->branchAccess->enabled()) {
            $adminBranchId = $this->branchAccess->adminBranchId();
            if ($adminBranchId !== null && ! $this->branchAccess->canViewAllBranches()) {
                $branchId = $adminBranchId;
            }
        }

        $usePivotAssignments = BranchSharingSettings::accountsPerBranch();
        $includeUnassigned = $branchId !== null
            && ! $usePivotAssignments
            && $this->branchAccess->isMainBranchId($branchId);
        $filterAtQueryLevel = $branchId !== null
            && ($usePivotAssignments || $this->branchAccess->isMainBranchId($branchId));

        $accounts = $this->model
            ->select([
                'id',
                'name',
                'code',
                'type',
                'status',
                'level',
                'parent_id',
                'operation_type',
                'statement_type',
                'created_by_id',
                'branch_id',
            ])
            ->when($filterAtQueryLevel, fn ($query) => $this->filterAccountAvailability($query, $branchId, $includeUnassigned))
            // العدّاد هو ما يقرر ظهور رابط كشف الحساب. بلا نفس شرط الفرع يظهر
            // الرابط لحساب كل حركته في فرع آخر، فيُفتح كشف فارغ.
            ->withCount(['constraintDetails' => fn ($query) => BranchReportFilter::apply($query, 'fn_constraint_details')])
            ->orderBy('code')
            ->orderBy('id')
            ->get();

        if ($branchId !== null && ! BranchSharingSettings::accountsPerBranch() && ! $this->branchAccess->isMainBranchId($branchId)) {
            $accounts = $this->treeAccountsForBranch($accounts, $branchId);
        }

        $accountsById = $accounts->keyBy('id');
        $childrenByParent = [];
        $roots = new EloquentCollection();

        foreach ($accounts as $account) {
            $this->normalizeAccountEnumValuesForTree($account);
            $account->setRelation(
                'constraintDetails',
                (int) ($account->constraint_details_count ?? 0) > 0
                    ? new EloquentCollection([new ConstraintDetails()])
                    : new EloquentCollection()
            );
            $account->setRelation('childrenRecursive', new EloquentCollection());
        }

        foreach ($accounts as $account) {
            $parentId = $account->parent_id ? (int) $account->parent_id : null;

            if (!$parentId || !$accountsById->has($parentId) || $this->accountTreeHasParentCycle($account, $accountsById)) {
                $roots->push($account);
                continue;
            }

            $childrenByParent[$parentId][] = $account;
        }

        $this->attachAccountTreeChildren($roots, $childrenByParent);

        foreach ($roots as $account) {
            $account->all_children_ids = $this->accountTreeDescendantIds($account);
        }

        $data['constraintDetails'] = $this->getConDetails();
        $data['accounts'] = $roots;
        return $data;
    }

    private function normalizeAccountEnumValuesForTree(Account $account): void
    {
        $validTypes = array_map(static fn ($case) => $case->value, AccountTypesEnum::cases());
        $type = $account->getRawOriginal('type');
        if (!is_numeric($type) || !in_array((int) $type, $validTypes, true)) {
            $account->setAttribute('type', AccountTypesEnum::NormalAccount->value);
        }

        $status = (string) $account->getRawOriginal('status');
        if (!in_array($status, ['debtor', 'creditor'], true)) {
            $account->setAttribute('status', 'debtor');
        }
    }

    private function accountTreeHasParentCycle(Account $account, EloquentCollection $accountsById): bool
    {
        $seen = [(int) $account->id => true];
        $parentId = $account->parent_id ? (int) $account->parent_id : null;

        while ($parentId) {
            if (isset($seen[$parentId])) {
                return true;
            }

            $seen[$parentId] = true;
            $parent = $accountsById->get($parentId);
            if (!$parent) {
                return false;
            }

            $parentId = $parent->parent_id ? (int) $parent->parent_id : null;
        }

        return false;
    }

    private function treeAccountsForBranch(EloquentCollection $accounts, int $branchId): EloquentCollection
    {
        $accountsById = $accounts->keyBy('id');
        $visibleIds = [];

        foreach ($accounts as $account) {
            if ((int) ($account->branch_id ?? 0) !== $branchId) {
                continue;
            }

            $current = $account;
            $seen = [];

            while ($current) {
                $currentId = (int) $current->id;
                if (isset($seen[$currentId])) {
                    break;
                }

                $seen[$currentId] = true;
                $visibleIds[$currentId] = true;

                $parentId = $current->parent_id ? (int) $current->parent_id : null;
                $current = $parentId ? $accountsById->get($parentId) : null;
            }
        }

        return $accounts
            ->filter(fn ($account) => isset($visibleIds[(int) $account->id]))
            ->values();
    }

    private function attachAccountTreeChildren(EloquentCollection $accounts, array $childrenByParent): void
    {
        foreach ($accounts as $account) {
            $children = new EloquentCollection($childrenByParent[(int) $account->id] ?? []);
            $this->attachAccountTreeChildren($children, $childrenByParent);
            $account->setRelation('childrenRecursive', $children);
        }
    }

    private function accountTreeDescendantIds(Account $account): array
    {
        $ids = [];

        foreach ($account->childrenRecursive as $child) {
            $ids[] = (int) $child->id;
            $child->all_children_ids = $this->accountTreeDescendantIds($child);
            $ids = array_merge($ids, $child->all_children_ids);
        }

        return $ids;
    }

    protected function addDescendantIdsToAccount($account)
    {
        // Get all descendant IDs for this account
        $allDescendantIds = $this->getAllDescendantIds($account);

        // Add the descendant IDs to the current account
        $account->all_children_ids = $allDescendantIds;

        // Recursively add descendant IDs to each child
        if (!empty($account->childrenRecursive)) {
            foreach ($account->childrenRecursive as $child) {
                $this->addDescendantIdsToAccount($child);
            }
        }
    }

    protected function getAllDescendantIds($account, &$allDescendantIds = [])
    {
        if (!empty($account->childrenRecursive)) {
            foreach ($account->childrenRecursive as $child) {
                $allDescendantIds[] = $child->id;
                $this->getAllDescendantIds($child, $allDescendantIds);
            }
        }

        return $allDescendantIds;
    }

    public function getConDetails()
    {
        // الـ global scope يعزل موظف الفرع، لكنه لا يرى الاختيار اليدوي لمن
        // يملك رؤية كل الفروع — وهذا هو الشرط الذي يجعل رصيد الشجرة يطابق
        // كشف الحساب وميزان المراجعة لنفس الفرع المختار.
        return BranchReportFilter::apply(ConstraintDetails::query(), 'fn_constraint_details')
            ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
            ->whereNull('fn_constraints.deleted_at')
            ->select(
                'fn_constraint_details.account_id',
                DB::raw('SUM(fn_constraint_details.debtor_value) as debtor_value'),
                DB::raw('SUM(fn_constraint_details.creditor_value) as creditor_value')
            )
            ->groupBy('fn_constraint_details.account_id')
            ->get()
            ->keyBy('account_id')
            ->map(function ($constraint) {
                return [
                    'debtor_value' => (float) ($constraint->debtor_value ?? 0),
                    'creditor_value' => (float) ($constraint->creditor_value ?? 0),
                ];
            });
    }

    public function findByType($type)
    {
        return $this->model->where("type", $type)->first();
    }

    public function getValidParents()
    {
        $levelConstraints = [1 => 10, 2 => 100, 3 => 1000, 4 => 10000, 5 => 100000];

        // Retrieve all parents with their children count
        $parents = $this->model->withCount('children')
            ->where('operation_type', "!=", 2)
            ->whereIn('level', array_keys($levelConstraints))
            ->get();

        // Filter the parents based on the constraints
        return $parents->filter(function ($parent) use ($levelConstraints) {
            return $parent->children_count < $levelConstraints[$parent->level];
        });
    }

    public function getValidParentsWhereNotIn4Types()
    {
        $excludedTypes = [
            AccountTypesEnum::Bank->value,
            AccountTypesEnum::Banks->value,
            AccountTypesEnum::BankName->value,
            AccountTypesEnum::Box->value,
            AccountTypesEnum::Boxs->value,
            AccountTypesEnum::Clients->value,
            AccountTypesEnum::Client->value,
            AccountTypesEnum::Suppliers->value,
            AccountTypesEnum::Supplier->value,
        ];

        $levelConstraints = [1 => 10, 2 => 100, 3 => 1000, 4 => 10000, 5 => 100000];

        return $this->model->withCount('children')
            ->where('operation_type', '!=', 2)
            ->whereNotIn('type', $excludedTypes)
            ->whereIn('level', array_keys($levelConstraints))
            ->get()
            ->filter(fn($parent) => $parent->children_count < $levelConstraints[$parent->level]);
    }

    public function getChildCode($id, $childPluckCode, $parentPluckCode)
    {
        if ($id != 'without') {
            $parent = $this->find($id);
            return $this->firstAvailableCode(((int) $parent->code) * 100, $childPluckCode);
        }

        return $this->firstAvailableCode(1, $parentPluckCode);
    }

    protected function firstAvailableCode(int $start, $codes): int
    {
        foreach ($codes as $code) {
            $code = (int) $code;
            if ($code < $start) {
                continue;
            }
            if ($code !== $start) {
                return $start;
            }
            $start++;
        }

        return $start;
    }

    protected function nextAvailableRootCodeForUpdate(?int $excludeId = null): int
    {
        $query = $this->model->newQuery()
            ->whereNull('parent_id')
            ->when($excludeId, fn ($query) => $query->where('id', '<>', $excludeId))
            ->orderBy('code')
            ->lockForUpdate();

        return $this->firstAvailableCode(1, $query->pluck('code'));
    }

    protected function nextAvailableChildCodeForUpdate(Account $parent, ?int $excludeId = null): int
    {
        $query = $this->model->newQuery()
            ->where('parent_id', $parent->id)
            ->when($excludeId, fn ($query) => $query->where('id', '<>', $excludeId))
            ->orderBy('code')
            ->lockForUpdate();

        return $this->firstAvailableCode(((int) $parent->code) * 100, $query->pluck('code'));
    }

    public function childPluckCode($id)
    {
        return $this->model->where('parent_id', $id)->orderBy('code')->pluck('code');
    }

    public function parentPluckCode()
    {
        return $this->model->where('parent_id', null)->orderBy('code')->pluck('code');
    }

    public function parentAccounts($parent_id)
    {
        return $this->model->where('parent_id', $parent_id)->get();
    }

    public function getAccountWithNoChild($type = null, ?int $branchId = null)
    {
        if (! BranchSharingSettings::accountsPerBranch()) {
            return $this->model
                ->doesntHave('children')
                ->when($type, function ($query) use ($type) {
                    return $query->where('type', $type);
                })
                ->get();
        }

        $includeUnassigned = $branchId !== null
            && $this->branchAccess->isMainBranchId($branchId);

        return $this->model
            ->when(
                $branchId !== null,
                fn ($query) => $query->whereDoesntHave(
                    'children',
                    fn ($children) => $this->filterAccountAvailability($children, $branchId, $includeUnassigned)
                ),
                fn ($query) => $query->doesntHave('children')
            )
            ->when($branchId !== null, fn ($query) => $this->filterAccountAvailability($query, $branchId, $includeUnassigned))
            ->when($type, function ($query) use ($type) {
                return $query->where('type', $type);
            })
            ->get();
    }

    private function filterAccountAvailability($query, int $branchId, bool $includeUnassigned = false)
    {
        if (BranchSharingSettings::accountsPerBranch()) {
            return $query->assignableTo($branchId);
        }

        return $this->visibleForBranch($query, $branchId, $includeUnassigned);
    }

    private function extractBranchAssignmentInput(array &$data): array
    {
        $branchScopePresent = array_key_exists('account_branch_scope_present', $data);
        $branchIds = collect($data['branch_ids'] ?? [])
            ->filter(fn ($branchId) => (int) $branchId > 0)
            ->map(fn ($branchId) => (int) $branchId)
            ->unique()
            ->values()
            ->all();

        unset($data['account_branch_scope_present'], $data['branch_ids']);

        return [$branchScopePresent, $branchIds];
    }

    private function syncBranchAssignmentsFromInput(Account $account, bool $branchScopePresent, array $branchIds, mixed $legacyBranchId): void
    {
        if (! BranchSharingSettings::accountsPerBranch()) {
            return;
        }

        if ($branchScopePresent) {
            $account->branches()->sync($branchIds);
            return;
        }

        $this->syncBranchAssignment($account, $legacyBranchId);
    }

    private function syncBranchAssignment(Account $account, mixed $branchId): void
    {
        $branchId = (int) ($branchId ?? 0);

        if ($branchId <= 0 || ! BranchSharingSettings::accountsPerBranch()) {
            return;
        }

        $account->branches()->syncWithoutDetaching([$branchId]);
    }

    private function branchesForAccountAssignment()
    {
        return Branch::query()
            ->active()
            ->ordered()
            ->get(['id', 'name_ar', 'name_en', 'code']);
    }

    /** الفرع الرئيسي يرى الحسابات غير المنسوبة، وباقي الفروع ترى حساباتها فقط. */
    private function visibleForBranch($query, int $branchId, bool $includeUnassigned = true)
    {
        $branchColumn = $query->getModel()->getTable() . '.branch_id';

        if (! $includeUnassigned) {
            return $query->where($branchColumn, $branchId);
        }

        return $query->where(function ($query) use ($branchColumn, $branchId) {
            $query->whereNull($branchColumn)->orWhere($branchColumn, $branchId);
        });
    }

    public function getSupplierStatementAccounts()
    {
        return $this->model
            ->doesntHave('children')
            ->where(function ($query) {
                $query->where('type', AccountTypesEnum::Supplier->value)
                    ->orWhereHas('parent', function ($parentQuery) {
                        $parentQuery->where('code', 221);
                    });
            })
            ->orderBy('code')
            ->get();
    }

    public function getByType($type)
    {
        return $this->model->where("type", $type)->get();
    }

    public function getByTypes(array $types)
    {
        return $this->model->whereIn("type", $types)->get();
    }

    public function getBoxsAndBanksAccounts()
    {
        $types = [
            AccountTypesEnum::Bank,
            AccountTypesEnum::Box,
            AccountTypesEnum::ElectronicWallet
        ];
        return $this->model->whereIn("type", $types)->get();
    }

    public function paginate($size = 10)
    {
        return $this->model->paginate($size);
    }

    public function getLevels()
    {
        return $this->model
            ->select('level')
            ->distinct()
            ->orderBy('level')
            ->pluck('level')
            ->toArray();
    }

    public function getAccountsByLevel($level)
    {
        return $this->model
            ->where('level', $level)
            ->with('childrenRecursive', function ($q) {
                $q->select('id', 'parent_id');
            })
            ->latest()
            ->get();
    }

    public function getDescendantsByIds(array $parentIds)
    {
        return $this->model->whereIn('parent_id', $parentIds)
            ->get();
    }

    public function getOperationTypesList()
    {
        return OperationTypeEnum::cases();
    }

    public function getDatatableData(): Builder
    {
        return $this->model->query()
            ->select([
                'id',
                'name',
                'code',
                'type',
                'status',
                'level',
                'parent_id',
                'operation_type',
            ])
            ->with([
                'parent:id,name',
            ])
            ->withCount('constraintDetails')
            ->when(request()->filled('main_account'), function ($query) {
                $query->where('parent_id', request('main_account'));
            })
            ->when(request()->filled('type'), function ($query) {
                $query->where('type', request('type'));
            })
            ->when(request()->filled('status'), function ($query) {
                $query->where('status', request('status'));
            })
            ->when(request()->filled('level'), function ($query) {
                $query->where('level', request('level'));
            })
            ->when(request()->filled('keyword'), function ($query) {
                $keyword = trim((string) request('keyword'));
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('code', 'ASC')
            ->orderBy('id', 'DESC');
    }

    public function balanceAccountsByTypeNoNormal($search)
    {
        return DB::table('fn_accounts as account')
            ->leftJoin('fn_constraint_details as c_details', function ($join) {
                $join->on('account.id', '=', 'c_details.account_id')
                    ->whereNull('c_details.deleted_at');
            })
            ->leftJoin('fn_constraints as constraints', function ($join) {
                $join->on('constraints.id', '=', 'c_details.constraint_id')
                    ->whereNull('constraints.deleted_at');
            })
            ->select(
                'account.id',
                'account.code',
                'account.status',
                'account.name',
                DB::raw("CASE
            WHEN account.status = 'debtor' THEN COALESCE(SUM(CASE WHEN constraints.id IS NOT NULL THEN c_details.debtor_value ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN constraints.id IS NOT NULL THEN c_details.creditor_value ELSE 0 END), 0)
            ELSE COALESCE(SUM(CASE WHEN constraints.id IS NOT NULL THEN c_details.creditor_value ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN constraints.id IS NOT NULL THEN c_details.debtor_value ELSE 0 END), 0)
        END AS balance")
            )
            ->when(isset($search['from_date']) && $search['from_date'], function ($query) use ($search) {
                return $query->where('c_details.date', '>=', $search['from_date']);
            })->when(isset($search['from_date']) && $search['to_date'], function ($query) use ($search) {
                return $query->where('c_details.date', '<=', $search['to_date']);
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('fn_accounts as children')
                    ->whereColumn('children.parent_id', 'account.id');
            })
            ->having('balance', '<', 0)
            ->groupBy('account.id', 'account.code', 'account.status', 'account.name')
            ->get();
    }

    public function balanceAccountsByType($type, $search)
    {
        return DB::table('fn_accounts as account')
            ->leftJoin('fn_constraint_details as c_details', function ($join) {
                $join->on('account.id', '=', 'c_details.account_id')
                    ->whereNull('c_details.deleted_at');
            })
            ->leftJoin('fn_constraints as constraints', function ($join) {
                $join->on('constraints.id', '=', 'c_details.constraint_id')
                    ->whereNull('constraints.deleted_at');
            })
            ->select(
                'account.id',
                'account.code',
                'account.status',
                'account.name',
                DB::raw("CASE
            WHEN account.status = 'debtor' THEN COALESCE(SUM(CASE WHEN constraints.id IS NOT NULL THEN c_details.debtor_value ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN constraints.id IS NOT NULL THEN c_details.creditor_value ELSE 0 END), 0)
            ELSE COALESCE(SUM(CASE WHEN constraints.id IS NOT NULL THEN c_details.creditor_value ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN constraints.id IS NOT NULL THEN c_details.debtor_value ELSE 0 END), 0)
        END AS balance")
            )
            ->where('account.status', $type)
            ->when(isset($search['from_date']) && $search['from_date'], function ($query) use ($search) {
                return $query->where('c_details.date', '>=', $search['from_date']);
            })->when(isset($search['from_date']) && $search['to_date'], function ($query) use ($search) {
                return $query->where('c_details.date', '<=', $search['to_date']);
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('fn_accounts as children')
                    ->whereColumn('children.parent_id', 'account.id');
            })
            ->groupBy('account.id', 'account.code', 'account.status', 'account.name')
            ->get();
    }

    /**
     * Return posting accounts whose balance is actually on the requested side.
     *
     * The account status is its normal accounting nature, not its current balance.
     * Reports based only on that status omit debit-nature accounts that currently
     * have a credit balance and include credit-nature accounts with zero/debit
     * balances. This query derives the side from the posted movements instead.
     */
    public function balanceAccountsByActualSide(string $side, array $search = [])
    {
        $side = $side === 'debtor' ? 'debtor' : 'creditor';
        $debtorTotal = 'movements.debtor_total';
        $creditorTotal = 'movements.creditor_total';
        $balanceExpression = $side === 'debtor'
            ? "{$debtorTotal} - {$creditorTotal}"
            : "{$creditorTotal} - {$debtorTotal}";

        $movements = DB::table('fn_constraint_details as detail')
            ->join('fn_constraints as constraint', function ($join) {
                $join->on('constraint.id', '=', 'detail.constraint_id')
                    ->whereNull('constraint.deleted_at');
            })
            ->whereNull('detail.deleted_at')
            ->when(!empty($search['from_date']), function ($query) use ($search) {
                $query->where('detail.date', '>=', $search['from_date']);
            })
            ->when(!empty($search['to_date']), function ($query) use ($search) {
                $query->where('detail.date', '<=', $search['to_date']);
            })
            ->groupBy('detail.account_id')
            ->selectRaw('detail.account_id, SUM(detail.debtor_value) AS debtor_total, SUM(detail.creditor_value) AS creditor_total');

        return DB::table('fn_accounts as account')
            ->leftJoin('fn_accounts as parent', 'parent.id', '=', 'account.parent_id')
            ->joinSub($movements, 'movements', 'movements.account_id', '=', 'account.id')
            ->select([
                'account.id',
                'account.code',
                'account.status',
                'account.name',
                'account.level',
                'parent.code as parent_code',
                'parent.name as parent_name',
            ])
            ->selectRaw("{$debtorTotal} AS debtor_total, {$creditorTotal} AS creditor_total")
            ->selectRaw("{$balanceExpression} AS balance")
            ->when(!empty($search['keyword']), function ($query) use ($search) {
                $keyword = trim((string) $search['keyword']);
                $query->where(function ($query) use ($keyword) {
                    $query->where('account.name', 'like', "%{$keyword}%")
                        ->orWhere('account.code', 'like', "%{$keyword}%")
                        ->orWhere('parent.name', 'like', "%{$keyword}%")
                        ->orWhere('parent.code', 'like', "%{$keyword}%");
                });
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('fn_accounts as children')
                    ->whereColumn('children.parent_id', 'account.id');
            })
            ->whereRaw("{$balanceExpression} > ?", [0.005])
            ->orderBy('account.code')
            ->orderBy('account.id')
            ->get();
    }

    public function balanceAccountsDifferedFromNature(array $search = [])
    {
        $movements = DB::table('fn_constraint_details as detail')
            ->join('fn_constraints as constraint', function ($join) {
                $join->on('constraint.id', '=', 'detail.constraint_id')
                    ->whereNull('constraint.deleted_at');
            })
            ->whereNull('detail.deleted_at')
            ->when(!empty($search['from_date']), function ($query) use ($search) {
                $query->where('detail.date', '>=', $search['from_date']);
            })
            ->when(!empty($search['to_date']), function ($query) use ($search) {
                $query->where('detail.date', '<=', $search['to_date']);
            })
            ->groupBy('detail.account_id')
            ->selectRaw('detail.account_id, SUM(detail.debtor_value) AS debtor_total, SUM(detail.creditor_value) AS creditor_total');

        $balanceExpression = "CASE
            WHEN account.status = 'debtor' THEN movements.creditor_total - movements.debtor_total
            ELSE movements.debtor_total - movements.creditor_total
        END";

        return DB::table('fn_accounts as account')
            ->leftJoin('fn_accounts as parent', 'parent.id', '=', 'account.parent_id')
            ->joinSub($movements, 'movements', 'movements.account_id', '=', 'account.id')
            ->select([
                'account.id',
                'account.code',
                'account.status',
                'account.name',
                'account.level',
                'parent.code as parent_code',
                'parent.name as parent_name',
            ])
            ->selectRaw('movements.debtor_total, movements.creditor_total')
            ->selectRaw("{$balanceExpression} AS balance")
            ->selectRaw("CASE WHEN account.status = 'debtor' THEN 'creditor' ELSE 'debtor' END AS actual_status")
            ->when(!empty($search['keyword']), function ($query) use ($search) {
                $keyword = trim((string) $search['keyword']);
                $query->where(function ($query) use ($keyword) {
                    $query->where('account.name', 'like', "%{$keyword}%")
                        ->orWhere('account.code', 'like', "%{$keyword}%")
                        ->orWhere('parent.name', 'like', "%{$keyword}%")
                        ->orWhere('parent.code', 'like', "%{$keyword}%");
                });
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('fn_accounts as children')
                    ->whereColumn('children.parent_id', 'account.id');
            })
            ->whereRaw("{$balanceExpression} > ?", [0.005])
            ->orderBy('account.code')
            ->orderBy('account.id')
            ->get();
    }

    public function balanceconstraintsByType($type = null)
    {
        return DB::table('fn_constraint_details as d')
            ->rightJoin('fn_accounts as a', 'd.account_id', '=', 'a.id')
            ->leftJoin('fn_constraints as c', function ($join) {
                $join->on('c.id', '=', 'd.constraint_id')
                    ->whereNull('c.deleted_at');
            })
            ->select(
                'a.id',
                'a.code',
                'a.status',
                'a.name',
                DB::raw("CASE
            WHEN a.status = 'debtor' THEN COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN d.debtor_value ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN d.creditor_value ELSE 0 END), 0)
            ELSE COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN d.creditor_value ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN c.id IS NOT NULL THEN d.debtor_value ELSE 0 END), 0)
        END AS balance")
            )
            ->where('a.status', $type)
            ->groupBy('a.id', 'a.code', 'a.status', 'a.name')
            ->get();
    }

    public string $folderPath = "finance::finance.account";
    public string $mainRoute = "finance.accounts";

    public function getCreateView($request)
    {
        if ($request['parent_id']) {
            $parent = $this->model->findOrFail($request['parent_id']);
            $type = $parent->type->value;

            if ($type === AccountTypesEnum::BankName->value) {
                return redirect(route('finance.banks.edit', $parent->id));
            }

            $redirectRoutes = [
                AccountTypesEnum::Suppliers->value => 'finance.suppliers.create',
                AccountTypesEnum::Clients->value => 'finance.clients.create',
                AccountTypesEnum::Boxs->value => 'finance.boxes.create',
                AccountTypesEnum::Banks->value => 'finance.banks.create',
            ];

            if (isset($redirectRoutes[$type])) {
                return redirect(route($redirectRoutes[$type]));
            }
        }


        $returnHTML = view($this->folderPath . ".create")->with([
            "accountTypes" => $this->getAccountTypes(),
            "accounts" => $this->getValidParentsWhereNotIn4Types(),
            "types" => $this->getAccountTypesList(),
            "operationTypes" => $this->getOperationTypesList(),
            "storeRoute" => route($this->mainRoute . ".store"),
            "branches" => $this->branchesForAccountAssignment(),
            "accountBranchAssignmentsEnabled" => BranchSharingSettings::accountsPerBranch(),
        ])->render();

        return jsonSuccess(["html" => $returnHTML]);
    }

    public function getIncomeAccounts()
    {
        return $this->incomeStatementRootsQuery()
            ->orderBy('type', 'asc')
            ->with(['children', 'childrenRecursive' => function ($query) {
                $query->select('id', 'parent_id', 'type', 'status');
            }])
            ->get();
    }


    public function getIncomeReportLevels(): array
    {
        $roots = $this->incomeStatementRootsQuery()
            ->with('children.children.children.children.children')
            ->get();

        $maxDepth = 1;

        foreach ($roots as $root) {
            $maxDepth = max($maxDepth, $this->getMaxChildrenDepth($root));
        }

        return range(1, $maxDepth);
    }

    public function getIncomeAccountsChildrens($constraints_ids, ?int $level = null)
    {
        // Load all accounts with their nested children in one query
        $accounts = $this->incomeStatementRootsQuery()
            ->with('children.children.children.children.children') // Adjust depth as needed
            ->get();

        // Pre-load all constraint details for all accounts in one query
        $all_account_ids = $this->getAllAccountIds($accounts);
        $constraints_data = $this->getConstraintsData($all_account_ids, $constraints_ids);

        $main_list = [];
        foreach ($accounts as $account) {
            $list = [];
            $account_total_price = 0;

            if ($account->children->count() > 0) {
                foreach ($account->children as $children) {
                    $row = $this->buildIncomeAccountRow($children, $constraints_data);
                    $account_total_price += $row['total_price'];
                    $list[] = $row;
                }
            }

            $main_list[] = [
                'account' => $account,
                'report_type' => $this->incomeStatementRootType($account),
                'list' => $list,
                'account_total_price' => $account_total_price
            ];
        }

        if ($level) {
            $main_list = $this->filterIncomeAccountsByReportLevel($main_list, $level);
        }

        return $main_list;
    }

    private function incomeStatementRootsQuery()
    {
        return $this->model
            ->whereNull('parent_id')
            ->where(function ($query) {
                $query->whereIn('type', [
                    AccountTypesEnum::GeneralOutcome->value,
                    AccountTypesEnum::GeneralIncome->value,
                ])->orWhereIn('code', [4, 5]);
            });
    }

    private function incomeStatementRootType($account): AccountTypesEnum
    {
        if ($account->type === AccountTypesEnum::GeneralIncome || (string) $account->code === '4') {
            return AccountTypesEnum::GeneralIncome;
        }

        return AccountTypesEnum::GeneralOutcome;
    }

    private function getMaxChildrenDepth($account, int $depth = 0): int
    {
        if (!$account->children || $account->children->isEmpty()) {
            return $depth;
        }

        $maxDepth = $depth;

        foreach ($account->children as $child) {
            $maxDepth = max($maxDepth, $this->getMaxChildrenDepth($child, $depth + 1));
        }

        return $maxDepth;
    }

    private function filterIncomeAccountsByReportLevel(array $main_list, int $level): array
    {
        return array_map(function ($root) use ($level) {
            $root['list'] = $this->keepRowsUntilReportLevel($root['list'], $level);

            return $root;
        }, $main_list);
    }

    private function keepRowsUntilReportLevel(array $rows, int $targetLevel, int $currentLevel = 1): array
    {
        return array_map(function ($row) use ($targetLevel, $currentLevel) {
            if ($currentLevel >= $targetLevel) {
                $row['children'] = [];

                return $row;
            }

            $row['children'] = $this->keepRowsUntilReportLevel(
                $row['children'] ?? [],
                $targetLevel,
                $currentLevel + 1
            );

            return $row;
        }, $rows);
    }

    private function buildIncomeAccountRow($account, $constraints_data)
    {
        $children = [];
        $children_total = 0;

        if ($account->children->count() > 0) {
            foreach ($account->children as $child) {
                $child_row = $this->buildIncomeAccountRow($child, $constraints_data);
                $children_total += $child_row['total_price'];
                $children[] = $child_row;
            }
        }

        $own_total = $constraints_data[$account->id] ?? 0;

        return [
            'account' => $account,
            'total_price' => $own_total + $children_total,
            'own_total_price' => $own_total,
            'children' => $children,
        ];
    }

    private function getTotalPriceOfChildrens($account, $constraints_data)
    {
        $childrens = $account->children;
        $total_price = 0;

        if ($childrens->count() > 0) {
            foreach ($childrens as $children) {
                $total_price += $this->getTotalPriceOfChildrens($children, $constraints_data);
            }
        } else {
            // Use pre-loaded data instead of querying database
            if (isset($constraints_data[$account->id])) {
                $total_price += $constraints_data[$account->id];
            }
        }

        return $total_price;
    }

    private function getAllAccountIds($accounts)
    {
        $account_ids = [];

        foreach ($accounts as $account) {
            $account_ids[] = $account->id;
            $this->collectChildrenIds($account, $account_ids);
        }

        return array_unique($account_ids);
    }

    private function collectChildrenIds($account, &$account_ids)
    {
        if ($account->children && $account->children->count() > 0) {
            foreach ($account->children as $child) {
                $account_ids[] = $child->id;
                $this->collectChildrenIds($child, $account_ids);
            }
        }
    }

    private function getConstraintsData($account_ids, $constraintsQuery)
    {
        // Keep the matching detail IDs as a subquery. Materializing them into a
        // whereIn array can exceed MySQL's 65,535 prepared-statement placeholders.
        $constraints = DB::table('fn_constraint_details')
            ->join('fn_accounts', 'fn_accounts.id', '=', 'fn_constraint_details.account_id')
            ->whereIn('fn_constraint_details.account_id', $account_ids)
            ->whereIn('fn_constraint_details.id', $constraintsQuery)
            ->whereNull('fn_constraint_details.deleted_at')
            ->select(
                'fn_constraint_details.account_id',
                'fn_accounts.status',
                DB::raw('SUM(fn_constraint_details.debtor_value) as debtor_value'),
                DB::raw('SUM(fn_constraint_details.creditor_value) as creditor_value')
            )
            ->groupBy('fn_constraint_details.account_id', 'fn_accounts.status')
            ->get();

        // Group by account_id and calculate totals
        $constraints_data = [];
        foreach ($constraints as $constraint) {
            $account_id = $constraint->account_id;
            if (!isset($constraints_data[$account_id])) {
                $constraints_data[$account_id] = 0;
            }
            $constraints_data[$account_id] += $constraint->status === 'creditor'
                ? ($constraint->creditor_value - $constraint->debtor_value)
                : ($constraint->debtor_value - $constraint->creditor_value);
        }

        return $constraints_data;
    }
    public function getMainAccounts()
    {
        return $this->model
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get(['id', 'name', 'code']);
    }
}
