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
        $costs = $this->model->with('childrenRecursive')->whereNull('parent_id')->get();

        foreach ($costs as $cost) {
            $this->buildAllChildrenIDs($cost);
        }

        $data['costs'] = $costs;
        $data['constraintDetails'] = $this->getConDetails();
        return $data;
    }

    public function getDatatableData()
    {
        return $this->model->query()->orderBy("id", "ASC");
    }

    public function buildAllChildrenIDs($item)
    {
        // Initialize the allChildrenIDs property for the current item
        $item->allChildrenIDs = $this->getAllChildrenIDs($item);

        // Recursively process children
        foreach ($item->childrenRecursive as $child) {
            $this->buildAllChildrenIDs($child);
        }
    }

    private function getAllChildrenIDs($item)
    {
        $allChildrenIDs = [$item->id];

        foreach ($item->childrenRecursive as $child) {
            $allChildrenIDs = array_merge($allChildrenIDs, $this->getAllChildrenIDs($child));
        }

        return $allChildrenIDs;
    }

    public function getConDetails()
    {
        return DB::table('fn_constraint_details')
            ->selectRaw('cost_center_id, SUM(debtor_value) as debtor_value, SUM(creditor_value) as creditor_value, MAX(id) as id')
            ->whereNotNull('cost_center_id')
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
