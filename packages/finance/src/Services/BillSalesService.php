<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Enums\SalesItemStatusEnum;
use Nami\Finance\Enums\SalesTypeEnum;
use Nami\Finance\Enums\StockMovementTypeEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Sale;
use Nami\Finance\Models\StockStatus;

//use App\Services\Zatca\ShareInvoiceZatcaService;

class BillSalesService extends MainService
{

    public function __construct(
        Sale                               $model,
        protected ItemService              $itemService,
        protected ConfirmSettingService    $confirmSettingService,
        protected BillSalesItemService     $billSalesItemService,
        protected ConfirmBillSaleService   $confirmBillSaleService,
        protected StockService             $stockService,
        protected StockMovementSaleService $stockMovementSaleService,
//        protected ShareInvoiceZatcaService $shareInvoiceZatcaService,
        protected StockMovementService     $stockMovementService,
        protected ClientService            $clientService,
        protected SalesOrderItemService    $salesOrderItemService
    )
    {
        $this->model = $model;
        $this->fileFolder = 'bill_sales/';
        $this->files = ['file'];
    }

    public function getClients()
    {
        return $this->clientService->get();
    }

    public function getStocks()
    {
        return $this->stockService->getActive();
    }

    public function getStocksSetting()
    {
        return $this->stockMovementService->stockSetting();
    }

    public function getItems()
    {
        return $this->itemService->get();
    }

