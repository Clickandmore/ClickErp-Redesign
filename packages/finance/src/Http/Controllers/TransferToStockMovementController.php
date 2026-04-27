<?php

namespace Nami\Finance\Http\Controllers;


use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Services\ConversionService as ObjService;
use Nami\Finance\Services\SarfMovementService;
use Nami\Finance\Services\StockMovementItemService;
use Nami\Finance\Services\StockStatusService;
use Nami\Finance\Services\TransferToStockMovementService;

class TransferToStockMovementController extends Controller
{
    public string $folderPath = "finance::finance.conversions";
    public string $mainRoute = "finance.transfer-to-stock-movement";


    public function update(Request $request,int $id, ObjService $ConversionService, SarfMovementService $sarfMovementService,
    StockStatusService $stockStatusService, TransferToStockMovementService $transferToStockMovementService
    , StockMovementItemService $stockMovementItemService)
    {
        $conversion = $ConversionService->find($id);
        $sarfMovement = $transferToStockMovementService->storeSarfMovement($sarfMovementService, $conversion, $request);
        $transferToStockMovementService->storeStockMovementItem($conversion, $stockMovementItemService, $sarfMovement);
        $transferToStockMovementService->updateConversionStatus($ConversionService, $id, $sarfMovement);

        $transferToStockMovementService->addQuantitiesToStockStatuses($conversion, $stockStatusService);

        return jsonSuccess();
    }

    public function show(int $id, Request $request, ObjService $service, SarfMovementService $sarfMovementService)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".transfer_to_stock_movement")->with([
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
