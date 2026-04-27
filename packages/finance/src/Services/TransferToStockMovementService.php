<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\ConversionStatus;
use Nami\Finance\Enums\StockMovementTypeEnum;
use Nami\Finance\MainService;

class TransferToStockMovementService extends MainService
{

    public function storeSarfMovement($sarfMovementService, $conversion, $request)
    {
        return $sarfMovementService->store([
            'fn_stock_id' => $conversion->to_stock_id,
            'type' => StockMovementTypeEnum::TransferTo->value,
            'note' => $request->note ?? 'لا يوجد',
            'code' => $sarfMovementService->lastCode(),
            'date' => date('Y-m-d'),
        ]);
    }


    public function storeStockMovementItem($conversion, $stockMovementItemService, $sarfMovement) {
        foreach ($conversion->conversionItems as $conversionItem) {
            $stockMovementItemService->store([
                'fn_stock_movement_id' => $sarfMovement->id,
                'item_id' => $conversionItem->item_id,
                'price' => $conversionItem->purchase_price,
                'amount' => $conversionItem->transfer_amount,
                'total_price' => $conversionItem->transfer_amount * $conversionItem->purchase_price,
                'status' => 'new',
            ]);
        }
    }

    public function updateConversionStatus($ConversionService, $id, $sarfMovement) {
        $ConversionService->update($id,
        [
            'status' => ConversionStatus::Ended->value,
            'add_movement_id' => $sarfMovement->id
        ]);
    }

    public function addQuantitiesToStockStatuses($conversion, $stockStatusService) {
        $stockStatuses = $stockStatusService->getWhere(['fn_stock_id' => $conversion->to_stock_id]);
        foreach ($conversion->conversionItems as $conversionItem) {
            $stockStatus = $stockStatuses->where('item_id', $conversionItem->item_id)
                                         ->where('fn_stock_id', $conversion->to_stock_id)
                                         ->where('price', $conversionItem->purchase_price)
                                         ->first();

            if ($stockStatus) {
                $stockStatus->amount += $conversionItem->transfer_amount;
                $stockStatus->save();
            } else {
                $stockStatusService->store([
                    'fn_stock_id' => $conversion->to_stock_id,
                    'item_id' => $conversionItem->item_id,
                    'price' => $conversionItem->purchase_price,
                    'amount' => $conversionItem->transfer_amount
                ]);
            }
        }
    }
}
