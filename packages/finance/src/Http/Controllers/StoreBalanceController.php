<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Services\ItemService;
use Nami\Finance\Services\StockService;
use Nami\Finance\Services\StockStatusService as ObjService;

class StoreBalanceController extends Controller
{
    public string $folderPath = "finance::finance.store_balances";
    public string $mainRoute = "finance.store-balances";

    public function index(Request $request, ObjService $service, StockService $stockService, ItemService $itemService)
    {
        $filters = $request->only('stock_id', 'item_id');

        $data['stock_status'] = $service->getReport($filters);
        $data["bladeTitle"] = __("finance::finance.store-balances");
        $data["addButtonText"] = __("finance::finance.store-balances");
        $data["stocks"] = $stockService->get();
        $data["items"] = $itemService->getItemsWithoutSomeTypes([CategoryTypes::Service->value]);

        if ($request->ajax()) {
            return view('finance::finance.store_balances.table', $data);
        }

        return view($this->folderPath . '.index', $data);
    }
}
