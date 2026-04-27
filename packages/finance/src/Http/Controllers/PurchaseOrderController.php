<?php

namespace Nami\Finance\Http\Controllers;

use Nami\Finance\Http\Requests\PurchaseOrderRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ClientStatusEnum;
use Nami\Finance\Enums\PurchaseOrderStatusEnum;
use Nami\Finance\Http\MainController;
use Nami\Finance\Services\PurchaseOrderService as ObjService;
use Yajra\DataTables\DataTables;

class PurchaseOrderController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "finance::finance.purchase_orders";
        $this->mainRoute = "finance.purchase-orders";
        $this->action = $action;
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $dataTable = $action->getDataTable();
            if ($request->filled('from_date') && $request->filled('to_date')) {
                $fromDate = $request->from_date;
                $toDate = $request->to_date;
                $dataTable = $dataTable->whereBetween('date', [$fromDate, $toDate]);
            }

//            if ($request->filled('code')) {
//                $code = $request->code;
//                $dataTable = $dataTable->where('code', $code);
//            }

            if ($request->filled('supplier_id')) {
                $stock = $request->supplier_id;
                $dataTable = $dataTable->where('supplier_id', $stock);
            }

            if ($request->filled('item_id')) {
                $item = $request->item_id;
                $dataTable = $dataTable->whereHas('purchaseOrderItems', function ($query) use ($item) {
                    $query->where('item_id', $item);
                });
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('supplier_id', function ($row) {
                    return @$row->suppiler->name;
                })
                ->addColumn('amount_items', function ($row) {
                    return $row->purchaseOrderItems()->count() ?? 0;
                })
                ->addColumn('total_before_discount', function ($row) {
                    $totalBeforeDiscount = $row->purchaseOrderItems->sum(function ($item) {
                        return $item->price * $item->amount;
                    });

                    // Format the total using number_format
                    return number_format($totalBeforeDiscount, 2); // Adjust the number of decimals as needed
                })

                ->addColumn('total_discount_value', function ($row) {
                    return number_format($row->purchaseOrderItems()->sum('discount_value'), 2) ?? 0;
                })
                ->addColumn('total_tax_value', function ($row) {
                    return number_format($row->purchaseOrderItems()->sum('tax_value'), 2) ?? 0;
                })
                ->addColumn('total_price', function ($row) {
                    return number_format($row->purchaseOrderItems()->sum('total_price'), 2) ?? 0;
                })
                ->editColumn('status', function ($row) {
                    try {
                        $statusEnum = PurchaseOrderStatusEnum::from($row->status);
                        return trans('finance.' . $statusEnum->name);
                    } catch (\ValueError $e) {
                        return $row->status;
                    }
                })
                ->addColumn('actions', function ($row) {
                    $transferButton = '<a href="'.route('finance.transfer-purchase-order.edit',$row->id).'" class="btn btn-warning" data-id="' . $row->id . '" title="تحويل الى فاتورة شراء">
                                            <i class="fa fa-reply"></i>
                                           </a>';


                    $detailsButton = editButton(route($this->mainRoute . ".show", $row->id), $row->name,"eye");
                    $printButton = printButton(route("finance.purchase-orders-print.show", $row->id), null, 'finance.purchase-orders-print-read');
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    if($row->status != 'new'){
                        $editButton     = '';
                        $deleteButton   = '';
                        $transferButton = '';
                    }
                    return $transferButton." ".$detailsButton . " " . $printButton . " " .  $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.purchase_order");
        $data["addButtonText"] = __("finance::finance.purchase_orders");
        $data["canPermission"] = __("ClientCategory add");
        $data['suppliers'] = $this->action->suppliers();
        $data['items'] = $this->action->items();
        $data["modalType"] = "modal-fullscreen";
        return view($this->folderPath . '.index', $data);
    }




}
