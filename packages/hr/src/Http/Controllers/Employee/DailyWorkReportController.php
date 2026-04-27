<?php

namespace Nami\Hr\Http\Controllers\Employee;

use Illuminate\Http\Request;
use Nami\Hr\Http\MainController;
use Nami\Hr\Http\Requests\DailyWorkReportRequest;
use Nami\Hr\Models\Employee;
use Nami\Hr\Services\DailyWorkReportService;
use Yajra\DataTables\DataTables;

class DailyWorkReportController extends MainController
{
    public function __construct(DailyWorkReportService $action, DailyWorkReportRequest $request)
    {
        $this->folderPath = "hr::hr.daily_work_reports";
        $this->mainRoute = "daily-work-reports";
        $this->action = $action;
        $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, DailyWorkReportService $service)
    {
        if ($request->ajax()) {
            if($request->user_id != null){
                $dataTable = $service->getDataTable($request->user_id);
            }else{
                $dataTable = $service->getDataTable();
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('employee_id', function ($row) {
                    return $row->employee->name ??"";
                })
                ->editColumn('title', function ($row) {
                    return  '<span title="'.$row->description.'">'.$row->title.'</span>';
                })
                ->editColumn('file', function ($row) {
                    return getImgTag($row->file, 100, 100);
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);

                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("hr::hr.daily-work-reports");
        $data['employees'] = Employee::get();

        $data["addButtonText"] = __("hr::hr.report");
        $data["canPermission"] = __("dailyWorkReports add");
        return view($this->folderPath . '.index', $data);
    }



}
