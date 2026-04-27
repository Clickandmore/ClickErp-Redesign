<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Http\Requests\PurchasesRequest as ObjRequest;
use Nami\Finance\Services\PurchaseOrderService;
use Nami\Finance\Services\PurchaseService as ObjService;

class ConvertPurchaseController extends Controller
{
    public string $folderPath = "finance::finance.bill_purchases";
    public $postData = ['code','supplier_bill_number','supplier_id','date',
        'notes','total_item','file','main_stock_id'];
    public string $mainRoute = "finance.convert-purchases";


    public function store(ObjRequest $request, ObjService $service,PurchaseOrderService $purchaseOrderService)
    {
        $purchaseData = $request->only($this->postData);
        // dd($purchaseData);
        if (isset($request->purchase_order)){
            $purchaseOrderService->transferPurchaseOrderToBillPurchase($request->purchase_order);
        }
        $service->storeBackPurchase($purchaseData,$request);
        return jsonSuccess(null,null,201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, ObjService $service,request $request)
    {
        $data['obj'] = $service->find($id);
        // dd($data['obj']->supplier);
        $data['storeRoute'] = route("finance.back-purchases.store");
        $data['suppliers'] = $service->getSuppliers();
        $data['lastCode'] = $service->getLastCodeOfType(PurchaseTypeEnum::BackPurchase->value);
        $data['items'] = $service->getItemsExcept();
        $data['stocks'] = $service->getStocks();
        $data['stock_setting'] = $service->getStocksSetting();
        $data['bladeTitle'] = __('finance.back_purchase');
        return view($this->folderPath . ".convert_purchase",$data);
    }
}
