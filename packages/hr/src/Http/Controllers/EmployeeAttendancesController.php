<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\EmployeeAttendances\EditEmployeeAttendancesRequest;
use Nami\Hr\Http\Requests\EmployeeAttendances\EmployeeAttendancesRequest;
use Nami\Hr\Services\EmployeeAttendancesService as objService;

class EmployeeAttendancesController extends Controller
{
    public $folderPath = "hr::hr.employeeAttendances";
    public $postData = ['date','type','attendance_time','leave_time','month_year'
    ,'deducted_from_salary','deducted_from_salary_vlaue','shift_id','employee_id','have_permission'];
    public $mainRoute = "employeeAttendances";


    public function index(Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $dataTable = $action->getAttendancesDataTable();
            return datatables()->of($dataTable)
                ->addIndexColumn()
                ->editColumn('employee_id', function ($row) {
                    return @$row->employee->name;
                })
                ->editColumn('created_by_id', function ($row) {
                    return @$row->creator->name;
                })
                ->editColumn('date', function ($row) {
                    return $row->date;
                })
                ->editColumn('attendance_time', function ($row) {
                    return $row->attendance_time != null ? date("H:i", strtotime($row->attendance_time)) : 'غير مسجل';
                })
                ->editColumn('leave_time', function ($row) {
                    return $row->leave_time != null ? date("H:i", strtotime($row->leave_time)) : 'غير مسجل';
                })
                ->editColumn('month_year', function ($row) {
                    return $row->month_year;
                })
                ->editColumn('type', function ($row) {
                    return getHrEnumValue("employee_attendances_type", $row->type);
                })
                ->addColumn('actions', function ($row) {
                    $leaveButton = '';
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    if($row->type  == 2) {
                        $leaveButton = leaveButton(route($this->mainRoute . ".show", $row->id), $row->name);
                    }

                    return $editButton . " " . $deleteButton . " " . $leaveButton;
                })->rawColumns(['actions','absence_sanctions_id','employee_id','leave','status'])->make(true);
        }

        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.employeeAttendances');
        $data["addButtonText"] = __('hr::hr.employeeAttendances');
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data)->with([
            'employees' => $action->getEmployee(),
            "employee_attendances_type" => $action->getEmployeeAttendancesTypies(),
            "employee_attendances_type_full" => $action->getEmployeeAttendancesTypiesFull()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                'employees' => $action->getEmployee(),
                "employee_attendances_type" => $action->getEmployeeAttendancesTypies()
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(EmployeeAttendancesRequest $request, objService $action)
    {
        // dd($request->all());
        $dataInsert = $request->only($this->postData);
        return $action->storeEmployeeAttendances($dataInsert,$request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".leave")->with([
                "obj" => $action->find($id),
                'employees' => $action->getEmployee(),
                "employee_attendances_type" => $action->getEmployeeAttendancesTypies()
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
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
                "obj" => $action->find($id),
                'employees' => $action->getEmployee(),
                "employee_attendances_type" => $action->getEmployeeAttendancesTypiesFull()
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function update(EditEmployeeAttendancesRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        return $action->updateEmployeeAttendances($id, $dataInsert,$request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $action)
    {
        $action->deleteEmployeeAttendances($id);
        return jsonSuccess();
    }
}
