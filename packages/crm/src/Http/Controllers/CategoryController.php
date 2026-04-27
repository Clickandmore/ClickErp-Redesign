<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Services\CategoryService as ObjService;
use Nami\Crm\Http\Requests\CategoryRequest;
use Illuminate\Http\Request;
use Nami\Crm\Models\Category as ObjModel;
use Nami\Crm\Models\ClientCategory;
use Yajra\DataTables\DataTables;


class CategoryController extends Controller
{
    public $folderPath = "crm::crm.categories";
    public $postData = ["name", "is_shown"];
    public $mainRoute = "crm.categories";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()

                ->editColumn('is_shown', function ($row) {
                    if ($row->is_shown == 1) {
                        $is_shown = "<button class='btn btn-success update-status' data-id='" . $row->id . "'>" . __("crm::crm.show") . "</button>";
                    } else {
                        $is_shown = "<button class='btn btn-danger update-status' data-id='" . $row->id . "'>" . __("crm::crm.hidden") . "</button>";
                    }
                    return $is_shown;
                })
                ->addColumn('actions', function ($row) {

                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })
                ->rawColumns(['actions','is_shown'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("crm::crm.categories");
        $data["addButtonText"] = __("crm::crm.categories");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryRequest $request, ObjService $service)
    {
        $dataInsert = $request->only($this->postData);
        $data = $service->store($dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, ObjService $service)
    {
        $crm = $service->find($id);
        $crm->is_shown = !$crm->is_shown;
        $crm->save();
        return response()->json(['success' => true]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id)
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CategoryRequest $request, $id, ObjService $service)
    {
        $dataInsert = $request->only($this->postData);
        $data = $service->update($id, $dataInsert);
        return jsonSuccess($data);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {
        $obj = $service->find($id);
        $clientCount = ClientCategory::where('category_id', $obj->id)->count();
        if ($clientCount > 0) {
            return jsonValid( ['error'=>[__("crm.sorry,can't delete this Category")]],'');
        } else {
            $service->delete($id);
            return jsonSuccess();
        }
    }
}
