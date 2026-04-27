<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\Inventory;

class InventoryService extends MainService
{
    public function __construct(Inventory $model,protected StockService $stockService
    ,protected ItemService $itemService, protected InventoryItemService $inventoryItemService)
    {
        $this->model = $model;
        $this->fileFolder = 'inventories/';
        $this->files = ['file'];
    }

    public function getStocks() {
        return $this->stockService->get();
    }

    public function getCode()
    {
        $lastRow = $this->model->latest('code')->first();
        if ($lastRow) {
            return $lastRow->code + 1;
        } else {
            return 1;
        }
    }

    public function getItems() {
        return $this->itemService->getItems();
    }

    public function storeInventory($data) {
        $obj = $this->store($data);
        $totalDifferencePurchasePrice = $this->inventoryItemService->storeInventoryItem($data, $obj->id);
        $obj->update(["total_purchase_price" => $totalDifferencePurchasePrice]);
        return $obj;
    }

    public function updateInventory($id,$data) {
        $update = $this->update($id,$data);
        $obj = $this->find($id);
        $obj->inventoryItems()->delete();
        $totalDifferencePurchasePrice = $this->inventoryItemService->storeInventoryItem($data, $obj->id);

        $obj->update(["total_purchase_price" => $totalDifferencePurchasePrice]);
        return $obj;
    }
}
