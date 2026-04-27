<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\EmployeeAttendances\EditEmployeeAttendancesRequest;
use Nami\Hr\Http\Requests\MlutiAttendance\MlutiAttendanceRequest;
use Nami\Hr\Models\Department;
use Nami\Hr\Models\Employee;
use Nami\Hr\Services\MlutiEmployeeService as objService;

class MlutiEmployeeController extends Controller
{
    public $folderPath = "hr::hr.employeeAttendances";
    public $postData = ['date', 'attendance_time', 'leave_time', 'employee_id'];
    public $mainRoute = "mluti-employee";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $action)
    {

        $allEmployees = Employee::get();
        $employees = Employee::get();

        if ($request->ajax()) {
            $employees = Employee::get();

            if ($request->department_id) {
                $employees = Employee::where('deparment_id', $request->department_id)->get();
            }

//             dd($employees);
            $table = view($this->folderPath . '.table', compact('employees', 'allEmployees'))->render();
            return response()->json(['table' => $table]);
        }

        $departments = Department::all();
//        if ($request->ajax()) {
//            $employees = Employee::get();
//            $allEmployees = Employee::get();
//
//            if($request->department_id){
//                $employees = Employee::where('deparment_id', $request->department_id)->get();
//            }
//
//            $table = view($this->folderPath . '.table', compact('employees', 'allEmployees'))->render();
//            return response()->json(['table' => $table]);
//        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.employeeExecelFile');
        $data["addButtonText"] = __('hr::hr.employeeExecelFile');
        $data["modalType"] = "";
        $data["allEmployees"] = Employee::get();
        $data["employees"] = Employee::get();
        $data["departments"] = Department::all();

        return view($this->folderPath . '.attendance_multi', $data)->with([
                'allEmployees' => $allEmployees,
                'departments' => $departments,
                'employees' => $action->getEmployee(),
            ]
        );
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(MlutiAttendanceRequest $request, objService $action)
    {
        // dd($request->all());
        $dataInsert = $request->only($this->postData);
        return $action->storeEmployeeAttendances($dataInsert, $request);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request, ObjService $action)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $action)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(EditEmployeeAttendancesRequest $request, $id, ObjService $action)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $action)
    {
        //
    }
}
