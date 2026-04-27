<?php

namespace Nami\Finance\Http\Controllers;

use Nami\Finance\Http\Requests\ClientRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Finance\Enums\ClientStatusEnum;
use Nami\Finance\Http\MainController;
use Nami\Finance\Services\ClientService as ObjService;
use Yajra\DataTables\DataTables;

class ClientController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "finance::finance.clients";
        $this->mainRoute = "finance.clients";
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
            $dataTable = $action->getClientDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('client_category_id', function ($row) {
                    return $row->category->name;
                })->addColumn('current_balance', function ($row) {
                    return 0;
                })
                ->editColumn('status', function ($row) {
                    $status = ClientStatusEnum::from($row->status);

                    return '
                        <button class="btn btn-sm '.$status->class().' '.$status->updateStatusButtonClass().'" id="update-status" data-id="' . $row->id . '">' . $status->lang() . '</button>';
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    $showButton = showButton(route("finance.clients-show", $row->id));

                    return $editButton . " " . $deleteButton.' '.$showButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.clients");
        $data["addButtonText"] = __("finance::finance.client");
        $data["canPermission"] = __("ClientCategory add");
        return view($this->folderPath . '.index', $data);
    }


    public function show(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = $this->action->find($id);
            $obj->status = !$obj->status;
            $obj->save();
            return jsonSuccess(['success' => true]);
        }
    }

    public function store()
    {
        $dataInsert = $this->request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $data = $this->action->customStore($dataInsert);
        return jsonSuccess($data);
    }

    public function update( $id)
    {
        $dataInsert = $this->request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $data = $this->action->customUpdate($id, $dataInsert);
        return jsonSuccess($data);
    }

    public function destroy($id)
    {
        $this->action->deleteClient($id);
        return jsonSuccess();
    }
    public function showClient(Request $request,$id)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $this->action->find($id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

}
