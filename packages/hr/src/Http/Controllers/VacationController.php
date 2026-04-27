<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\vacation\EditVacationRequest;
use Nami\Hr\Http\Requests\vacation\VacationRequest;
use Nami\Hr\Models\Vacation as objModel;
use Nami\Hr\Services\VacationService as objService;
use Yajra\DataTables\DataTables;

class VacationController extends Controller
{
    public $folderPath = "hr.vacations";
    public $postData = ['title','number_of_days','with_salary'];
    public $mainRoute = "vacations";
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
                ->editColumn('number_of_days', function ($row) {
                    return $row->number_of_days;
                })
                ->editColumn('with_salary', function ($row) {
                    return ($row->with_salary == 1 ? 'نعم'  : 'لا');
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr.vacations');
        $data["addButtonText"] = __('hr.vacation');
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
    public function store(VacationRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeVacation($dataInsert,$request);
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
    public function update(EditVacationRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateVacation($id, $dataInsert,$request);
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
        $action->deleteVacation($id);
        return jsonSuccess();
    }

}
