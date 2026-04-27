<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\Insurances\EditInsuranceRequest;
use Nami\Hr\Http\Requests\Insurances\InsuranceRequest;
use Nami\Hr\Models\Insurance;
use Nami\Hr\Models\Insurance as objModel;
use Nami\Hr\Services\InsuranceService as ObjService;
use Yajra\DataTables\DataTables;

class InsuranceController extends Controller
{
    public $folderPath = "hr::hr.insurances";
    public $postData = ['title','type','value','emp_value','company_value'];
    public $mainRoute = "insurances";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
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
                ->editColumn('type', function ($row) {
                    return ($row->type == 0 ? __('hr::hr.percentage_of_salary') : __("hr::hr.fixed_amount"));
                })
                ->editColumn('value', function ($row) {
                    return $row->value;
                })
                ->editColumn('emp_value', function ($row) {
                    return $row->emp_value;
                })
                ->editColumn('company_value', function ($row) {
                    return $row->company_value;
                })
                ->addColumn('status', function ($row) {
                   return updateStatusButton('hr_insurances',$row->status, $row->id);
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions','status','value'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.insurances');
        $data["addButtonText"] = __('hr::hr.insurances');
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $action)
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
    public function store(InsuranceRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = Insurance::create($dataInsert);
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
    public function update(EditInsuranceRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateInsurance($id, $dataInsert);
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
        $action->deleteInsurance($id);
        return jsonSuccess();
    }

}
