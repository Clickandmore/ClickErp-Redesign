<?php

namespace Nami\Finance\Http\Controllers;

use Nami\Finance\Http\Requests\FirstStockRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ClientStatusEnum;
use Nami\Finance\Http\MainController;
use Nami\Finance\Services\FirstStockService as ObjService;
use Yajra\DataTables\DataTables;

class FirstStockController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "finance::finance.first_stocks";
        $this->mainRoute = "finance.first-stocks";
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

            if ($request->filled('stock_id')) {
                $stock = $request->stock_id;
                $dataTable = $dataTable->whereHas('first_stock', function ($query) use ($stock) {
                    $query->where('stock_id', $stock);
                });
            }
            if ($request->filled('item_id')) {
                $item = $request->item_id;
                $dataTable = $dataTable->where('item_id', $item);
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('code', function ($row) {
                    return @$row->item->name.'-'.$row->item->code_string.$row->item->code ;
                })
                ->editColumn('item', function ($row) {
                    return @$row->item->name;
                })
                ->editColumn('unit', function ($row) {
                    return @$row->item->purchaseUnit->title;
                })
                ->editColumn('stock', function ($row) {
                    return @$row->first_stock->stock->name;
                })
                ->editColumn('purchase_price', function ($row) {
                    return @$row->item->purchase_price;
                })
                ->editColumn('sale_price', function ($row) {
                    return @$row->item->sale_price;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
//                    if($row->status != 'new'){
//                        $editButton = '';
//                        $deleteButton = '';
//                    }
                    return  $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.first-stocks");
        $data["addButtonText"] = __("finance::finance.first-stocks");
        $data["canPermission"] = __("firstStocks add");
        $data['stocks'] = $this->action->stocks();
        $data['items'] = $this->action->items();
        $data['stock_setting'] = $this->action->getStocksSetting();
        return view($this->folderPath . '.index', $data);
    }






}
