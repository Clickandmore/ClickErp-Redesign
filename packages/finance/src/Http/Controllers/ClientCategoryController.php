<?php

namespace Nami\Finance\Http\Controllers;

use Nami\Finance\Http\Requests\ClientCategoryRequest as ObjRequest;
use Nami\Finance\Services\ClientCategoryService as ObjService;
use Illuminate\Http\Request;
use Nami\Finance\Http\MainController;
use Yajra\DataTables\DataTables;

class ClientCategoryController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "finance::finance.client_categories";
        $this->mainRoute = "finance.client-categories";
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
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);

                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.client-categories");
        $data["addButtonText"] = __("finance::finance.client-category");
        $data["canPermission"] = __("ClientCategory add");
        return view($this->folderPath . '.index', $data);
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
        if ($this->action->find($id)->clients()->count() >= 1) {
            return jsonValid([
                "name" => "لا يمكن حذف هذا العنصر لأنه مرتبط بعملاء"
            ], null, 422);
        }
        $this->action->delete($id);
        return jsonSuccess();
    }

}
