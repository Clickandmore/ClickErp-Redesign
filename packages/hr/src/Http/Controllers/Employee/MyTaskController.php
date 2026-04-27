<?php

namespace Nami\Hr\Http\Controllers\Employee;
use Nami\Hr\Services\TaskService as ObjService;
use Nami\Hr\Http\Requests\TaskRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Hr\Http\MainController;
use Yajra\DataTables\DataTables;
class MyTaskController extends MainController
{
    public function __construct(ObjService $action,ObjRequest $request)
    {
         $this->folderPath = "hr.employees.my_tasks";
         $this->mainRoute = "employee.my-tasks";
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
            if($request->user_id != null){
                $dataTable = $service->getMyDatatable($request->user_id);
            }else{
                $dataTable = $service->getMyDatatable();
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('file', function ($row) {
                    return getImgTag($row->file);
                })
                ->editColumn('employee_id', function ($row) {
                    return @$row->employee->name;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';

//                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id));
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    $replyButton =  editButton(route("employee.hr-task-replies.create",['task_id'=>$row->id]),'','reply','',__('hr.replies'),'info');

                    return $editButton . " " . $deleteButton . " " . $replyButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("hr.tasks");
        $data["addButtonText"] = __("hr.tasks");
        $data["canPermission"] = __("hr-task-reply add");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

}
