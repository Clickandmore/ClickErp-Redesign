<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Services\BillSalesItemService as ObjService;
use Nami\Finance\Services\ClientService;
use Nami\Finance\Services\ItemService;
use Nami\Finance\Services\StockService;

class ItemSaleMovementController extends Controller
{
    public string $folderPath = "finance::finance.item_sale_movement";
    public string $mainRoute = "finance.item-sale-movement";

    public function index(Request $request, ObjService $service,
    ClientService $clientService, ItemService $itemService, StockService $stockService)
    {
        $filters = $request->only('stock_id', 'item_id', 'client_id','from_date','to_date');
        if ($request->ajax()) {
            $report = $service->getReport($filters);
            return response()->json([
                'data' => view($this->folderPath . '.table', compact('report'))->render()
            ]);
        }
        $data["bladeTitle"] = __("finance::finance.item-sale-movement");
        $data["clients"] = $clientService->get();
        $data["stocks"] = $stockService->get();
        $data["items"] = $itemService->getItemsWithoutSomeTypes(
                    [CategoryTypes::Service->value,CategoryTypes::Boxs,CategoryTypes::FinishedProduct->value]
                );

        return view($this->folderPath . '.index', $data);
    }
}
