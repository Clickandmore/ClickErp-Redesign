<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Services\ItemService;
use Nami\Finance\Services\PurchaseItemService as ObjService;
use Nami\Finance\Services\StockService;
use Nami\Finance\Services\SupplierService;

class ItemPurchaseMovementController extends Controller
{
    public string $folderPath = "finance::finance.item_purchase_movement";
    public string $mainRoute = "finance.item-purchase-movement";

    public function index(Request $request, ObjService $service, SupplierService $supplierService,
    StockService $stockService, ItemService $itemService)
    {
        $filters = $request->only('stock_id', 'item_id', 'supplier_id','from_date','to_date');
        if ($request->ajax()) {
            $report = $service->getReport($filters);
            return response()->json([
                'data' => view($this->folderPath . '.table', compact('report'))->render()
            ]);
        }
        $data["bladeTitle"] = __("finance::finance.item-purchase-movement");
        $data["suppliers"] = $supplierService->get();
        $data["stocks"] = $stockService->get();
        $data["items"] = $itemService->getItemsWithoutSomeTypes(
                    [CategoryTypes::Service->value,CategoryTypes::Boxs,CategoryTypes::FinishedProduct->value]
                );

        if ($request->ajax()) {
            return view($this->folderPath . '.table', $data);
        }

        return view($this->folderPath . '.index', $data);
    }
}
