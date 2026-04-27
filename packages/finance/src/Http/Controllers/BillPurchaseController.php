<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Http\Requests\PurchasesRequest as ObjRequest;
use Nami\Finance\Services\PurchaseOrderService;
use Nami\Finance\Services\PurchaseService as ObjService;
use Yajra\DataTables\DataTables;

class BillPurchaseController extends Controller
{
    public string $folderPath = "finance::finance.bill_purchases";
    public $postData = ['code', 'supplier_bill_number', 'supplier_id', 'date',
        'notes', 'total_item', 'file', 'main_stock_id'];
    public string $mainRoute = "finance.bill-purchases";


    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getPurchaseTable();

            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            // Apply date filters based on which dates are provided
            if ($fromDate && $toDate) {
                // Both from_date and to_date are defined
                $dataTable->whereBetween('date', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                // Only from_date is defined
                $dataTable->where('date', '>=', $fromDate);
            } elseif ($toDate) {
                // Only to_date is defined
                $dataTable->where('date', '<=', $toDate);
            }

            if ($request->filled('code')) {
                $code = $request->code;
                $dataTable = $dataTable->where('code', $code);
            }


            if ($request->filled('supplier_id')) {
                $supplier = $request->supplier_id;
                $dataTable = $dataTable->where('supplier_id', $supplier);
            }


            if ($request->filled('item_id')) {
                $item = $request->item_id;
                $dataTable = $dataTable->whereHas('purchaseItem', function ($query) use ($item) {
                    $query->where('item_id', $item);
                });
            }
            if ($request->filled('stock_id')) {
                $item = $request->stock_id;
                $dataTable = $dataTable->whereHas('purchaseItem', function ($query) use ($item) {
                    $query->where('stock_id', $item);
                });
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('code', function ($row) {
                    return '<a href="' . route($this->mainRoute . ".show", $row->id) . '"
                             class="dropdown-item d-flex align-items-center gap-2" target="_blank"> <span class="badge bg-dark">' . $row->code . '</span>
                            </a>';
                })
                ->editColumn('client_id', function ($row) {
                    return $row->client->name ?? "";
                })
                ->addColumn('the_stock', function ($row) {
                    return $row->stocksNames() ?? ""; // Call the method stocksNames()
                })
                ->editColumn('supplier_id', function ($row) {
                    return $row->supplier->name ?? '';
                })
                ->addColumn('total_before_all', function ($row) {
                    // Ensure numeric values to avoid errors
                    $finalTotal = is_numeric($row->final_total) ? $row->final_total : 0;
                    $taxValue = is_numeric($row->tax_value) ? $row->tax_value : 0;
                    $discount = is_numeric($row->discount) ? $row->discount : 0;

                    // Calculate total before all adjustments
                    $totalBeforeAll = $finalTotal - $taxValue + $discount;

                    // Format the result using number_format
                    return number_format($totalBeforeAll, 2); // Adjust decimals as needed
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-dark">
                                        <i class="fa fa-cogs"></i> الإجراءات
                                    </button>
                                <button type="button" class="btn btn-sm btn-dark dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                </button>
                                <ul class="dropdown-menu dropdown-menu-dark border-0 shadow py-3 px-2" style="min-width: 220px;">';
                    if ($row->is_confirmed == 0) {
                        $actions .= '<li>
                                        <button type="button" class="dropdown-item py-2 rounded d-flex align-items-center gap-2 confirmButton"
                                            confirm-route="' . route('finance.confirm-bill-purchase.update', $row->id) . '">
                                            <i class="fa fa-check text-success"></i> تأكيد الفاتورة
                                        </button>
                                    </li>';
                    }
                    $actions .= '<li>
                                    <a href="' . route($this->mainRoute . ".show", $row->id) . '"
                                       class="dropdown-item py-2 rounded d-flex align-items-center gap-2"
                                       target="_blank">
                                        <i class="fa fa-eye text-info"></i> التفاصيل
                                    </a>
                                </li>';
                    $actions .= '<li>
                                    <a href="' . route($this->mainRoute . ".edit", $row->id) . '"
                                       class="dropdown-item py-2 rounded d-flex align-items-center gap-2">
                                        <i class="fa fa-edit text-primary"></i> تعديل
                                    </a>
                                </li>';
                    $actions .= '<li>
                                    <a href="' . route("finance.bill-purchases-print.show", $row->id) . '"
                                       class="dropdown-item py-2 rounded d-flex align-items-center gap-2"
                                       target="_blank">
                                        <i class="fa fa-print text-success"></i> طباعة
                                    </a>
                                </li>';
                    if ($row->purchaseItem->contains(fn($item) => $item->amount > $item->return_amount)) {
                        $actions .= '<li>
                                        <a href="' . route("finance.convert-purchases.show", $row->id) . '"
                                           class="dropdown-item py-2 rounded d-flex align-items-center gap-2"
                                           target="_blank">
                                            <i class="fa fa-sync text-warning"></i> إرجاع المشتريات
                                        </a>
                                    </li>';
                    }
                    $actions .= '<li>
                                    <button type="button" class="dropdown-item py-2 rounded text-danger d-flex align-items-center gap-2 deleteButton"
                                        delete-route="' . route($this->mainRoute . ".destroy", $row->id) . '">
                                        <i class="fa fa-trash-o"></i> حذف
                                    </button>
                                </li>';
                    $actions .= '</ul></div>';
                    return $actions;
                })
                ->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.bill_purchases");
        $data["addButtonText"] = __("finance::finance.bill_purchases");
        $data["suppliers"] = $service->getSuppliers();
        $data["items"] = $service->getItems();
        $data["stocks"] = $service->getStocks();
        $data["modalType"] = "modal-xl";
        return view($this->folderPath . '.index', $data);
    }

    public function create(Request $request, ObjService $service)
    {
        $data['storeRoute'] = route($this->mainRoute . ".store");
        $data['suppliers'] = $service->getSuppliers();
        $data['lastCode'] = $service->getLastCodeOfType(PurchaseTypeEnum::Purchase->value);
        $data['items'] = $service->getItemsExcept();
        $data['stocks'] = $service->getStocks();
        $data['stock_setting'] = $service->getStocksSetting();
        $data["bladeTitle"] = __("finance::finance.bill_purchase");
        return view($this->folderPath . '.create', $data);
    }


    public function store(ObjRequest $request, ObjService $service, PurchaseOrderService $purchaseOrderService)
    {
        $purchaseData = $request->only($this->postData);
        if (isset($request->purchase_order)) {
            $purchaseOrderService->transferPurchaseOrderToBillPurchase($request->purchase_order);
        }
        $service->storePurchase($purchaseData, $request);
        return jsonSuccess(null, null, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, ObjService $service, request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $service->find($id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        } else {
            $obj = $service->find($id);
            $bladeTitle = __('finance::finance.bill_purchase');
            return view($this->folderPath . ".show", compact('obj', 'bladeTitle'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id, ObjService $service)
    {
        $data['updateRoute'] = route($this->mainRoute . ".update", $id);
        $data['suppliers'] = $service->getSuppliers();
        $data['items'] = $service->getItemsExcept();
        $data['stocks'] = $service->getStocks();
        $data['stock_setting'] = $service->getStocksSetting();
        $data["bladeTitle"] = __("finance::finance.bill_purchase");
        $data['obj'] = $service->find($id);
        return view($this->folderPath . '.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ObjRequest $request, string $id, ObjService $service, PurchaseOrderService $purchaseOrderService)
    {
        $purchaseData = $request->only($this->postData);

        $bill = $service->find($id);

        // bill has no movements
        if ($bill->movements()->count() == 0) {
            if (isset($request->purchase_order)) {
                $purchaseOrderService->transferPurchaseOrderToBillPurchase($request->purchase_order);
            }
            $service->storePurchase($purchaseData, $request);
            $service->deleteBill($bill);
            return jsonSuccess(['url' => route($this->mainRoute . ".index")], null, 203);
        }

        // bill has movements
        $result_check = $service->checkStockAmounts($bill, $request->validated());
        if ($result_check['code'] == '422') {
            return jsonValid(['error' => $result_check['message']]);
        }


        // create new bill
        if (isset($request->purchase_order)) {
            $purchaseOrderService->transferPurchaseOrderToBillPurchase($request->purchase_order);
        }
        $purchaseData['code'] = $service->getLastCodeOfType(PurchaseTypeEnum::Purchase->value);
        $service->storePurchase($purchaseData, $request);

        $obj = $service->find($id);
        $service->deleteBill($obj); //  delete old bill
        return jsonSuccess(['url' => route($this->mainRoute . ".index")], null, 203);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, ObjService $service)
    {
        $obj = $service->find($id);
        return $service->deleteBill($obj);
    }
}
