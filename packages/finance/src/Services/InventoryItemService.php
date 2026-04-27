<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\InventoryItem;

class InventoryItemService extends MainService
{
    public function __construct(InventoryItem $model)
    {
        $this->model = $model;
    }

    public function storeInventoryItem($data,$inventoryId) {
        $totalDifferencePurchasePrice = 0;
        foreach ($data['items'] as $key => $item) {
            $item['inventory_id'] = $inventoryId;
            $item['notes'] = $item['notes'] ?? "لا يوجد";
            $this->model->create($item);
            $totalDifferencePurchasePrice += $item['difference_purchase_price'] ?? 0;
        }
        return $totalDifferencePurchasePrice;
    }
}
