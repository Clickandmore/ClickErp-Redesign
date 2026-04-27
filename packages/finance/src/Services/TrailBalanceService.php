<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Account as Model;

class TrailBalanceService extends MainService
{

    function __construct(
        Model $model,
        protected yearService $yearService,
        protected BankAccountService $bankAccountService,
        protected AccountService $accountService,
        protected ConstraintDetailsService $constraintDetailsService,
    )
    {
        $this->model = $model;
    }

    public function showLevels()
    {
        return $this->accountService->getLevels();
    }

    public function getTrailBalance($request,$active_year_id)
    {
        $parentAccounts = $this->accountService->getAccountsByLevel($request['level']);

        $accountsWithChildren = $this->formatAccountsWithDescendants($parentAccounts);

        $data['constraints']          = $this->getConstraintsDetails($request,$active_year_id);
        $data['accountsWithChildren'] = $accountsWithChildren;

        return $data;
    }

    private function formatAccountsWithDescendants($parentAccounts)
    {
        return $parentAccounts->map(function ($parentAccount) {
            $allChildrenIds = $this->getAllDescendantIds($parentAccount);
            return [
                'id' => $parentAccount->id,
                'name' => $parentAccount->name,
                'code' => $parentAccount->code,
                'status' => $parentAccount->status,
                'all_children_ids' => $allChildrenIds,
            ];
        });
    }

    private function getAllDescendantIds($account)
    {
        return $this->retrieveDescendantIds($account);
    }

    private function retrieveDescendantIds($account)
    {
        $allDescendantIds = [$account->id];
        foreach ($account->childrenRecursive as $child) {
            $allDescendantIds = array_merge($allDescendantIds, $this->retrieveDescendantIds($child));
        }
        return $allDescendantIds;
    }

    public function getConstraintsDetails($request,$active_year_id)
    {
        $yearId = $active_year_id;
        $level = $request['level'];
        $fromDate = $request['from_date'];
        $toDate = $request['to_date'];
        $includeFirstBalance = isset($request['show_first_balance']) && $request['show_first_balance'];

        // Get results for the date range
        $results = $this->getQueryResults($yearId, $level, $fromDate, $toDate);

        // If user wants the first balance, get and merge those results
        if ($includeFirstBalance) {
            $results['firstBalanceResults']  = $this->getQueryResults($yearId, $level, null, $fromDate);
        }

        return $results;
    }

    protected function getQueryResults($yearId, $level, $startDate = null, $endDate = null)
    {
        $query = $this->constraintDetailsService->model->query()->with('account');

        // Filter by Active year
        $query->whereHas('constraint', function ($q) use ($yearId) {
            $q->where('year_id', $yearId);
        });

        // Filter by level
        $query->whereHas('account', function ($q) use ($level) {
            $q->where('level', '>=', $level);
        });

        // Filter by date range if provided
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        else{
            $query->whereDate('date', '<', $endDate);
        }

        // Get results and map them
        return $query->get()->keyBy('account_id')->map(function ($constraint) {
            return [
                'id' => $constraint->id,
                'constraint_id' => $constraint->constraint_id,
                'type' => $constraint->type,
                'account_type' => optional($constraint->account)->status,
                'debtor_value' => $constraint->debtor_value,
                'creditor_value' => $constraint->creditor_value,
            ];
        });
    }

    /**********************************************************************************************/

