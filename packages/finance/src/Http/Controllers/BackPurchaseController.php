<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\PurchaseTypeEnum;
use Nami\Finance\Http\Requests\PurchasesRequest as ObjRequest;
use Nami\Finance\Services\PurchaseOrderService;
use Nami\Finance\Services\PurchaseService as ObjService;
use Yajra\DataTables\DataTables;

class BackPurchaseController extends Controller
{
    public string $folderPath = "finance::finance.back_purchases";
    public $postData = ['code','supplier_bill_number','supplier_id','date',
        'notes','total_item','file','main_stock_id'];
    public string $mainRoute = "finance.back-purchases";


    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getBackPurchaseTable();

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
                    $deleteButton = "";
                    $editButton = "";
                    $transferButton = "";
                    $detailsButton = "";
                    $confirmButton = "";

                    if ($row->is_confirmed == 0 ) {
//                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
//                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                        $confirmButton = (!$row->is_confirmed) ? confirmBtn(route('finance.confirm-bill-purchase.update',$row->id)) : '';
                    }
                    $detailsButton = editHyperButton(route($this->mainRoute . ".show", $row->id), $row->name, "eye", true);
                    $printButton = printButton(route("finance.back-purchases-print.show", $row->id), null, 'finance.back-purchases-print-read');
                    return  $confirmButton." ".$detailsButton . " " . $editButton . ' ' . $printButton . " " . $deleteButton . " " . $transferButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.back_purchases");
        $data["addButtonText"] = __("finance::finance.back_purchases");
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
        $data['lastCode'] = $service->getLastCodeOfType(PurchaseTypeEnum::BackPurchase->value);
        $data['items'] = $service->getItemsExcept();
        $data['stocks'] = $service->getStocks();
        $data['stock_setting'] = $service->getStocksSetting();
        $data["bladeTitle"] = __("finance::finance.back_purchase");
        return view($this->folderPath . '.create', $data);
    }

    public function store(ObjRequest $request, ObjService $service, PurchaseOrderService $purchaseOrderService)
    {
        $purchaseData = $request->only($this->postData);
        $stock = $service->checkStockAvailability($request['stock_id'], $request['item_id'], $request['amount']);
        // dd($stock);
        if ($stock != null && $stock['error'] == "stock_not_exist") {
            return jsonValid(['error' => [__("finance::finance.stock_not_exist", ['stock_id' => $stock['stock_name']])]], '');
        } elseif ($stock != null && $stock['error'] == "item_not_exist") {
            return jsonValid(['error' => [__("finance::finance.item_not_exist_in_stock", ['stock_id' => $stock['stock_name'], 'item_id' => $stock['item_name']])]], '');
        } elseif ($stock != null && $stock['error'] == "insufficient_quantity") {
            return jsonValid(['error' => [__("finance::finance.insufficient_quantity", ['stock_id' => $stock['stock_name'], 'item_id' => $stock['item_name'], 'available_amount' => $stock['available_amount']])]], '');
        }
        // dd('sss');
        if (isset($request->purchase_order)){
            $purchaseOrderService->transferPurchaseOrderToBillPurchase($request->purchase_order);
        }

        $service->storeBackPurchase($purchaseData, $request);
        return jsonSuccess(null, null, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, ObjService $service,request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $service->find($id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        else{
            $obj = $service->find($id);
            $bladeTitle = __('finance::finance.back_purchase');
            return view($this->folderPath . ".show",compact('obj','bladeTitle'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id,ObjService $service)
    {
        $obj = $service->find($id);
        $service->deleteBill($obj);
        return jsonSuccess();
    }
}
