<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\PurchaseItemStatusEnum;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Enums\StockMovementTypeEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Purchase;

class PurchaseService extends MainService
{

    public function __construct(
        Purchase                       $model,
        protected ItemService          $itemService,
        protected StockSettingService  $stockSetting,
        protected SupplierService      $supplierService,
        protected StockService         $stockService,
        protected StockStatusService   $stockStatusService,
        protected PurchaseItemService  $purchaseItemService,
        protected StockMovementService $stockMovementService,
        protected ClientService        $clientService,
        protected PurchaseReturnService $purchaseReturnService,
        protected StockMovementPurchaseService  $stockMovementPurchaseService,
    )
    {
        $this->model = $model;
        $this->fileFolder = 'purchases/';
    }

    public function getPurchaseTable()
    {
        return $this->model->where('type',PurchaseTypeEnum::Purchase->value)->latest();
    }

    public function getByType(string $type, array $dataSearch)
    {
        return $this->model->where('type', $type)
            ->when($dataSearch["from_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", ">=", $dataSearch["from_date"]);
            })
            ->when($dataSearch["to_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", "<=", $dataSearch["to_date"]);
            })
            ->get();
    }

    public function getBackPurchaseTable()
    {
        return $this->model->where('type',PurchaseTypeEnum::BackPurchase->value)->latest();
    }


    public function getSuppliers()
    {
        return $this->supplierService->get();
    }

    public function getStocks()
    {
        return $this->stockService->getActive();
    }

    public function getItems()
    {
        return $this->itemService->get();
    }

    public function getStocksSetting()
    {
        return $this->stockSetting->getSetting();
    }

    public function getLastCodeOfType($type)
    {
        $row = $this->model->where('type', $type)->orderBy('code', 'DESC')->first();
        return (isset($row->code)) ? ($row->code + 1) : 1;
    }

    public function getItemsExcept()
    {
        return $this->itemService->getExcept([
            CategoryTypes::Boxs->value,
            CategoryTypes::Service->value,
            CategoryTypes::FinishedProduct->value,
        ]);
    }


    public function storePurchase($purchaseData, $itemsData)
    {
        // Set sales type and created by ID
        $purchaseData['type'] = PurchaseTypeEnum::Purchase->value;
        $purchaseData['created_by_id'] = auth('admin')->user()->id;

        // Get client account ID
        $purchaseData['credit_supplier_id'] = $this->supplierService->getAccountByID($purchaseData['supplier_id'])->id;

        // Set the final total amount
        $purchaseData['final_total'] = $purchaseData['total_item'];

        // save the file
        if (isset($purchaseData['file'])) {
            $image = $purchaseData['file'];
            $purchaseData['file'] = uploadFile($image, $this->fileFolder);
        }

        $stock_id = $purchaseData['main_stock_id'] ?? null;

        unset($purchaseData['main_stock_id']);

        // Store the purchase
        $purchaseData['notes'] = $purchaseData['notes'] ?? "-";
        $purchaseDataAdd['tax_value'] = array_sum($itemsData['tax_value']);
        $purchaseDataAdd['discount'] = array_sum($itemsData['discount_value']);
        $purchase = $this->store($purchaseData + $purchaseDataAdd);

        // Save items
        $this->saveItems($itemsData, $purchase,$stock_id);


        // انشاء اذن اضافة مع الفاتورة
        if ($this->getStocksSetting()->create_additional_permit_with_purchase_bill) {
            // لو المنتجات هتتخزن في اكتر من مخزن يتم انشاء اكثر من اذن اضافة
            if ($stock_id == null){
                $this->saveStockMovement($itemsData, $purchaseData,$purchase);
            }
            // لو المنتجات هتتخزن في مخزن واحد يتم انشاء اذن اضافة واحد
            else{
                $this->saveStockMovementForOneStock($itemsData, $purchaseData,$purchase,$stock_id);
            }
        }
    }



    public function saveItems($itemsData, $purchase,$stock_id)
    {
        foreach ($itemsData['item_id'] as $key => $itemId) {
            $data = [
                'purchase_id' => $purchase->id,
                'item_id' => $itemId,
                'price' => $itemsData['price'][$key] - $itemsData['tax_value'][$key] + $itemsData['discount_value'][$key],
                'tax_per' => $itemsData['tax_per'][$key],
                'tax_value' => $itemsData['tax_value'][$key],
                'discount_per' => $itemsData['discount_per'][$key],
                'discount_value' => $itemsData['discount_value'][$key],
                'notes' => $itemsData['ItemNotes'][$key] ?? "لا يوجد",
                'final_total' => $itemsData['price'][$key],
                'amount' => $itemsData['amount'][$key],
                'stock_id' => $itemsData['stock_id'][$key] ?? $stock_id,
                'status' => PurchaseItemStatusEnum::New->value,
            ];
            $this->purchaseItemService->store($data);
        }

    }

    public function saveReturnAmount($itemsData)
    {
        foreach ($itemsData['purchase_item_id'] as $key => $itemId) {
            $item = $this->purchaseItemService->find($itemId);
            $this->purchaseItemService->update($itemId, ['return_amount' => ($item->return_amount + $itemsData['amount'][$key])]);
        }
    }

    public function getByTypeGroupBy(string $type, array $dataSearch)
    {
        return $this->model->where('type', $type)
            ->when($dataSearch["from_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", ">=", $dataSearch["from_date"]);
            })
            ->when($dataSearch["to_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", "<=", $dataSearch["to_date"]);
            })
            ->join('fn_purchase_items', 'fn_purchases.id', '=', 'fn_purchase_items.purchase_id')
            ->groupBy('fn_purchase_items.discount_per')->selectRaw('fn_purchase_items.discount_per, SUM(fn_purchase_items.final_total) as sum_value, SUM(fn_purchase_items.discount_value) as sum_total_discount_value')
            ->get();
    }

    public function getMinMaxDate(array $dataSearch)
    {
        return $this->model->where('type', PurchaseTypeEnum::Purchase->value)
            ->when($dataSearch["from_date"] ?? "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", ">=", $dataSearch["from_date"]);
            })
            ->when($dataSearch["to_date"] ?? "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", "<=", $dataSearch["to_date"]);
            })
            ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
            ->first();
    }


    // انشاء اذن اضافة في حالة اكثر من مخزن
    public function saveStockMovement($itemsData, $purchaseData,$purchase, $is_element = false)
    {
        // تقسيم المنتجات لكل مخزن
        $groupedItems = $this->groupItemsByStockId($itemsData);
        // dd($purchaseData['type']);
        $purchaseType = $purchaseData['type'];
        // انشاء امر اضافة لكل مخزن بالمنتجات اللي هتتضاف فيه
        foreach ($groupedItems as $stockId => $items) {

            // اعادة تشكيل الداتا للحفظ
            $purchaseData = $this->preparePurchaseDataForStock($purchaseData, $stockId);

            // حفظ اذن الاضافة
            $movement = $this->stockMovementService->store($purchaseData);

            // اعادة تشكيل الداتا للحفظ
            $itemsDataToSave = $this->prepareItemsDataForSaving($items);

            // حفظ المنتجات الخاصة باذن الاضافة
            $savedItems = $this->stockMovementService->saveMovementItems($itemsDataToSave, $movement, $is_element);

            // تحديث رصيد المخزن
            if ($purchaseType == PurchaseTypeEnum::Purchase->value) {
                $this->stockStatusService->updateItemsBalances($movement, $savedItems);
            } else {
                $this->stockStatusService->increaseItemsBalances($movement, $savedItems);
            }

            // ربط اذن الاضافة بالفاتورة
            $this->associateBillWithMovement($movement,$purchase);
        }
    }


    // انشاء اذن اضافة في حالة مخزن واحد
    public function saveStockMovementForOneStock($itemsData, $purchaseData,$purchase,$stock_id, $is_element = false)
    {
        $purchaseType = $purchaseData['type'];

        // اعادة تشكيل الداتا للحفظ
        $purchaseData = $this->preparePurchaseDataForStock($purchaseData,$stock_id);

        // حفظ اذن الاضافة
        $movement = $this->stockMovementService->store($purchaseData);

        // حفظ المنتجات الخاصة باذن الاضافة
        $savedItems = $this->stockMovementService->saveMovementItems($itemsData, $movement, $is_element);

        // تحديث رصيد المخزن
        if ($purchaseType == PurchaseTypeEnum::Purchase->value) {
            $this->stockStatusService->updateItemsBalances($movement, $savedItems);
        } else {
            $this->stockStatusService->increaseItemsBalances($movement, $savedItems);
        }

        // ربط اذن الاضافة بالفاتورة
        $this->associateBillWithMovement($movement,$purchase);
    }


    // تقسيم المنتجات لكل مخزن
    private function groupItemsByStockId($itemsData)
    {
        $groupedItems = [];

        foreach ($itemsData['stock_id'] as $key => $stockId) {
            $groupedItems[$stockId][] = [
                'item_id' => $itemsData['item_id'][$key],
                'one_price' => $itemsData['purchase_price'][$key],
                'sale_price' => $itemsData['sale_price'][$key],
                'amount' => $itemsData['amount'][$key],
                'price' => $itemsData['price'][$key],
                'ItemNotes' => $itemsData['ItemNotes'][$key],
            ];
        }

        return $groupedItems;
    }


    // ربط اذن الاضافة بالفاتورة
    public function associateBillWithMovement($movement,$purchase)
    {
        $data = [
            'stock_movement_id' => $movement->id,
            'bill_id'           => $purchase->id,
        ];
        $this->stockMovementPurchaseService->store($data);
    }

    // اعادة تشكيل الداتا للحفظ
    private function preparePurchaseDataForStock($purchaseData, $stockId)
    {
        $purchaseData['fn_stock_id'] = $stockId;

        // Remove unnecessary fields from purchase data
        $fieldsToUnset = [
            'supplier_bill_number',
            'supplier_id',
            'notes',
            'total_item',
            'file',
            'created_by_id',
            'credit_supplier_id',
            'final_total',
            'stock_id'
        ];
        $purchaseData['type'] = ($purchaseData['type'] == PurchaseTypeEnum::Purchase->value) ? StockMovementTypeEnum::PurchaseAdditional->value : StockMovementTypeEnum::SalePurchase->value;
        $purchaseData['code'] = $this->stockMovementService->lastCode();
        return $this->removeFieldsFromData($purchaseData, $fieldsToUnset);
    }

    // اعادة تشكيل الداتا للحفظ
    private function removeFieldsFromData($data, $fields)
    {
        foreach ($fields as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    // اعادة تشكيل الداتا للحفظ
    private function prepareItemsDataForSaving($items)
    {
        return [
            'item_id' => array_column($items, 'item_id'),
            'one_price' => array_column($items, 'one_price'),
            'sale_price' => array_column($items, 'sale_price'),
            'amount' => array_column($items, 'amount'),
            'price' => array_column($items, 'price'),
            'ItemNotes' => array_column($items, 'ItemNotes'),
        ];
    }

    public function deleteBill($bill)
    {
        $movementIds = $bill->movements()->pluck('stock_movement_id')->toArray();

        //        تقليل الكميات التي تم اضافتها من قبل لفاتورة الشراء قبل حذفها
        foreach ($movementIds as $move_id){
            $result = $this->stockMovementService->deleteMovement($move_id);
            if ($result['code'] == '422') {
                return jsonValid(['error'=>[$result['message']]]);
            }
        }
        if(isset($bill->constraint)){
            $bill->constraint()->delete();
        }
        // حذف الفاتورة
        $bill->delete();
        return jsonSuccess();
    }

    public function checkStockAmounts($bill, $purchaseData)
    {
        $movementIds = $bill->movements()->pluck('stock_movement_id')->toArray();

        foreach ($movementIds as $move_id){
            $result = $this->stockMovementService->checkStockAmounts($move_id, $purchaseData);
            if ($result['code'] == '422') {
                return [
                    'code' => '422',
                    'message' => $result['message']
                ];
            }
        }
        return [
            'code' => '200',
        ];
    }


    // Start Store Back Purchase //
    public function checkStockAvailability($stockIds, $items, $amounts)
    {
        foreach ($stockIds as $index => $stockId) {
            $amount = $amounts[$index];
            $itemId = $items[$index];
            $stock = $this->stockService->getWhereFirst(['id' => $stockId]);
            $item = $this->itemService->getWhereFirst(['id' => $itemId]);
            $stockName = $stock->name;
            $itemName = $item->name;
            $stock_status = $this->stockStatusService->getWhereFirst([
                'fn_stock_id' => $stockId,
            ]);

            if (!$stock_status) {
                return [
                    'error' => "stock_not_exist",
                    'stock_name' => $stockName,
                ];
            }

            $stock_items = $this->stockStatusService->getWhereFirst([
                'fn_stock_id' => $stockId,
                'item_id' => $itemId,
            ]);

            if (!$stock_items) {
                return [
                    'error' => "item_not_exist",
                    'stock_name' => $stockName,
                    'item_name' => $itemName,
                ];
            }

            if ($stock_items->amount < $amount) {
                return [
                    'error' => "insufficient_quantity",
                    'stock_name' => $stockName,
                    'item_name' => $itemName,
                    'available_amount' => $stock_items->amount,
                ];
            }
        }
    }


    public function storeBackPurchase($purchaseData, $itemsData)
    {
        // Set sales type and created by ID
        $purchaseData['type'] = PurchaseTypeEnum::BackPurchase->value;
        $purchaseData['created_by_id'] = auth('admin')->user()->id;
        // dd($itemsData->purchase_item_id);
        // Get client account ID
        $purchaseData['credit_supplier_id'] = $this->supplierService->getAccountByID($purchaseData['supplier_id'])->id;

        // Set the final total amount
        $purchaseData['final_total'] = $purchaseData['total_item'];

        // save the file
        if (isset($purchaseData['file'])) {
            $image = $purchaseData['file'];
            $purchaseData['file'] = uploadFile($image, $this->fileFolder);
        }

        $stock_id = $purchaseData['main_stock_id'] ?? null;

        unset($purchaseData['main_stock_id']);

        // Store the purchase
        $purchaseData['notes'] = $purchaseData['notes'] ?? "-";
        $purchaseDataAdd['tax_value'] = array_sum($itemsData['tax_value']);
        $purchaseDataAdd['discount'] = array_sum($itemsData['discount_value']);

        $purchase = $this->store($purchaseData + $purchaseDataAdd);
        if (isset($itemsData['purchase_id']) && $itemsData['purchase_id'] != null) {
            $this->purchaseReturnService->store([
                'purchase_id' => $itemsData['purchase_id'],
                'return_purchase_id' => $purchase->id,
            ]);
        }

        // Save items
        $this->saveItems($itemsData, $purchase,$stock_id);
        if ($itemsData->purchase_item_id) {
            $this->saveReturnAmount($itemsData);
        }

        // انشاء اذن اضافة مع الفاتورة
        if ($this->getStocksSetting()->create_additional_permit_with_back_purchase_bill) {
            // لو المنتجات هتتخزن في اكتر من مخزن يتم انشاء اكثر من اذن اضافة
            if ($stock_id == null){
                $this->saveStockMovement($itemsData, $purchaseData,$purchase);
            }
            // لو المنتجات هتتخزن في مخزن واحد يتم انشاء اذن اضافة واحد
            else{
                $this->saveStockMovementForOneStock($itemsData, $purchaseData,$purchase,$stock_id);
            }
        }
    }

    // End Store Back Purchase //

}
