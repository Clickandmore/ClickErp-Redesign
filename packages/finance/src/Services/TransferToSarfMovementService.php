<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\ConversionStatus;
use Nami\Finance\Enums\StockMovementTypeEnum;
use Nami\Finance\MainService;

class TransferToSarfMovementService extends MainService
{
    public function aggregatedStockStatuses($stockStatuses) {
        return $stockStatuses->groupBy('item_id')->map(function ($group) {
            return $group->sum('amount');
        });
    }

    public function storeSarfMovement($sarfMovementService, $conversion, $request)
    {
        return $sarfMovementService->store([
            'fn_stock_id' => $conversion->from_stock_id,
            'type' => StockMovementTypeEnum::TransferFrom->value,
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
                'amount' => -$conversionItem->transfer_amount,
                'total_price' => $conversionItem->transfer_amount * $conversionItem->purchase_price,
                'status' => 'new',
            ]);
        }
    }

    public function updateConversionStatus($ConversionService, $id, $sarfMovement) {
        $ConversionService->update($id,
        [
            'status' => ConversionStatus::MakeSarf->value,
            'stock_movement_id' => $sarfMovement->id
        ]);
    }

    public function deductQuantitiesFromStockStatuses($conversion, $stockStatuses) {
        foreach ($conversion->conversionItems as $conversionItem) {
            $stockItems = $stockStatuses->where('item_id', $conversionItem->item_id);

            $remainingTransferAmount = $conversionItem->transfer_amount;

            foreach ($stockItems as $stockStatus) {
                if ($remainingTransferAmount > 0) {
                    if ($stockStatus->amount >= $remainingTransferAmount) {
                        $stockStatus->amount -= $remainingTransferAmount;
                        $stockStatus->save();
                        break;
                    } else {
                        $remainingTransferAmount -= $stockStatus->amount;
                        $stockStatus->amount = 0;
                        $stockStatus->save();
                    }
                }
            }
        }
    }
}
