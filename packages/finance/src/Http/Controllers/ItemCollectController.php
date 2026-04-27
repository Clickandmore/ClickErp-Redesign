<?php

namespace Nami\Finance\Http\Controllers;

use Nami\Finance\Http\MainController;
use Nami\Finance\Http\Requests\ItemCollectRequest as ObjRequest;
use Nami\Finance\Http\Requests\SarfMovementCollectRequest as ObjSarfRequest;
use Illuminate\Http\Request;
use Nami\Finance\Enums\CategoryTypes;
use Nami\Finance\Services\ItemCollectService as ObjService;
use Nami\Finance\Services\ItemComponentService;
use Yajra\DataTables\DataTables;

class ItemCollectController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request, ObjSarfRequest $requestSarf)
    {
        $this->folderPath = "finance::finance.item_collects";
        $this->mainRoute = "finance.item-collects";
        $this->action = $action;
        $this->request = $request;
        $this->requestSarf = $requestSarf;
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
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('stock', function ($row) {
                    return @$row->toStock->name;
                })
                 ->addColumn('item', function ($row) {
                    return @$row->item->name;
                })
                ->addColumn('actions', function ($row) {

                    $can = $row->item->type->value == CategoryTypes::Boxs->value &&
                        $row->updated_at == $row->created_at;
                    $showButton = showButton(route($this->mainRoute . ".show", $row->id), $row->name);
                    $editButton =$can
                        ? editButton(route($this->mainRoute . ".edit", $row->id), $row->name) : '';

                    $deleteButton = $can ? deleteButton(route($this->mainRoute . ".destroy", $row->id)) :'';
                    return $showButton . " " . $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.item-collects");
        $data["addButtonText"] = __("finance::finance.item-collects");
        $data["canPermission"] = __("ItemCollects add");
        return view($this->folderPath . '.index', $data);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update( $id)
    {
        $dataInsert = $this->requestSarf->validated();

        $data = $this->action->customUpdate($id, $dataInsert);
        return jsonSuccess($data);
    }


    public function getItemComponents(Request $request,ItemComponentService $itemComponentService)
    {

        if ($request->ajax()) {
            $data = $itemComponentService->filter($request);
//        dd($data);
            $returnHTML = view($this->folderPath . ".details")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

}
