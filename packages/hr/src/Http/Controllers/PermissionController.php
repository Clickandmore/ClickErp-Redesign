<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\permission\EditPermissionRequest;
use Nami\Hr\Http\Requests\permission\PermissionRequest;
use Nami\Hr\Models\Permission as objModel;
use Nami\Hr\Services\PermissionService as objService;
use Yajra\DataTables\DataTables;

class PermissionController extends Controller
{
    public $folderPath = "hr::hr.permissions";
    public $postData = ['title'];
    public $mainRoute = "permissions";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dataTable = objModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('title', function ($row) {
                    return $row->title;
                })
                ->addColumn('actions', function ($row) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.permissions');
        $data["addButtonText"] = __('hr::hr.permission');
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
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
    public function store(PermissionRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storePermission($dataInsert,$request);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $action->find($id)
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
    public function update(EditPermissionRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updatePermission($id, $dataInsert);
        return jsonSuccess($data);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $action)
    {
        $action->deletePermission($id);
        return jsonSuccess();
    }

}
