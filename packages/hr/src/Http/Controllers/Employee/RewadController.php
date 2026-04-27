<?php

namespace Nami\Hr\Http\Controllers\Employee;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\EmployeeReward\EmployeeRewardRequest as objRequest;
use Nami\Hr\Services\EmployeeRewardService as objService;
use Yajra\DataTables\DataTables;

class RewadController extends Controller
{
    public $folderPath = "hr.employees.rewards";
    public $mainRoute = "employee.rewards";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('employee_id', function ($row) {
                    return $row->employee->name ?? "";
                })->editColumn('reward_id', function ($row) {
                    return $row->reward->title ?? "";
                })->editColumn('exchange_timing', function ($row) {
                    return $row->exchange_timing->lang();
                })->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions',"employee_id","reward_id","exchange_timing"])->make(true);
        }
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr.rewards');
        // $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, objService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create",[
                'storeRoute' =>  route($this->mainRoute . ".store"),
                'employees' => $service->getEmployees(),
                'rewards' => $service->getRewards(),
                'exchange_timing' => \Nami\Hr\Database\Seeders\Enums\ExchangeTimingEnum::cases(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(objRequest $request, objService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->storeEmployeeReward($dataInsert);
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
    public function edit($id, Request $request, objService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit",[
                'updateRoute' =>  route($this->mainRoute . ".update", $id),
                "obj" => $service->find($id),
                "employees" => $service->getEmployees(),
                "rewards" => $service->getRewards(),
                "exchange_timing" => \Nami\Hr\Database\Seeders\Enums\ExchangeTimingEnum::cases(),
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
    public function update(objRequest $request, $id, objService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->updateEmployeeReward($id, $dataInsert);
        return jsonSuccess($data);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, objService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}
