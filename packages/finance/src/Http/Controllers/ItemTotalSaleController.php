<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\SalesTypeEnum;
use Nami\Finance\Services\BillSalesItemService as ObjService;
use Nami\Finance\Services\ItemService;
use Nami\Finance\Services\SupplierService;

class ItemTotalSaleController extends Controller
{
    public string $folderPath = "finance::finance.item_total_sale";
    public string $mainRoute = "finance.item-total-sale";

    public function index(Request $request, ObjService $service, SupplierService $supplierService,
     ItemService $itemService)
    {
        $filters = $request->only('item_id','type','from_date','to_date');
        if ($request->ajax()) {
            $report = $service->getSaleReport($filters);
//            dd($report);
            return response()->json([
                'data' => view($this->folderPath . '.table', compact('report'))->render()
            ]);
        }
        $data["bladeTitle"] = __("finance::finance.item-total-sale");
        $data["suppliers"] = $supplierService->get();
        $data["types"] = SalesTypeEnum::cases();
        $data["report"] = $service->getSaleReport($filters);
        $data["items"] = $itemService->getItemsWithoutSomeTypes(
                    [CategoryTypes::Service->value,CategoryTypes::Boxs,CategoryTypes::FinishedProduct->value]
                );

        if ($request->ajax()) {
            return view($this->folderPath . '.table', $data);
        }

        return view($this->folderPath . '.index', $data);
    }
}
