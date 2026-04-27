<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Enums\StockMovementTypeEnum;
use Nami\Finance\Http\Requests\StockMovementRequest as ObjRequest;
use Nami\Finance\Services\StockMovementService as ObjService;
use Yajra\DataTables\DataTables;

class StockMovementController extends Controller
{
    protected string $folderPath = "finance::finance.stock_movement";
    protected string $mainRoute = "finance.stock-movement";

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return DataTables::of($service->getNormalTypeTable())
                ->addIndexColumn()
                ->editColumn('type', function ($row) {
                    foreach (StockMovementTypeEnum::cases() as $case){
                        if($case->value == $row->type){
                            return $case->lang();
                        }
                    }
                })
                ->editColumn('the_stock', function ($row) {
                    return @$row->stock->name;
                })
                ->editColumn('date', function ($row) {
                    return '<span title="'.$row->notes.'">' . $row->date . '</span> ';
                })
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->filter(function ($instance) use ($request) {
                    if ($request->get('from_date')) {
                        $instance->where('date','>=', $request->get('from_date'));
                    }
                    if ($request->get('to_date')) {
                        $instance->where('date','<=', $request->get('to_date'));
                    }
                    if ($request->get('stock_id')) {
                        $instance->where('fn_stock_id', $request->get('stock_id'));
                    }
                })
                ->addColumn('actions', function ($row) {
                    $editButton = deleteActionButton(route($this->mainRoute . ".edit", $row->id), 'pencil','outline-secondary');
                    $deleteButton = deleteActionButton(route($this->mainRoute . ".destroy", $row->id));
                    $showButton = editButton(route($this->mainRoute . ".show", $row->id), $row->name, 'eye',true);

                    return $editButton . " " . $showButton . " " . $deleteButton;
                })
                ->escapeColumns([])
                ->make(true);
        }

        return view($this->folderPath . '.index', [
            "createRoute" => route($this->mainRoute . ".create"),
            "dataTableRoute" => route($this->mainRoute . ".index"),
            "bladeTitle" => __("finance::finance.Additional Permits"),
            "addButtonText" => __("finance::finance.Additional Permit"),
            'modalType' => 'modal-fullscreen',
            'stocks' => $service->getStocks(),
            'request' => $request
        ]);
    }

    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()){
            return jsonSuccess([
                'html' => view($this->folderPath . ".create")->with([
                    'items' => $service->getProductsExceptForStockMovement(),
                    'stocks' => $service->getStocks(),
                    'lastCode' => $service->getLastCodeOfType(StockMovementTypeEnum::NormalAddition->value),
                    'stock_setting' => $service->stockSetting(),
                    "storeRoute" => route($this->mainRoute . ".store"),
                ])->render()
            ]);
        }
        else{
            $data['items'] = $service->getProductsExceptForStockMovement();
            $data['stocks'] = $service->getStocks();
            $data['lastCode'] = $service->getLastCodeOfType(StockMovementTypeEnum::NormalAddition->value);
            $data['stock_setting'] = $service->stockSetting();
            $data['createRoute'] = route($this->mainRoute . ".create");
            $data['bladeTitle'] = __("finance::finance.Additional Permits");
            $data['addButtonText'] = __("finance::finance.Additional Permit");
            $data['storeRoute'] = route($this->mainRoute . ".store");
            return view($this->folderPath . ".create",$data);
        }
    }

    public function store(ObjRequest $request, ObjService $service)
    {
        $data = $request->validated();
        $service->storeNormalStoreMovement($data);
        return jsonSuccess(null,null,201);
    }

    public function show($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . ".show")->with([
                    "movement" => $service->findMovement($id),
                    'stock_setting' => $service->stockSetting(),
                ])->render()
            ]);
        }
    }

    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return jsonSuccess([
                'html' => view($this->folderPath . ".edit")->with([
                    "movement" => $service->findMovement($id),
                    'items' => $service->getProductsExceptForStockMovement(),
                    'stocks' => $service->getStocks(),
                    'lastCode' => $service->getLastCodeOfType(StockMovementTypeEnum::NormalAddition->value),
                    'stock_setting' => $service->stockSetting(),
                    "updateRoute" => route($this->mainRoute . ".update", $id),
                ])->render()
            ]);
        }
    }

    public function update(ObjRequest $request, $id, ObjService $service)
    {
        $data = $request->validated();
        $service->updateMovement($id, $data);
        return jsonSuccess();
    }

    public function destroy($id, ObjService $service)
    {
        $service->deleteMovement($id);
        return jsonSuccess();
    }
}
