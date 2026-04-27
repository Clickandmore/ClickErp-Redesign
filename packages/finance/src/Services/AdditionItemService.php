<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\AdditionItem;

class AdditionItemService extends MainService
{
    public function __construct(AdditionItem $model)
    {
        $this->model = $model;
    }

    public function storeAdditionItems($data, $sales_order_id)
    {
        if (isset($data['addition_items']) && is_array($data['addition_items']) && $data['addition_items'][0]['name'] != null) {
            $items = $this->getWhere(['sales_order_id' => $sales_order_id]);
            if ($items->count() > 0) {
                foreach ($items as $item) {
                    $item->delete();
                }
            }
            foreach ($data['addition_items'] as $item) {
                $this->store([
                    'name' => $item['name'],
                    'code' => $item['code'] ?? rand(1111,9999),
                    'unit' => $item['unit'],
                    'amount' => $item['amount'],
                    'price' => $item['price'],
                    'discount_per' => $item['discount_per'] ?? 0,
                    'discount_value' => $item['discount_value'] ?? 0,
                    'tax_per' => $item['tax_per'] ?? 0,
                    'tax_value' => $item['tax_value'] ?? 0,
                    'final_price' => $item['final_price'] ?? 0,
                    'sales_order_id' => $sales_order_id
                ]);
            }
        }
    }
}
