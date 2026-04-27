<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\SalesOrderStatusEnum;
use Nami\Finance\Enums\SalesTypeEnum;
use Nami\Finance\Http\Requests\FirstStockRequest as ObjRequest;
use Nami\Finance\Services\BillSalesService;
use Nami\Finance\Services\FirstStockService as ObjService;
use Nami\Finance\Services\SalesOrderService;

class TransferSalesOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "finance::finance.sales_order";
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
    public function edit(string $id, SalesOrderService $service, BillSalesService $billSalesService)
    {
        $data["bladeTitle"]    = __("finance.bill-sales");
        $data["salesOrder"]    = $service->find($id);
//        return $data["salesOrder"]->salesOrderItems[0]->item->maxStockAmount();
        $data["storeRoute"]    = route('finance.bill-sales.store');
        $data['clients']       = $billSalesService->getClients();
        $data['lastCode']      = $billSalesService->getLastCodeOfType(SalesTypeEnum::Sales->value);
        $data['items']         = $billSalesService->getItems();
        $data['stocks']        = $billSalesService->getStocks();
        $data['stock_setting'] = $billSalesService->getStocksSetting();
        if($data["salesOrder"]->status == SalesOrderStatusEnum::Transfer->value){
            return redirect(route('finance.bill-sales.index'));
        }
        return view($this->folderPath . '.transfer-order-to-sale', $data);
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