    // Method 1: Using Eloquent with recursive relationship
    public function getAccountSummaryByLevel($level)
    {
        // Get all accounts at the specified level
        $accounts = DB::table('fn_accounts')
            ->where('level', $level)
            ->get();

        $result = [];

        foreach ($accounts as $account) {
            // Get all descendant account IDs (children, grandchildren, etc.)
            $descendantIds = $this->getAllDescendantIdsShow($account->id);

            // Include the parent account ID itself
            $allAccountIds = array_merge([$account->id], $descendantIds);

            // Sum debtor and creditor values for this account and all its descendants
            $sums = DB::table('fn_constraint_details')
                ->whereIn('account_id', $allAccountIds)
                ->selectRaw('
                SUM(debtor_value) as total_debtor,
                SUM(creditor_value) as total_creditor
            ')
                ->first();

            $result[] = [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
                'total_debtor' => $sums->total_debtor ?? 0,
                'total_creditor' => $sums->total_creditor ?? 0,
                'balance' => ($sums->debtor_value ?? 0) - ($sums->creditor_value ?? 0)
            ];
        }

        return collect($result);
    }

    // Helper method to get all descendant IDs recursively
    private function getAllDescendantIdsShow($parentId)
    {
        $descendants = [];

        // Get direct children
        $children = DB::table('fn_accounts')
            ->where('parent_id', $parentId)
            ->pluck('id')
            ->toArray();

        foreach ($children as $childId) {
            $descendants[] = $childId;
            // Recursively get descendants of each child
            $descendants = array_merge($descendants, $this->getAllDescendantIdsShow($childId));
        }

        return $descendants;
    }


    // Method 2: Using Common Table Expression (CTE) for better performance
    public function getAccountSummaryByLevelCTE($level)
    {
        $query = "
        WITH RECURSIVE account_hierarchy AS (
            -- Base case: accounts at the specified level
            SELECT id, name, code, parent_id, level
            FROM fn_accounts
            WHERE level = ?

            UNION ALL

            -- Recursive case: children of accounts in hierarchy
            SELECT a.id, a.name, a.code, a.parent_id, a.level
            FROM fn_accounts a
            INNER JOIN account_hierarchy ah ON a.parent_id = ah.id
        ),
        level_accounts AS (
            SELECT id, name, code
            FROM fn_accounts
            WHERE level = ?
        )
        SELECT
            la.id,
            la.name,
            la.code,
            COALESCE(SUM(cd.debtor_value), 0) as total_debtor,
            COALESCE(SUM(cd.creditor_value), 0) as total_creditor,
            COALESCE(SUM(cd.debtor_value), 0) - COALESCE(SUM(cd.creditor_value), 0) as balance
        FROM level_accounts la
        LEFT JOIN account_hierarchy ah ON (ah.id = la.id OR ah.parent_id = la.id)
        LEFT JOIN fn_constraint_details cd ON cd.account_id = ah.id
        GROUP BY la.id, la.name, la.code
        ORDER BY la.code
    ";

        return DB::select($query, [$level, $level]);
    }


    // Method 3: Using Eloquent Models (if you have models set up)
    public function getAccountSummaryWithModels($level)
    {
        // Assuming you have FnAccount and FnConstraintDetail models
        return FnAccount::where('level', $level)
            ->with(['constraintDetails', 'allDescendants.constraintDetails'])
            ->get()
            ->map(function ($account) {
                // Get all constraint details for this account and its descendants
                $allDetails = collect([$account->constraintDetails])
                    ->merge($account->allDescendants->pluck('constraintDetails')->flatten())
                    ->flatten();

                return [
                    'id' => $account->id,
                    'name' => $account->name,
                    'code' => $account->code,
                    'total_debtor' => $allDetails->sum('debtor_value'),
                    'total_creditor' => $allDetails->sum('creditor_value'),
                    'balance' => $allDetails->sum('debtor_value') - $allDetails->sum('creditor_value')
                ];
            });
    }


    // Method 4: Single optimized query (most efficient)
    public function getAccountSummaryOptimized($level)
    {
        return DB::select("
        SELECT
            parent_acc.id,
            parent_acc.name,
            parent_acc.code,
            parent_acc.status,
            COALESCE(SUM(cd.debtor_value), 0) as total_debtor,
            COALESCE(SUM(cd.creditor_value), 0) as total_creditor,
            COALESCE(SUM(cd.debtor_value), 0) - COALESCE(SUM(cd.creditor_value), 0) as balance
        FROM fn_accounts parent_acc
        LEFT JOIN fn_accounts child_acc ON (
            child_acc.id = parent_acc.id OR
            child_acc.parent_id = parent_acc.id OR
            child_acc.parent_id IN (
                SELECT id FROM fn_accounts WHERE parent_id = parent_acc.id
            ) OR
            child_acc.parent_id IN (
                SELECT id FROM fn_accounts WHERE parent_id IN (
                    SELECT id FROM fn_accounts WHERE parent_id = parent_acc.id
                )
            )
            -- Add more levels if needed for deeper hierarchies
        )
        LEFT JOIN fn_constraint_details cd ON cd.account_id = child_acc.id
        WHERE parent_acc.level = ?
        GROUP BY parent_acc.id, parent_acc.name, parent_acc.code
        ORDER BY parent_acc.code
    ", [$level]);
    }


    //  Method 5: no child
    public function getAccountSummaryOptimizedNoChild()
    {
        return DB::select("
        SELECT
            parent_acc.id,
            parent_acc.name,
            parent_acc.code,
            parent_acc.status,
            COALESCE(SUM(cd.debtor_value), 0) as total_debtor,
            COALESCE(SUM(cd.creditor_value), 0) as total_creditor,
            COALESCE(SUM(cd.debtor_value), 0) - COALESCE(SUM(cd.creditor_value), 0) as balance
        FROM fn_accounts parent_acc
        LEFT JOIN fn_accounts children ON children.parent_id = parent_acc.id
        LEFT JOIN fn_accounts child_acc ON (
            child_acc.id = parent_acc.id OR
            child_acc.parent_id = parent_acc.id OR
            child_acc.parent_id IN (
                SELECT id FROM fn_accounts WHERE parent_id = parent_acc.id
            ) OR
            child_acc.parent_id IN (
                SELECT id FROM fn_accounts WHERE parent_id IN (
                    SELECT id FROM fn_accounts WHERE parent_id = parent_acc.id
                )
            )
            -- Add more levels if needed for deeper hierarchies
        )
        LEFT JOIN fn_constraint_details cd ON cd.account_id = child_acc.id
        WHERE children.id IS NULL  -- Only accounts without children (leaf accounts)
        GROUP BY parent_acc.id, parent_acc.name, parent_acc.code, parent_acc.status
        ORDER BY parent_acc.code
    ");
    }
    /**********************************************************************************************/


}
