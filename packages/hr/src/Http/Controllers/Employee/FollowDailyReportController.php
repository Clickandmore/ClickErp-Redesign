<?php

namespace Nami\Hr\Http\Controllers\Employee;

use Nami\Hr\Http\Requests\DailyWorkReportRequest as ObjRequest;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeeResponsible;
use Nami\Hr\Services\FollowDailyReportService as ObjService;
use Illuminate\Http\Request;
use Nami\Hr\Http\MainController;
use Yajra\DataTables\DataTables;

class FollowDailyReportController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "hr.employees.follow_daily_reports";
        $this->mainRoute = "employee.follow-daily-reports";
        $this->action = $action;
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request,ObjService $action)
    {
        if ($request->ajax()) {
            $dataTable = $action->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('title', function ($row) {
                    return  '<span title="'.$row->description.'">'.$row->title.'</span>';
                })
                ->editColumn('file', function ($row) {
                    return getImgTag($row->file, 100, 100);
                })
                ->editColumn('employee_id', function ($row) {
                    return  '<span title="'.$row->description.'">'.$row->employee->name.'</span>';
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);

                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $responsible_employees = EmployeeResponsible::where('responsible_employee_id', @auth('admin')->user()->employee->id)->pluck('employee_id')->toArray();

        $data['employees'] = Employee::whereIn('id', $responsible_employees)->get();

        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("hr.follow-daily-reports");
        $data["addButtonText"] = __("hr.follow-daily-reports");
        $data["canPermission"] = __("dailyWorkReports add");
        return view($this->folderPath . '.index', $data);
    }

}
