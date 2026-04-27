<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\EmployeeSanction\EditEmployeeSanctionRequest;
use Nami\Hr\Http\Requests\EmployeeSanction\EmployeeSanctionRequest;
use Nami\Hr\Models\EmployeeSanction as objModel;
use Nami\Hr\Services\EmployeeSanctionService as objService;
use Yajra\DataTables\DataTables;

class EmployeeSanctionController extends Controller
{
    public $folderPath = "hr::hr.employeeSanctions";
    public $postData = ['employee_id','sanction_id','value','date','with_salary','reason' , 'file'];
    public $mainRoute = "employeeSanctions";


    public function index(Request $request)
    {
        if ($request->ajax()) {
            $dataTable = objModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('employee_id', function ($row) {
                    return $row->employee->name;
                })
                ->editColumn('sanction_id', function ($row) {
                    return @$row->sanction->title;
                })
                ->editColumn('value', function ($row) {
                    return $row->value;
                })
                ->editColumn('date', function ($row) {
                    return $row->date;
                })
                ->editColumn('with_salary', function ($row) {
                    return ($row->with_salary == 1 ? 'نعم'  : 'لا');
                })
                ->editColumn('reason', function ($row) {
                    return $row->reason;
                })
                ->editColumn('status', function ($row) {
                    $tableName = 'hr_employee_sanctions';
                    if ($row->status == 0) {
                        return updateSuccessEmployeeStatusButton($tableName, $row->id, 'مقبول') .
                            " " .updateDangerEmployeeStatusButton($tableName, $row->id, 'مرفوض');
                    }
                    else {
                        return getHrEnumValue("employee_vacation_status", $row->status);
                    }
                })->addColumn('actions', function ($row) {
                    if($row->status == \Nami\Hr\Enums\EmployeeVacationStatus::ACCEPTED->value){
                        return '';
                    }elseif($row->status == \Nami\Hr\Enums\EmployeeVacationStatus::REFUSED->value){
                        return deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    $editButton = $row->status == 0 ? editButton(route($this->mainRoute . ".edit", $row->id), $row->name) : '';
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions','employee_id','sanction_id','status'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.employeeSanctions');
        $data["addButtonText"] = __('hr::hr.employeeSanctions');
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
                "employee_vacation_status" => $action->getEmployeeSanctionStatus(),
                'employees' => $action->getEmployee(),
                'sanctions' => $action->getSanction(),

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
    public function store(EmployeeSanctionRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeEmployeeSanction($dataInsert,$request);
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
                'sanctions' => $action->getSanction(),
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
    public function update(EditEmployeeSanctionRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateEmployeeSanction($id, $dataInsert,$request);
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
        $action->deleteEmployeeSanction($id);
        return jsonSuccess();
    }
}
