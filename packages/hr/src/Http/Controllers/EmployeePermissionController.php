<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\EmployeePermission\EditEmployeePermissionRequest;
use Nami\Hr\Http\Requests\EmployeePermission\EmployeePermissionRequest;
use Nami\Hr\Models\EmployeePermission as objModel;
use Nami\Hr\Services\EmployeePermissionService as objService;
use Yajra\DataTables\DataTables;

class EmployeePermissionController extends Controller
{
    public $folderPath = "hr::hr.employeePermissions";
    public $postData = ['employee_id','permission_id','date','time','reason','from_time','to_time','file'];
    public $mainRoute = "employeePermissions";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, objService $service)
    {
        if ($request->ajax()) {
            if($request->user_id != null){
                $dataTable = $service->getDataTable($request->user_id);
            }else{
                $dataTable = objModel::query()->latest();
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('employee_id', function ($row) {
                    return $row->employee->name;
                })
                ->editColumn('permission_id', function ($row) {
                    return $row->permission->title;
                })
                ->editColumn('date', function ($row) {
                    return $row->date;
                })
                ->editColumn('time', function ($row) {
                    return $row->time;
                })
                ->editColumn('reason', function ($row) {
                    return $row->reason;
                })
                ->editColumn('status', function ($row) {
                    $tableName = 'hr_employee_permissions';
                    if ($row->status == 0) {
                        return updateSuccessEmployeeStatusButton($tableName, $row->id, 'مقبول') .
                            " " .updateDangerEmployeeStatusButton($tableName, $row->id, 'مرفوض');
                    } else {
                        return getHrEnumValue("employee_vacation_status", $row->status);
                    }
                })->addColumn('actions', function ($row) {
                    $editButton = "";
                        if ($row->status == 0) {
                            $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        }
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions','employee_id','permission_id','status'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.employeePermissions');
        $data["addButtonText"] = __('hr::hr.employeePermissions');
        $data["modalType"]= "modal-lg";
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
            $returnHTML = view($this->folderPath . ".create")->with([
                "employee_vacation_status" => $action->getEmployeePermissionStatus(),
                'employees' => $action->getEmployee(),
                'permissions' => $action->getPermission(),

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
    public function store(EmployeePermissionRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeEmployeePermission($dataInsert,$request);
        if ($data ["code"] == 402) {
            return jsonValid(['error' => [$data ["message"]]], '');
        }
        return jsonSuccess($data ["data"], $data ["message"], $data ["code"]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request, ObjService $action)
    {
        $id = $request->input('id');
        $status = $request->input('status');

        $vacation = $action->find($id);
        $vacation->status = $status;
        $vacation->save();

        return response()->json(['success' => true]);
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
                'permissions' => $action->getPermission(),
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
    public function update(EditEmployeePermissionRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateEmployeePermission($id, $dataInsert,$request);
        return jsonSuccess($data ["data"], $data ["message"], $data ["code"]);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $action)
    {
        $action->deleteEmployeePermission($id);
        return jsonSuccess();
    }
}
