<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\PurchaseOrderStatusEnum;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Http\Requests\FirstStockRequest as ObjRequest;
use Nami\Finance\Services\FirstStockService as ObjService;
use Nami\Finance\Services\PurchaseOrderService;
use Nami\Finance\Services\PurchaseService;
use Nami\Finance\Services\SalesOrderService;

class TransferPurchaseOrderController extends Controller
{
    protected $folderPath = '';
    /**
     * Display a listing of the resource.
     */

    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "finance::finance.purchase_orders";
    }

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
    public function store(Request $request, SalesOrderService $service)
    {
        $service->transferSaleOrderToBillSale($request->id);
        return jsonSuccess();
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
    public function edit(string $id, PurchaseOrderService $purchaseOrderService, PurchaseService $purchaseService)
    {
        $data["bladeTitle"]    = __("finance.bill_purchases");
        $data["purchaseOrder"] = $purchaseOrderService->find($id);
//        return $data["salesOrder"]->salesOrderItems[0]->item->maxStockAmount();
        $data["storeRoute"]    = route('finance.bill-purchases.store');
        $data['suppliers']     = $purchaseService->getSuppliers();
        $data['lastCode']      = $purchaseService->getLastCodeOfType(PurchaseTypeEnum::Purchase->value);
        $data['items']         = $purchaseService->getItems();
        $data['stocks']        = $purchaseService->getStocks();
        $data['stock_setting'] = $purchaseService->getStocksSetting();
        if($data["purchaseOrder"]->status == PurchaseOrderStatusEnum::Transfer->value){
            return redirect(route('finance.bill-purchases.index'));
        }
        return view($this->folderPath . '.transfer-order-to-purchase', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id,SalesOrderService $service)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
