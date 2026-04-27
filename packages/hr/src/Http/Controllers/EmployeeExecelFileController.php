<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\EmployeeAttendances\EditEmployeeAttendancesRequest;
use Nami\Hr\Services\EmployeeExecelFileService as objService;
use Nami\Hr\Services\EmployeeService;

class EmployeeExecelFileController extends Controller
{
    public $folderPath = "hr::hr.employeeAttendances";
    public $postData = ['date','attendance_time','leave_time','employee_id'];
    public $mainRoute = "employeeExecelFile";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $action, EmployeeService $employeeService)
    {
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.employeeExecelFile');
        $data["addButtonText"] = __('hr::hr.employeeExecelFile');
        $data["employees"] = $employeeService->get();
        $data["modalType"] = "";
        return view($this->folderPath . '.attendance_excel_files', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $action)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, objService $action)
    {
        // dd($request->all());
        $dataInsert = $request->only($this->postData);
        $data = $action->storeEmployeeAttendances($dataInsert,$request);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request, ObjService $action)
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(EditEmployeeAttendancesRequest $request, $id, ObjService $action)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $action)
    {
        //
    }
}
