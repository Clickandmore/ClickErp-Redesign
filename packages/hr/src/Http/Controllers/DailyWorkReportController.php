<?php

namespace Nami\Hr\Http\Controllers;

use Nami\Hr\Models\Employee;
use Nami\Hr\Services\DailyWorkReportService as ObjService;
use Nami\Hr\Http\Requests\DailyWorkReportRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Hr\Models\DailyWorkReport as ObjModel;
use Nami\Hr\Http\MainController;
use Yajra\DataTables\DataTables;
use function Laravel\Prompts\alert;

class DailyWorkReportController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "hr.daily_work_reports";
        $this->mainRoute = "daily-work-reports";
        $this->action = $action;
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            if($request->user_id != null){
                $dataTable = $service->getDataTable($request->user_id);
            }else{
                $dataTable = ObjModel::query()->latest();
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('title', function ($row) {
                    return  '<span title="'.$row->description.'">'.$row->title.'</span>';
                })
                ->editColumn('file', function ($row) {
                    return getImgTag($row->file, 100, 100);
                })
                ->editColumn('employee_id', function ($row) {
                    return  '<span title="'.$row->description.'">'.@$row->employee->name.'</span>';
                })
                ->filter(function ($query) use ($request) {
                    if ($request->filled('employee_id')) {
                        $query->where('employee_id', $request->employee_id);
                    }
                    if ($request->filled('date')) {
                        $query->where('date', $request->date);
                    }
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);

                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("hr.daily-work-reports");
        $data['employees'] = Employee::get();

        $data["addButtonText"] = __("hr.report");
        $data["canPermission"] = __("dailyWorkReports add");
        return view($this->folderPath . '.index', $data);
    }


}
