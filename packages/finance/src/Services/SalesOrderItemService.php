<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\SalesOrderItem;

class SalesOrderItemService extends MainService
{
    public function __construct(SalesOrderItem $model)
    {
        $this->model = $model;
    }

    public function storeSalesOrderItem($data,$salesOrderId) {
        if (isset($data['items'][0]['item_id'])){
            foreach ($data['items'] as $key => $item) {
                $item['sales_order_id'] = $salesOrderId;
                $item['discount_per'] = $item['discount_per'] ?? 0;
                $item['tax_per'] = $item['tax_per'] ?? 0;
                $item['sales_price'] = $item['sales_price'] ?? 0;
                $item['tax_value'] = $item['tax_value'] ?? 0;
                $this->model->create($item);
            }
        }
    }
}
