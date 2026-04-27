<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\SalesOrderStatusEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\SalesOrder;

class SalesOrderService extends MainService
{

    public function __construct(SalesOrder $model,protected ClientService $clientService
    ,protected ItemService $itemService, protected SalesOrderItemService $salesOrderItemService,protected BillSalesService $billSalesService)
    {
        $this->model = $model;
        $this->fileFolder = 'sales_orders/';
        $this->files = ['file'];
    }

    public function getClients() {
        return $this->clientService->get();
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
        return $this->itemService->get();
    }

    public function storeSalesOrder($data) {
        $data['notes'] = $data['notes'] ?? "-";
        $data['total_price'] = $data['total_price'] ?? 0;
        $data['total_discount_value'] = $data['total_discount_value'] ?? 0;
        $data['total_tax_value'] = $data['total_tax_value'] ?? 0;
        $data['final_total'] = $data['final_total'] ?? 0;
        $obj = $this->store($data);
        $this->salesOrderItemService->storeSalesOrderItem($data, $obj->id);
        return $obj;
    }

    public function updateSalesOrder($id,$data) {
        $data['notes'] = $data['notes'] ?? "-";
        $update = $this->update($id,$data);
        $obj = $this->find($id);
        $obj->salesOrderItems()->delete();
        $this->salesOrderItemService->storeSalesOrderItem($data, $obj->id);
        return $obj;
    }

    public function transferSaleOrderToBillSale($id)
    {
        $saleOrder = $this->find($id);
        $saleOrder->update(['status' => SalesOrderStatusEnum::Transfer->value]);
    }

}
