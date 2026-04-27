<?php

namespace Nami\Hr\Http\Controllers\Employee;
use Nami\Hr\Services\TaskReplyService as ObjService;
use Nami\Hr\Http\Requests\TaskReplyRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Hr\Http\MainController;
use Yajra\DataTables\DataTables;
class HrTaskReplyController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
         $this->folderPath = "hr.employees.hr_task_replies";
         $this->mainRoute = "employee.hr-task-replies";
         $this->action = $action;
         $this->request = $request;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request,ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable()->where('task_id', $request->task_id);
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('file', function ($row) {
                    return getImgTag($row->file);
                })
                ->editColumn('employee_id', function ($row) {
                    return @$row->employee->name;
                })
                 ->editColumn('employee_id', function ($row) {
                    return @$row->employee->name;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id));
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })
                ->escapeColumns([])->make(true);
        }
        $data["createRoute"] = '';
        $data["dataTableRoute"] = route($this->mainRoute . ".index",['task_id'=> @$request->task_id]);
        $data["bladeTitle"] = __("hr.hr_task_replies");
        $data["addButtonText"] = __("hr.hr_task_replies");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

}