    public function getByTypeGroupBy($type,$dataSearch)
    {
        return $this->model->where('type', $type)
            ->when($dataSearch["from_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", ">=", $dataSearch["from_date"]);
            })
            ->when($dataSearch["to_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", "<=", $dataSearch["to_date"]);
            })
            ->join('fn_sale_items', 'fn_sales.id', '=', 'fn_sale_items.sale_id')
            ->groupBy('fn_sale_items.tax_per')->selectRaw('fn_sale_items.tax_per, SUM(fn_sale_items.final_total) as sum_value, SUM(fn_sale_items.tax_value) as sum_total_tax_value')
            ->get();
    }

    public function getMinMaxDate(array $dataSearch)
    {
        return $this->model->where('type', SalesTypeEnum::Sales->value)
            ->when($dataSearch["from_date"] ?? "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", ">=", $dataSearch["from_date"]);
            })
            ->when($dataSearch["to_date"] ?? "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", "<=", $dataSearch["to_date"]);
            })
            ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
            ->first();
    }

    public function getLastCodeOfType($type)
    {
        $row = $this->model->where('type', $type)->orderBy('code', 'DESC')->first();
        return (isset($row->code)) ? ($row->code + 1) : 1;
    }

    public function getByType($type,$dataSearch)
    {
        return $this->model->where('type', $type)
            ->when($dataSearch["from_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", ">=", $dataSearch["from_date"]);
            })
            ->when($dataSearch["to_date"] != "", function ($q) use ($dataSearch) {
                return $q->whereDate("date", "<=", $dataSearch["to_date"]);
            })->get();
    }

    public function storeSales($salesData, $itemsData)
    {
        // حساب الخصم الاجمالي والضريبة الاجمالية للفاتورة
        $salesData = $this->sumDiscountAndTax($salesData, $itemsData);

        DB::transaction(function () use ($salesData, $itemsData) {
            // نوع الفاتورة كمبيعات
            $salesData['type'] = SalesTypeEnum::Sales->value;
            // حفظ الادمن القائم بالاضافة للفاتورة
            $salesData['created_by_id'] = auth('admin')->user()->id;

            // الحصول علي حساب العميل
            $salesData['debit_client_id'] = $this->clientService->getAccountByID($salesData['client_id'])->id;

            // حساب الخصم الاجمالي والضريبة الاجمالية للفاتورة
            $salesData = $this->sumDiscountAndTax($salesData, $itemsData);

            // حفظ السعر الاجمالي الاخير والسعر قبل الخصم والضريبة كان كام
            $salesData['final_total'] = $salesData['total_item'];
            $salesData['total_item'] = $this->getTotalPriceBeforeDiscountAndTax($itemsData);


            // Set the stock id
            $stock_id = $salesData['main_stock_id'] ?? null;

            unset($salesData['main_stock_id']);


            // Store the sale
            $sale = $this->store($salesData);

            // انشاء اذن صرف مع الفاتورة
            if ($this->getStocksSetting()->auto_create_stock_movement_with_bill_sale) {
                // لو المنتجات هتتخزن في اكتر من مخزن يتم انشاء اكثر من اذن اضافة
                if ($stock_id == null) {
                    $this->saveStockMovement($itemsData, $salesData, $sale);
                } // لو المنتجات هتتخزن في مخزن واحد يتم انشاء اذن اضافة واحد
                else {
                    $this->saveStockMovementForOneStock($itemsData, $salesData, $sale, $stock_id);
                }
            }

            // Decrement the stock for each item sold
            $this->decrementItemsFromStock($itemsData, $sale, $stock_id);


            // مشاركة الفاتورة مع هيئة الزكاة
            if ($this->getStocksSetting()->sync_with_zatca) {
                $this->shareInvoiceWithZatca($sale);
            }
        });
    }

    public function checkIsConfirmSetting($data, $type)
    {
        $isConfirm = $this->confirmSettingService->getConfirmSetting($type);
        if (isset($isConfirm) && $isConfirm->value == "off") {
            $this->confirmBillSaleService->confirmBillSale($data->id);
        }
    }


    // انشاء اذن اضافة في حالة اكثر من مخزن
    public function saveStockMovement($itemsData, $purchaseData, $purchase)
    {
        // تقسيم المنتجات لكل مخزن
        $groupedItems = $this->groupItemsByStockId($itemsData);

        // انشاء امر اضافة لكل مخزن بالمنتجات اللي هتتضاف فيه
        foreach ($groupedItems as $stockId => $items) {

            // اعادة تشكيل الداتا للحفظ
            $purchaseData = $this->preparePurchaseDataForStock($purchaseData, $stockId);

            // حفظ اذن الاضافة
            $movement = $this->stockMovementService->store($purchaseData);

            // اعادة تشكيل الداتا للحفظ
            $itemsDataToSave = $this->prepareItemsDataForSaving($items);

            // حفظ المنتجات الخاصة باذن الاضافة
            $this->stockMovementService->saveMovementItems($itemsDataToSave, $movement);

            // ربط اذن الاضافة بالفاتورة
            $this->associateBillWithMovement($movement, $purchase);
        }
    }


    // انشاء اذن اضافة في حالة مخزن واحد
    public function saveStockMovementForOneStock($itemsData, $salesData, $sale, $stock_id)
    {
        // اعادة تشكيل الداتا للحفظ
        $salesData = $this->preparePurchaseDataForStock($salesData, $stock_id);

        // حفظ اذن الاضافة
        $movement = $this->stockMovementService->store($salesData);

        $itemsData = $this->getPricesOfItems($itemsData);

        // حفظ المنتجات الخاصة باذن الاضافة
        $this->stockMovementService->saveMovementItems($itemsData, $movement);

        // ربط اذن الاضافة بالفاتورة
        $this->associateBillWithMovement($movement, $sale);
    }

    private function getPricesOfItems($itemsData)
    {

        $itemsArray = $itemsData->all();

        $preparedItems = [
            'item_id' => [],
            'one_price' => [],
            'amount' => [],
            'price' => [],
            'total_purchase_price' => [],
        ];

        foreach ($itemsArray['item_id'] as $index => $itemId) {
            $preparedItems['item_id'][] = $itemId;
            $preparedItems['one_price'][] = $itemsArray['one_price'][$index];
            $preparedItems['amount'][] = -$itemsArray['amount'][$index];
            $preparedItems['price'][] = $itemsArray['total_purchase_price'][$index];
        }

        return $preparedItems;
    }

    private function preparePurchaseDataForStock($purchaseData, $stockId)
    {
        $purchaseData['fn_stock_id'] = $stockId;

        // Remove unnecessary fields from purchase data
        $fieldsToUnset = [
            'payment_type',
            'client_id',
            'debit_client_id',
            'notes',
            'total_item',
            'file',
            'created_by_id',
            'credit_supplier_id',
            'final_total',
            'discount',
            'total_tax_value',
            'stock_id'
        ];
        $purchaseData['type'] = StockMovementTypeEnum::SalePurchase->value;
        $purchaseData['code'] = $this->stockMovementService->lastCode();
        return $this->removeFieldsFromData($purchaseData, $fieldsToUnset);
    }


    public function decrementItemsFromStock($itemsData, $sale, $stock_id)
    {
        foreach ($itemsData['item_id'] as $key => $itemId) {
            $item = $this->itemService->find($itemId);
            $requestedAmount = $itemsData['amount'][$key]; // الكمية المطلوبة من المنتج
            $stockId = $itemsData['stock_id'][$key] ?? $stock_id; // مخزن صرف الكمية للمنتج ده

            if($item->type != CategoryTypes::Service){

                // Decrement stock based on requested amount
                $this->decrementStockQuantity($itemId, $stockId, $requestedAmount, $itemsData, $sale, $key);
            }
            else{
                $this->processSale($sale, $itemsData, $requestedAmount, $key, $itemId);
            }
        }
    }

    public function associateBillWithMovement($movement, $sale)
    {
        $data = [
            'stock_movement_id' => $movement->id,
            'bill_id' => $sale->id,
        ];
        $this->stockMovementSaleService->store($data);
    }

    private function decrementStockQuantity($itemId, $stockId, &$requestedAmount, $itemsData, $sale, $key)
    {
        // الكميات المتاحة للمنتج ده في المخزن ده (بيستجلوا في اكتر من صف في حالة السعر مختلف)
        $stockQuantities = StockStatus::where([
            ['item_id', $itemId],
            ['fn_stock_id', $stockId],
        ])->orderBy('created_at')->get();
//
//        // لو مفيش سجلات خالص للمنتج في المخزن (في حالة طلب خدمة)
//        if ($stockQuantities->isEmpty()) {
//            $item = $this->itemService->find($itemId);
//            StockStatus::create([
//                'item_id' => $itemId,
//                'fn_stock_id' => $stockId,
//                'amount' => $requestedAmount,
//                'price' => $item->purchase_price,
//            ]);
//
//            // ممكن تحدث قيمة $stockQuantities بعد الإنشاء
//            $stockQuantities = StockStatus::where([
//                ['item_id', $itemId],
//                ['fn_stock_id', $stockId],
//            ])->orderBy('created_at')->get();
//        }

        foreach ($stockQuantities as $stock) {
            // لو تم تغطيه الصرف يخرج من اللوب
            if ($requestedAmount <= 0) {
                return;
            }

            // لو الكمية كلها متوفرة في المخزن ده
            if ($stock->amount > 0) {
                // لو الرو بتاع المخزن ده يقدر يغطي الكمية المطلوبة
                if ($stock->amount >= $requestedAmount) {
                    // احفظ العنصر في تفاصيل الفاتورة بالكمية اللي ت صرفها
                    $this->processSale($sale, $itemsData, $requestedAmount, $key, $itemId, $stock);

                    // قلل الكمية اللي تم صرفها
                    $stock->decrement('amount', $requestedAmount);
                    $requestedAmount = 0; // Reset requested amount after fulfillment
                } else {
                    // لو الكمية مش هتغطي الطلب يبقي قلل الكمية المطلوبة من الصف وخش علي الصف اللي بعده
                    $requestedAmount -= $stock->amount; // Reduce requested amount
                    $this->processSale($sale, $itemsData, $stock->amount, $key, $itemId, $stock);

                    // Decrement the whole quantity from stock
                    $stock->decrement('amount', $stock->amount);
                }
            }
        }
    }

    private function processSale($sale, $itemsData, $amount, $key, $itemId, $stock = null)
    {
        $totalPriceBeforeTaxAndDiscount = $itemsData['sale_price'][$key] * $amount;
        $totalPriceAfterTaxAndDiscount  = $totalPriceBeforeTaxAndDiscount - $itemsData['discount_value'][$key] + $itemsData['tax_value'][$key];
        // Prepare the sales item data
        $data = [
            'sale_id' => $sale->id,
            'item_id' => $itemId,
            'price' => $itemsData['sale_price'][$key],
            'discount_per' => $itemsData['discount_per'][$key],
            'discount_value' => $itemsData['discount_value'][$key],
            'tax_per' => $itemsData['tax_per'][$key],
            'tax_value' => $itemsData['tax_value'][$key],
            'total_price_before' => $totalPriceBeforeTaxAndDiscount,
            'final_total' => $totalPriceAfterTaxAndDiscount,
            'purchase_price' => $stock ? $stock->price : $this->itemService->find($itemId)->purchase_price,
            'amount' => $amount,
            'stock_id' => $stock->fn_stock_id ?? null,
            'status' => SalesItemStatusEnum::New->value,
        ];

        // Save the sales item data
        $this->billSalesItemService->store($data);
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
                'amount' => -$itemsData['amount'][$key],
                'price' => $itemsData['price'][$key],
                'total_purchase_price' => $itemsData['total_purchase_price'][$key],
            ];
        }

        return $groupedItems;
    }

    // مسح الفيلدات اللي ملهاش لازمة
    private function removeFieldsFromData($data, $fields)
    {
        foreach ($fields as $field) {
            unset($data[$field]);
        }

        return $data;
    }


    private function prepareItemsDataForSaving($items)
    {
        return [
            'item_id' => array_column($items, 'item_id'),
            'one_price' => array_column($items, 'one_price'),
            'amount' => array_column($items, 'amount'),
            'price' => array_column($items, 'total_purchase_price'),
            'ItemNotes' => array_column($items, 'ItemNotes'),
        ];
    }


    // مشاركة الفاتورة مع هيئة الزكاة
    public function shareInvoiceWithZatca($sale)
    {
        $this->shareInvoiceZatcaService->shareInvoice($sale->id);
    }

    public function getDataTableOfBill($request)
    {
        $query = $this->model->query();

        // Check if the `shared` parameter is set and filter accordingly
        if ($request->filled('shared')) {
            if ($request->shared === '1') {
                // Filter rows with a non-null `zatca_invoice_id`
                $query->whereNotNull('zatca_invoice_id');
            } elseif ($request->shared === '0') {
                // Filter rows with a null `zatca_invoice_id`
                $query->whereNull('zatca_invoice_id');
            }
        }

        return $query;
    }

    public function getInvoiceDocumentData(int $id): array
    {
        $sale = $this->model
            ->with([
                'client',
                'items.item',
                'zatca',
                'saleAttachments',
                'sourceInvoice',
            ])
            ->findOrFail($id);

        $warnings = [];
        $lines = $sale->items->map(function ($item, int $index) use (&$warnings) {
            $quantity = (float) $item->amount;
            $unitPrice = (float) $item->price;
            $discount = (float) $item->discount_value;
            $taxRate = (float) $item->tax_per;
            $taxAmount = (float) $item->tax_value;
            $exclusiveAmount = round(($unitPrice * $quantity) - $discount, 2);
            $inclusiveAmount = round($exclusiveAmount + $taxAmount, 2);
            $expectedTax = round($exclusiveAmount * ($taxRate / 100), 2);

            if (abs($expectedTax - $taxAmount) > 0.01) {
                $warnings[] = sprintf(
                    'ضريبة البند رقم %d لا تطابق النسبة المسجلة: المتوقع %.2f ر.س والمسجل %.2f ر.س.',
                    $index + 1,
                    $expectedTax,
                    $taxAmount
                );
            }

            if (abs($inclusiveAmount - (float) $item->final_total) > 0.01) {
                $warnings[] = sprintf(
                    'إجمالي البند رقم %d لا يطابق السعر والخصم والضريبة المسجلة.',
                    $index + 1
                );
            }

            return [
                'code' => $item->item?->code ?: ($index + 1),
                'description' => $item->item?->name ?: 'بند مبيعات',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'exclusive_amount' => $exclusiveAmount,
                'inclusive_amount' => $inclusiveAmount,
            ];
        });

        $calculated = [
            'subtotal' => round((float) $lines->sum(
                fn (array $line) => ($line['unit_price'] * $line['quantity'])
            ), 2),
            'discount' => round((float) $lines->sum('discount'), 2),
            'tax_exclusive' => round((float) $lines->sum('exclusive_amount'), 2),
            'tax' => round((float) $lines->sum('tax_amount'), 2),
            'payable' => round((float) $lines->sum('inclusive_amount'), 2),
        ];

        $stored = [
            'subtotal' => round((float) $sale->total_item, 2),
            'discount' => round((float) $sale->discount, 2),
            'tax_exclusive' => round((float) $sale->total_item - (float) $sale->discount, 2),
            'tax' => round((float) $sale->total_tax_value, 2),
            'payable' => round((float) $sale->final_total, 2),
        ];

        foreach (['subtotal', 'discount', 'tax', 'payable'] as $field) {
            if (abs($stored[$field] - $calculated[$field]) > 0.01) {
                $warnings[] = 'إجماليات الفاتورة المحفوظة لا تطابق مجموع البنود.';
                break;
            }
        }

        return [
            'obj' => $sale,
            'invoiceLines' => $lines,
            'invoiceTotals' => $stored,
            'invoiceWarnings' => array_values(array_unique($warnings)),
        ];
    }


    public function sumDiscountAndTax($salesData, $itemsData)
    {
        $salesData['discount'] = array_sum($itemsData['discount_value']);
        $salesData['total_tax_value'] = array_sum($itemsData['tax_value']);
        return $salesData;
    }

    public function getTotalPriceBeforeDiscountAndTax($itemsData)
    {
        $totalPrice = 0;

        // Ensure 'sale_price' and 'amount' are arrays
        if (is_array($itemsData['sale_price']) && is_array($itemsData['amount'])) {
            $count = count($itemsData['sale_price']);

            for ($i = 0; $i < $count; $i++) {
                $totalPrice += $itemsData['sale_price'][$i] * $itemsData['amount'][$i];
            }
        }

        return $totalPrice;
    }

}
