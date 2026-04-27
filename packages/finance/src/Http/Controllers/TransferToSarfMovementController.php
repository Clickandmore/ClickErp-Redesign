<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\ConversionService as ObjService;
use Nami\Finance\Services\SarfMovementService;
use Nami\Finance\Services\StockMovementItemService;
use Nami\Finance\Services\StockStatusService;
use Nami\Finance\Services\TransferToSarfMovementService;

class TransferToSarfMovementController extends Controller
{
    public string $folderPath = "finance::finance.conversions";
    public string $mainRoute = "finance.transfer-to-sarf-movement";


    public function update(Request $request,int $id, ObjService $ConversionService, SarfMovementService $sarfMovementService,
    StockStatusService $stockStatusService, TransferToSarfMovementService $transferToSarfMovementService
    , StockMovementItemService $stockMovementItemService)
    {
        $conversion = $ConversionService->find($id);

        $stockStatuses = $stockStatusService->getWhere(['fn_stock_id' => $conversion->from_stock_id]);

        $aggregatedStockStatuses = $transferToSarfMovementService->aggregatedStockStatuses($stockStatuses);

        foreach ($conversion->conversionItems as $conversionItem) {
            $totalStockAmount = $aggregatedStockStatuses->get($conversionItem->item_id, 0);

            if ($totalStockAmount == 0) {
                return jsonValid(['error' => [__("finance.item_not_found_in_stock", ['item_name' => $conversionItem->item->name])]], '');
            }

            if ($conversionItem->transfer_amount > $totalStockAmount) {
                return jsonValid(['error' => [__("finance.transfer_amount_exceeds_stock", ['item_name' => $conversionItem->item->name])]], '');
            }
        }
        $sarfMovement = $transferToSarfMovementService->storeSarfMovement($sarfMovementService, $conversion, $request);

        $transferToSarfMovementService->storeStockMovementItem($conversion, $stockMovementItemService, $sarfMovement);
        $transferToSarfMovementService->updateConversionStatus($ConversionService, $id, $sarfMovement);

        $transferToSarfMovementService->deductQuantitiesFromStockStatuses($conversion, $stockStatuses);

        return jsonSuccess();
    }

    public function show(int $id, Request $request, ObjService $service, SarfMovementService $sarfMovementService)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".transfer_to_sarf_movement")->with([
                "obj" => $service->find($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
                'stocks' => $service->getStocks(),
                "code" => $sarfMovementService->lastCode(),
                'show_price_in_conversions' => $service->getShowPriceInConversions(),
                'items' => $service->getItems(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

}
