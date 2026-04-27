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
        $accounts = [];
        $data['total_depitors'] = 0;
        $data['total_creditors'] = 0;
        $data['total_balance'] = 0;

        foreach ($service->supliers_balances() as $account) {

            $balance = $account->account->status->value == 'debtor' ? $account->total_debtor - $account->total_creditor : $account->total_creditor - $account->total_debtor;

            $accounts[]= [
                'code' => $account->account->code,
                'name' => $account->account->name,
                'debtor_value' => $account->total_debtor,
                'creditor_value' => $account->total_creditor,
                'balance' => $balance
            ];

            $data['total_depitors'] += $account->total_debtor;
            $data['total_creditors'] += $account->total_creditor;
            $data['total_balance'] += $balance;

        }
        $data["bladeTitle"] = __("finance::finance.suppliers_balances");
        $data["accounts"] = $accounts;
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
