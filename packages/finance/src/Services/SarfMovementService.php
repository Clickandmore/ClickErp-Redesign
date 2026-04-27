<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\StockMovementTypeEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\StockMovement;
use Nami\Finance\Models\StockStatus;

class SarfMovementService extends MainService
{
    public function __construct(
        StockMovement $model,
        StockService $stockService,
        StockStatusService $stockStatusService,
        StockMovementItemService $stockMovementItemService,
        ItemService $itemService,
        StockSettingService $StockSettingService
    )
    {
        $this->model = $model;
        $this->itemService = $itemService;
        $this->stockService = $stockService;
        $this->StockSettingService = $StockSettingService;
        $this->stockStatusService = $stockStatusService;
        $this->stockMovementItemService = $stockMovementItemService;
        $this->fileFolder = 'additional_permit/';
    }

    public function lastCode()
    {
        $row = $this->model->orderBy('code', 'DESC')->first();
        return (isset($row->code)) ? ($row->code + 1) : 1;
    }

    public function getLastCodeOfType($type)
    {
        $row = $this->model->where('type', $type)->orderBy('code', 'DESC')->first();
        return (isset($row->code)) ? ($row->code + 1) : 1;
    }

    public function getSarfTable()
    {
        return $this->model->whereIn(
            'type',[StockMovementTypeEnum::SaleNormal->value,StockMovementTypeEnum::SalePurchase->value]
        )->latest();
    }

    public function getProductsExceptForStockMovement()
    {
        return $this->itemService->getExcept([
            CategoryTypes::Boxs->value,
            CategoryTypes::Service->value,
            CategoryTypes::FinishedProduct->value,
        ]);
    }

    public function getStocks()
    {
        return $this->stockService->getActive();
    }

    public function stockSetting()
    {
        return $this->StockSettingService->getSetting();
    }


    public function allStokes()
    {
        return $this->stockService->get();
    }

    public function storeSarfStoreMovement($data,$request)
    {
        // Create the stock movement entry
        $movement = $this->model->create([
            'fn_stock_id' => $data['stock_id'],
            'type'        => StockMovementTypeEnum::SaleNormal->value,
            'date'        => $data['date'],
            'code'        => $this->getLastCodeOfType(StockMovementTypeEnum::SaleNormal->value),
            'note'        => $data['note'],
        ]);
        // Call the method to save movement items
        $items = $this->saveMovementItems($data, $movement,$request);

        return $movement;
    }

    public function saveMovementItems($data, $movement,$request)
    {
        // حذف الداتا القديمة وتقليل كمياتها
        foreach ($movement->details as $row){
            $this->stockStatusService->decrementItemBalanceWhenDelete($movement->fn_stock_id, $row->item_id,$row->amount);
            $row->delete();
        }

        $items = [];
        // حفظ الداتا الجديدة
        foreach ($data['item_id'] as $index => $itemId) {
            // Save each item associated with the movement
            $items[] = $this->stockMovementItemService->store([
                'fn_stock_movement_id'  => $movement->id,
                'item_id'               => $itemId,
                'total_price'           => $request['total_price'][$index],
                'price'                 => $request['purchase_price'][$index],
                'amount'                => -$data['amount'][$index],
                'status'                => 'new' // ثابتة الان ,
            ]);
            $this->decrementStockQuantity($itemId, $movement->fn_stock_id, $data['amount'][$index]);
        }
        return $items;
    }


    private function decrementStockQuantity($itemId, $stockId, &$requestedAmount)
    {
        // الكميات المتاحة للمنتج ده في المخزن ده (بيستجلوا في اكتر من صف في حالة السعر مختلف)
        $stockQuantities = StockStatus::where([
            ['item_id', $itemId],
            ['fn_stock_id', $stockId],
        ])->orderBy('created_at')->get();

        foreach ($stockQuantities as $stock) {
            // لو تم تغطيه الصرف يخرج من اللوب
            if ($requestedAmount <= 0) {
                return;
            }

            // لو الكمية كلها متوفرة في المخزن ده
            if ($stock->amount > 0) {
                // لو الرو بتاع المخزن ده يقدر يغطي الكمية المطلوبة
                if ($stock->amount >= $requestedAmount) {
                    // قلل الكمية اللي تم صرفها
                    $stock->decrement('amount', $requestedAmount);
                    $requestedAmount = 0; // Reset requested amount after fulfillment
                } else {
                    // لو الكمية مش هتغطي الطلب يبقي قلل الكمية المطلوبة من الصف وخش علي الصف اللي بعده
                    $requestedAmount -= $stock->amount; // Reduce requested amount

                    // Decrement the whole quantity from stock
                    $stock->decrement('amount', $stock->amount);
                }
            }
        }
    }


    public function findMovement($id)
    {
        return $this->model->with('details','stock')->find($id);
    }

    public function updateMovement($id,$data)
    {
        $movement = $this->model->with('details')->find($id);

        // اذا تم تغيير المخزن في التعديل فقلل في الكمية في المخزن القديم
        if ($movement->fn_stock_id != $data['stock_id']){
            foreach ($movement->details as $row){
                $this->stockStatusService->decrementItemBalanceWhenDelete($movement->fn_stock_id, $row->item_id, $row->amount);
            }
        }

        // Step 2: تعديل بيانات اذن الاضافة
        $movement->update([
            'fn_stock_id' => $data['stock_id'],
            'date'        => $data['date'],
            'code'        => $data['code'],
            'note'        => $data['note'],
        ]);

        // حفظ منتجات اذن الاضافة
        $items = $this->saveMovementItems($data, $movement);

        // تحديث رصيد المخزون
        $this->stockStatusService->updateItemsBalances($movement,$items);
    }

    public function deleteMovement($movementId)
    {
        $movement = $this->find($movementId);

        // حذف الداتا القديمة وتقليل كمياتها
        foreach ($movement->details as $row){
            $this->stockStatusService->decrementItemBalanceWhenDelete($movement->fn_stock_id, $row->item_id, $row->amount);
            $row->delete();
        }
        // حذف اذن الاضافة
        $movement->delete();
    }


}
