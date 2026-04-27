<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\StockMovementTypeEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\StockMovement;

class StockMovementService extends MainService
{
    public function __construct(
        StockMovement $model,
        private StockService $stockService,
        private StockStatusService $stockStatusService,
        private StockMovementItemService $stockMovementItemService,
        private ItemService $itemService,
        private StockSettingService $StockSettingService
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

    public function getNormalTypeTable()
    {
        return $this->model->whereIn(
            'type',[StockMovementTypeEnum::NormalAddition->value,StockMovementTypeEnum::PurchaseAdditional->value]
        )->latest();
    }

    public function getLastCodeOfType($type)
    {
        $row = $this->model->where('type', $type)->orderBy('code', 'DESC')->first();
        return (isset($row->code)) ? ($row->code + 1) : 1;
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

    public function storeNormalStoreMovement($data)
    {
        // Create the stock movement entry
        $movement = $this->model->create([
            'fn_stock_id' => $data['stock_id'],
            'type'        => StockMovementTypeEnum::NormalAddition->value,
            'date'        => $data['date'],
            'code'        => $this->getLastCodeOfType(StockMovementTypeEnum::NormalAddition->value),
            'note'        => $data['note'],
        ]);

        // Call the method to save movement items
        $items = $this->saveMovementItems($data, $movement);

        // تحديث رصيد المخزون
        $this->stockStatusService->updateItemsBalances($movement,$items);

        return $movement;
    }

    public function saveMovementItems($data, $movement, $is_element = false)
    {
        // حذف الداتا القديمة وتقليل كمياتها
        foreach ($movement->details as $row){
            $this->stockStatusService->decrementItemBalanceWhenDelete($movement->fn_stock_id, $row->item_id,$row->amount);
            $row->delete();
        }

        $items = [];
        // حفظ الداتا الجديدة
        if($is_element){
            foreach ($data as $item) {
                // Save each item associated with the movement
                $items[] = $this->stockMovementItemService->store([
                    'fn_stock_movement_id'  => $movement->id,
                    'item_id'               => $item->item_id,
                    'total_price'           => $item->final_total,
                    'price'                 => $item->price,
                    'amount'                => $item->amount,
                    'status'                => 'new' // ثابتة الان ,
                ]);
            }
        }else{
            foreach ($data['item_id'] as $index => $itemId) {
                // Save each item associated with the movement
                $items[] = $this->stockMovementItemService->store([
                    'fn_stock_movement_id'  => $movement->id,
                    'item_id'               => $itemId,
                    'total_price'           => $data['price'][$index],
                    'price'                 => $data['one_price'][$index],
                    'amount'                => $data['amount'][$index],
                    'status'                => 'new' // ثابتة الان ,
                ]);
            }
        }

        return $items;
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

        // التاكد من الكميات المتاحة
        foreach ($movement->details as $row){
            $is_available = $this->stockStatusService->checkItemBalanceBeforeDelete($movement->fn_stock_id, $row->item_id, $row->amount);
            if (!$is_available) {
                return [
                    'code' => '422',
                    'message' => __('finance.not_enough_amount_you_can_add_returns_bill', ['stock' => $movement->stock->name, 'item' => $row->item->name])
                ];
            }
        }

        // حذف الداتا القديمة وتقليل كمياتها
        foreach ($movement->details as $row){
            $this->stockStatusService->decrementItemBalanceWhenDelete($movement->fn_stock_id, $row->item_id, $row->amount);
            $row->delete();
        }
        // حذف اذن الاضافة
        $movement->delete();
        return [
            'code' => '200',
        ];
    }

    public function checkStockAmounts($movementId, $purchaseData)
    {
        $movement = $this->find($movementId);

        // التاكد من الكميات المتاحة
        foreach ($movement->details as $row){
            $is_available = $this->stockStatusService->checkItemBalanceBeforeUpdate($movement->fn_stock_id, $row->item_id, $row->amount, $purchaseData);
            if (!$is_available) {
                return [
                    'code' => '422',
                    'message' => __('finance.not_enough_amount_you_can_not_update_bill', ['stock' => $movement->stock->name, 'item' => $row->item->name])
                ];
            }
        }
        return [
            'code' => '200',
        ];
    }


}
