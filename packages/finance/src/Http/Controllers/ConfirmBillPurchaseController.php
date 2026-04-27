<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Services\ConfirmBillPurchaseService;

class ConfirmBillPurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function update(Request $request, string $id,ConfirmBillPurchaseService $confirmBillPurchaseService)
    {
        $purchaseData = $confirmBillPurchaseService->find($id);
        if ($purchaseData['type'] == PurchaseTypeEnum::Purchase->value ) {
            $account = $confirmBillPurchaseService->getAccountId(AccountTypesEnum::Purchases->value);
        } else {
            $account = $confirmBillPurchaseService->getAccountId(AccountTypesEnum::BackPurchases->value);
        }
        if ($account == null) {
            return jsonValid(['error' => [__("finance.account not found, please add account")]], '');
        }
        return $confirmBillPurchaseService->confirmBillPurchase($id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
