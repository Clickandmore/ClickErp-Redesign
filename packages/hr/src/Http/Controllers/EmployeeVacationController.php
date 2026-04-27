<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Nami\Hr\Http\Requests\EmployeeVacation\EditEmployeeVacationRequest;
use Nami\Hr\Http\Requests\EmployeeVacation\EmployeeVacationRequest;
use Nami\Hr\Models\EmployeeVacation as objModel;
use Nami\Hr\Services\EmployeeService;
use Nami\Hr\Services\EmployeeVacationService as objService;
use Nami\Hr\Services\VacationYearService;
use Yajra\DataTables\DataTables;

class EmployeeVacationController extends Controller
{
    public $folderPath = "hr::hr.employeeVacations";
    public $postData = ['employee_id','vacation_id','value','from_date','to_date','days','reason','vacation_file'];
    public $mainRoute = "employeeVacations";

    public function index(Request $request,EmployeeService $employeeService, objService $service)
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
                ->editColumn('vacation_id', function ($row) {
                    return $row->vacation->title;
                })
                ->editColumn('value', function ($row) {
                    return $row->status->value == 1 ? $row->value - $row->days : $row->value;
                })
                ->editColumn('from_date', function ($row) {
                    return $row->from_date;
                })
                ->editColumn('to_date', function ($row) {
                    return $row->to_date;
                })
                ->editColumn('days', function ($row) {
                    return $row->days;
                })
                ->editColumn('reason', function ($row) {
                    return $row->reason;
                })
                ->editColumn('vacation_file', function ($row) {
                    if (!empty($row->vacation_file)) {
                        $fileUrl = showFile($row->vacation_file);
                        $linkText = helperTrans('crm.View Attached File');
                        return "<a href='{$fileUrl}' target='_blank' class='text-primary'>{$linkText}</a>";
                    }
                    return " ";
                })
                ->editColumn('status', function ($row) {
                    if ($row->status->value == 0) {
                        if (!in_array('ToggleStatusEmployeeVacation-update',@Auth::guard('admin')->user()->permissions->pluck('name')->toArray())){
                            $accept = "";
                            $reject = "";
                        }
                        else{
                            $accept = '<button class="btn btn-success btn-sm accept-button1" data-id="' . $row->id . '">مقبول</button>';
                            $reject = '<button class="btn btn-danger btn-sm reject-button1" data-id="' . $row->id . '">مرفوض</button>';
                        }

                        return $accept . " " . $reject;
                    } else {
                        return getEnumValue("employee_vacation_status",$row->status->value); // Cast to string if needed
                    }
                })
                ->addColumn('actions', function ($row) {
                    // بعد قبول الاجازة مفيش ولا تعديل ولا حذف
                    if ($row->status->value == 1) {
                        $editButton = " ";
                        $deleteButton = " ";
                    }
                    // بعد رفض الاجازة فيه حذف فقط
                    else if ($row->status->value == 2) {
                        $editButton = " ";
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    // لو طلب الاجازة لسه متعلق يبقي اقبل او ارفض عادي
                    else{
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $editButton . " " . $deleteButton;
                })->rawColumns(['actions','employee_id','vacation_id','status','vacation_file'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __('hr::hr.employeeVacations');
        $data["addButtonText"] = __('hr::hr.employeeVacation_order');
        return view($this->folderPath . '.index', $data)->with([
            'employees' => $employeeService->getEmployee(),
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
                "employee_vacation_status" => $action->getEmployeeVacationStatus(),
                'employees' => $action->getEmployee(),
                'vacations' => $action->getVacation(),

            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(EmployeeVacationRequest $request, objService $action,VacationYearService $vacationYearService)
    {
        $dataInsert = $request->only($this->postData);

        $hasOpeningVacationOrder = $action->employeeHasVacationOrder($dataInsert);
        if ($hasOpeningVacationOrder) {
            return jsonValid( ['error'=>[__("hr::hr.please end the opening vacation request for this employee")]],'');
        }

        $code = $vacationYearService->isOverTheYear($dataInsert);
        if ($code == 406 ) {
            return jsonValid( ['error'=>[__("hr::hr.can't over the active year")]],'');
        }
        if ($code == 405 ) {
            return jsonValid( ['error'=>[__("hr::hr.start date and end date should be in one active year")]],'');
        }

        $data = $action->storeEmployeeVacation($dataInsert,$request);
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
                'vacations' => $action->getVacation(),
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
    public function update(EditEmployeeVacationRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateEmployeeVacation($id, $dataInsert);
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
        $action->deleteEmployeeVacation($id);
        return jsonSuccess();
    }
}
