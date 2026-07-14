<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\MainService;
use Nami\Finance\Models\CostCenter;
use Nami\Hr\Services\EmployeeService;

class CostCenterService extends MainService
{

    public function __construct(CostCenter $model, private StockService $stockService)
    {
        $this->model = $model;
        $this->fileFolder = 'cost_centers/';

        // ✅ Laravel هيعمل auto-inject لكل dependencies داخل EmployeeService
        $this->employeeService = app()->make(EmployeeService::class);
    }

    public function getStocksWithNoCostCenter()
    {
        $stocks = $this->getWhere(['table_name' => 'stocks'])->pluck('table_related_id')->toArray();
        return $this->stockService->getWhereNotIn('id', $stocks);
    }
    public function getStocksWithCostCenter()
    {
        $stocks = $this->getWhere(['table_name' => 'stocks'])->pluck('table_related_id')->toArray();
        return $this->stockService->getWhereIn('id', $stocks);
    }

    public function getStocks()
    {
        return $this->stockService->get();
    }

    public function getEmployeesWithNoCostCenter()
    {
        $employees = $this->getWhere(['table_name' => 'employees'])->pluck('table_related_id')->toArray();
        return $this->employeeService->getWhereNotIn('id', $employees);
    }
    public function getEmployeesWithCostCenter()
    {
        $employees = $this->getWhere(['table_name' => 'employees'])->pluck('table_related_id')->toArray();
        return $this->employeeService->getWhereIn('id', $employees);
    }
    public function getEmployees()
    {
        return $this->employeeService->get();
    }

    public function getAccountTree()
    {
        $costs = $this->sortTree(
            $this->model->with('childrenRecursive')->whereNull('parent_id')->get()
        );
        $constraintDetails = $this->getConDetails();

        foreach ($costs as $cost) {
            $this->attachAggregateBalances($cost, $constraintDetails);
        }

        $data['costs'] = $costs;
        $data['constraintDetails'] = $constraintDetails;
        return $data;
    }

    private function sortTree($items)
    {
        $sorted = $items->sort(function ($left, $right) {
            $leftCode = (int) ($left->legacy_source_code ?: $left->code ?: $left->id);
            $rightCode = (int) ($right->legacy_source_code ?: $right->code ?: $right->id);

            return [$leftCode, $left->title, $left->id] <=> [$rightCode, $right->title, $right->id];
        })->values();

        foreach ($sorted as $item) {
            if ($item->relationLoaded('childrenRecursive')) {
                $item->setRelation('childrenRecursive', $this->sortTree($item->childrenRecursive));
            }
        }

        return $sorted;
    }

    public function getDatatableData()
    {
        return $this->model->query()->orderBy("id", "ASC");
    }

    private function attachAggregateBalances($item, $constraintDetails): array
    {
        $debtor = (float) ($constraintDetails[$item->id]['debtor_value'] ?? 0);
        $creditor = (float) ($constraintDetails[$item->id]['creditor_value'] ?? 0);

        foreach ($item->childrenRecursive as $child) {
            [$childDebtor, $childCreditor] = $this->attachAggregateBalances($child, $constraintDetails);
            $debtor += $childDebtor;
            $creditor += $childCreditor;
        }

        $item->aggregate_debtor_value = $debtor;
        $item->aggregate_creditor_value = $creditor;

        return [$debtor, $creditor];
    }

    public function getConDetails()
    {
        return DB::table('fn_constraint_details')
            ->selectRaw('cost_center_id, SUM(debtor_value) as debtor_value, SUM(creditor_value) as creditor_value, MAX(id) as id')
            ->whereNotNull('cost_center_id')
            ->whereNull('deleted_at')
            ->groupBy('cost_center_id')
            ->get()
            ->keyBy('cost_center_id')
            ->map(function ($constraint) {
            return [
                'id' => (int) $constraint->id,
                'constraint_id' => null,
                'type' => null,
                'account_type' => null,
                'debtor_value' => (float) $constraint->debtor_value,
                'creditor_value' => (float) $constraint->creditor_value,
            ];
        });
    }

    public function getMainAccounts()
    {
        return $this->model->query()->whereNull('parent_id')->get();
    }
}
