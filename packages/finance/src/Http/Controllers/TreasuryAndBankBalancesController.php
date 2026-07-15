<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\ConstraintDetailsService as ObjService;

class TreasuryAndBankBalancesController extends Controller
{
    public string $folderPath = "finance::finance.balances";
    public string $mainRoute = "finance.treasury-and-banks-balances";
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request,ObjService $service)
    {
        $filters = $service->normalizeBalanceMovementFilters($request->only(['from_date', 'to_date', 'keyword']));
        $data["dataTableRoute"] = route($this->mainRoute . '.index');
        $allAccounts = $service->balancesMovementReport('treasury_banks', $filters);
        $data['movementSummary'] = $service->summarizeBalanceMovementReport($allAccounts, 'treasury_banks');
        $data['accounts'] = $allAccounts;
        $data['filters'] = $filters;
        $data['reportType'] = 'treasury_banks';

        if ($request->ajax()) {
            return jsonSuccess(['html' => view($this->folderPath . '.result', $data)->render()]);
        }

        $data["bladeTitle"] = __("finance::finance.treasury_and_banks_balances");
        $data["exportFileName"] = 'treasury-and-banks-balances.xlsx';
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
