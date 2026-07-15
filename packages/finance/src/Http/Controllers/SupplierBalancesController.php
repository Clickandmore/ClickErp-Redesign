<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\ConstraintDetailsService as ObjService;


class SupplierBalancesController extends Controller
{
    public string $folderPath = "finance::finance.balances";
    public string $mainRoute = "finance.suppliers-balances";
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request,ObjService $service)
    {
        $filters = $service->normalizeBalanceMovementFilters($request->only(['from_date', 'to_date', 'keyword']));
        $data['accounts'] = $service->balancesMovementReport('external_agents', $filters);
        $data['filters'] = $filters;
        $data['reportType'] = 'external_agents';

        if ($request->ajax()) {
            return jsonSuccess(['html' => view($this->folderPath . '.result', $data)->render()]);
        }

        $data["bladeTitle"] = __("finance::finance.suppliers_balances");
        $data["dataTableRoute"] = route($this->mainRoute . '.index');
        $data["exportFileName"] = 'external-agents-balances.xlsx';
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
