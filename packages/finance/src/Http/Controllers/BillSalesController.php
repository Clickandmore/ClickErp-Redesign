<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\SalesTypeEnum;
use Nami\Finance\Http\Requests\SalesOrderRequest as ObjRequest;
use Nami\Finance\Http\Requests\SalesRequest;
use Nami\Finance\Services\BillSalesService as ObjService;
use Nami\Finance\Services\SalesOrderService;
use Yajra\DataTables\DataTables;

class BillSalesController extends Controller
{
    public string $folderPath = "finance::finance.bill_sales";
    public $postData = ['code', 'client_id', 'date', 'notes', 'total_item', 'payment_type', 'main_stock_id'];
    public string $mainRoute = "finance.bill-sales";


    public function index(Request $request, ObjService $service)
    {
        $dataTable = $service->getDataTableOfBill($request);
        if ($request->ajax()) {
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


            if ($request->filled('client_id')) {
                $stock = $request->client_id;
                $dataTable = $dataTable->where('client_id', $stock);
            }


            if ($request->filled('item_id')) {
                $item = $request->item_id;
                $dataTable = $dataTable->whereHas('items', function ($query) use ($item) {
                    $query->where('item_id', $item);
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
                ->addColumn('profit_margin', function ($row) {
                    return @$row->profit_with_margin;
                })
                ->editColumn('zatca_status', function ($row) {
                    if ($row->zatca) {
                        return match ($row->zatca->zatca_status) {
                            'REPORTED' => '<span class="badge bg-success"><i class="fa fa-check-circle m-lg-1"></i> REPORTED </span>',
                            'CLEARED' => '<span class="badge bg-primary"><i class="fa fa-check-circle m-lg-1"></i> CLEARED</span>',
                            'NOT_REPORTED' => '<span title="' . $row->error_message . '" class="badge bg-danger">NOT_REPORTED</span>',
                            'NOT_CLEARED' => '<span title="' . $row->error_message . '" class="badge bg-danger">NOT_CLEARED</span>',
                            default => 'لم يتم تصديرها',
                        };
                    }
                    return 'لم يتم تصديرها';
                })
                ->addColumn('actions', function ($row) {
                    $imageButton = showButton(route("finance.bill-sales-attachments.edit", $row->id), $row->name,"image", 'warning');
                    $printButton = printButton(route("finance.bill-sales-print.show", $row->id), null, 'finance.bill-sales-print-read');
                    $detailsButton = editHyperButton(route($this->mainRoute . ".show", $row->id), $row->name, "eye", true);
                    $shareButton = (!$row->zatca || ($row->zatca->zatca_status !== "REPORTED" && $row->zatca->zatca_status !== "CLEARED"))
                        ? shareZatcaBtn($row->id)
                        : '';
                    $downloadXMLButton = ($row->zatca && ($row->zatca->xml_file !== null))
                        ? downloadFileBtn(showFile($row->zatca->xml_file), "{$row->id}فاتورة_رقم_")
                        : '';
                    $confirmButton = (!$row->is_confirmed) ? confirmBtn(route('finance.confirm-bill-sales.update',$row->id)) : '';



                    return implode(' ', [$downloadXMLButton,$confirmButton, $imageButton, $printButton, $shareButton, $detailsButton]);
                })
                ->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index", request()->query());
        $data["bladeTitle"] = __("finance::finance.bill-sales");
        $data["addButtonText"] = __("finance::finance.bill-sale");
        $data["clients"] = $service->getClients();
        $data["items"] = $service->getItems();
        $data["modalType"] = "modal-xl";
        return view($this->folderPath . '.index', $data);
    }

    public function create(Request $request, ObjService $service)
    {
        $data['storeRoute'] = route($this->mainRoute . ".store");
        $data['clients'] = $service->getClients();
        $data['lastCode'] = $service->getLastCodeOfType(SalesTypeEnum::Sales->value);
        $data['items'] = $service->getItems();
        $data['stocks'] = $service->getStocks();
        $data['stock_setting'] = $service->getStocksSetting();
        $data["bladeTitle"] = __("finance::finance.bill-sale");
        return view($this->folderPath . '.create', $data);
    }


    public function store(SalesRequest $request, ObjService $service,SalesOrderService $salesOrderService)
    {
        $salesData = $request->only($this->postData);
        if (isset($request->sales_order)){
            $salesOrderService->transferSaleOrderToBillSale($request->sales_order);
        }
        $sale = $service->storeSales($salesData, $request);
//        $service->checkIsConfirmSetting($sale, ConfirmSettingTypes::BillSales->value);
        return jsonSuccess(null, null, 201);
    }


    public function show(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . '.show')->with([
                "obj" => $service->find($id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        } else {
            $obj = $service->find($id);
            $bladeTitle = __('finance::finance.bill-sales');
            return view($this->folderPath . ".show", compact('obj', 'bladeTitle'));
        }
    }

    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
                'clients' => $service->getClients(),
                'items' => $service->getItems(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->updateSalesOrder($id, $dataInsert);
        return jsonSuccess($data);
    }


    public function destroy(int $id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();

    }
}
