<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Services\ItemService;
use Nami\Finance\Services\PurchaseItemService as ObjService;
use Nami\Finance\Services\StockService;
use Nami\Finance\Services\SupplierService;

class TotalItemPurchaseController extends Controller
{
    public string $folderPath = "finance::finance.total_item_purchases";
    public string $mainRoute = "finance.total-item-purchases";

    public function index(Request $request, ObjService $service, SupplierService $supplierService,
    StockService $stockService, ItemService $itemService)
    {
        $filters = $request->only('item_id', 'type', 'supplier_id','from_date','to_date');
        if ($request->ajax()) {
            $report = $service->getTotalItemPurchasesReport($filters);
            return response()->json([
                'data' => view($this->folderPath . '.table', compact('report'))->render()
            ]);
        }
        $data["report"] = $service->getTotalItemPurchasesReport($filters);
        $data["bladeTitle"] = __("finance::finance.total-item-purchases");
        $data["suppliers"] = $supplierService->get();
        $data["stocks"] = $stockService->get();
        $data["types"] = PurchaseTypeEnum::cases();
        $data["items"] = $itemService->getItemsWithoutSomeTypes(
                    [CategoryTypes::Service->value,CategoryTypes::Boxs,CategoryTypes::FinishedProduct->value]
                );

        if ($request->ajax()) {
            return view($this->folderPath . '.table', $data);
        }

        return view($this->folderPath . '.index', $data);
    }
}
