<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\Department\DepartmentRequest;
use Nami\Hr\Http\Requests\Department\EditDepartmentRequest;
use Nami\Hr\Models\Department as objModel;
use Nami\Hr\Services\DepartmentService as objService;
use Yajra\DataTables\DataTables;

class DepartmentController extends Controller
{
    public $folderPath = "hr::hr.department";
    public $postData = ['title'];
    public $mainRoute = "departments";


    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dataTable = objModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('title', function ($row) {
                    return $row->title;
                })
                ->addColumn('number_employees', function ($row) {
                    return $row->employees->count() ?? 0;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.departments');
        $data["addButtonText"] = __('hr::hr.department');
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
    public function store(DepartmentRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeDepartment($dataInsert,$request);
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
    public function update(EditDepartmentRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateDepartment($id, $dataInsert);
        return jsonSuccess($data);
    }


    public function destroy($id, ObjService $action)
    {
        $data = $action->deleteDepartment($id);
        if(!$data) {
            return jsonValid( ['error'=>[__("hr.sorry,can't delete this department")]]);
        }
        return jsonSuccess();
    }
}
