<?php

namespace Nami\Finance\Http\Controllers;

use Nami\Finance\Services\ItemService as ObjService;
use Nami\Finance\Http\Requests\ItemRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Finance\Http\MainController;
use Yajra\DataTables\DataTables;

class MissingItemController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "finance::finance.missing_items";
        $this->mainRoute = "finance.missing-items";
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
            $dataTable = $action->getMissingItems();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('supplier_id', function ($row) {
                    return @$row->supplier->name;
                })->addColumn('current_balance', function ($row) {
                    return $row->itemStockStatus->sum('amount') ?? 0;
                })
                ->addColumn('unit', function ($row) {
                    return @$row->purchaseUnit->title;
                })->addColumn('actions', function ($row) {

                    return '';
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route('finance.purchase-orders.create',['from' => 'missing']);
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.purchase_order");
        $data["addButtonText"] = __("finance::finance.transfer_to_purchase_order");
        $data["canPermission"] = __("ClientCategory add");
        return view($this->folderPath . '.index', $data);
    }




}
