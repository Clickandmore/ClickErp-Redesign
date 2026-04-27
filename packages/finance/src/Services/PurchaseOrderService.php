<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\PurchaseOrderStatusEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\PurchaseOrder;

class PurchaseOrderService extends MainService
{
    public function __construct(PurchaseOrder $model, protected SupplierService $supplierService, protected ItemService $itemService)
    {
        $this->model = $model;
    }

    public function createData($mainRoute)
    {
        return [
            'code' => $this->generateCode(),
            'storeRoute' => route($mainRoute . '.store'),
            'suppliers' => $this->supplierService->get(),
            'items' => $this->items(),
        ];
    }

    public function editData($id, $mainRoute)
    {
        return [
            'obj' => $this->find($id),
            'suppliers' => $this->supplierService->get(),
            'items' => $this->items(),
            'updateRoute' => route($mainRoute . '.update', $id)
        ];
    }


    public function showData($id)
    {
        return [
            'obj' => $this->find($id),
            'suppliers' => $this->supplierService->get(),
            'items' => $this->itemService->get(),
        ];
    }

    public function suppliers()
    {
        return $this->supplierService->get();
    }


    public function items()
    {
        return $this->itemService->getItemsWithoutSomeTypes([CategoryTypes::Boxs->value, CategoryTypes::FinishedProduct->value,]);
    }

    private function generateCode()
    {
        return $this->first() != null ? $this->first()->code + 1 : 1;
    }


    public function customStore($data)
    {
        $obj = parent::customStore($data);
        $obj->purchaseOrderItems()->createMany($data['items']);
        return $obj;
    }

    public function customUpdate($id, $data)
    {
        $obj = $this->find($id);
        $obj->purchaseOrderItems()->delete();
        $obj->purchaseOrderItems()->createMany($data['items']);
        parent::customUpdate($id, $data);
        return $obj;
    }


    public function transferPurchaseOrderToBillPurchase($id)
    {
        $purchaseOrder = $this->find($id);
        $purchaseOrder->update(['status' => PurchaseOrderStatusEnum::Transfer->value]);
    }


}
