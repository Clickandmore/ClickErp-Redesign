<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Nami\Finance\Services\ConversionService;
use Nami\Finance\Services\ItemService;

class GetItemsStockController extends Controller
{
    public string $folderPath = "finance::finance.conversions";
    public string $mainRoute = "finance.get-items-by-stock";

    public function show(int $stockId, ItemService $itemService,ConversionService $conversionService)
    {
        $items = $itemService->getItemByStock($stockId);
        $show_price_in_conversions = $conversionService->getShowPriceInConversions();

        // $data = $items->map(function ($item) {
        //     return [
        //         'id' => $item->id,
        //         'name' => $item->name,
        //         'purchase_price' => $item->purchase_price,
        //         'total_price' => $item->itemStockStatus->sum('price'),
        //         'total_current_amount' => $item->itemStockStatus->sum('amount'),
        //     ];
        // });
        return view('finance::finance.conversions.tbody_render', compact('items','show_price_in_conversions'))->render();
        // return response()->json(['items' => $data]);
    }

}
