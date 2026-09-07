<?php

namespace Nami\Hr\Http\Controllers\Employee;
use Nami\Hr\Database\Seeders\Enums\TaskStatus;
use Nami\Hr\Services\TaskService as ObjService;
use Nami\Hr\Http\Requests\TaskRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Hr\Http\MainController;
use Yajra\DataTables\DataTables;
class TaskController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
         $this->folderPath = "hr.employees.tasks";
         $this->mainRoute = "employee.tasks";
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
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('end_date', function ($row) {
                    return $row->end_date."  ". $row->end_time;
                })
                ->editColumn('file', function ($row) {
                    if ($row->file) {
                        return '<a href="' . showFile($row->file) . '">عرض المرفق</a>';
                    } else {
                        return "لا يوجد";
                    }
                })
                ->addColumn('branch', function ($row) {
                    return $row->branch?->name ?? '-';
                })
                ->editColumn('replies', function ($row) {
                   return editButton(route($this->mainRoute.".replies",$row->id),__('hr.replies'),'reply-all',true);
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    $replyButton = '';

                    if($row->status != TaskStatus::Completed->value){
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id));
                        $deleteButton = ($row->replies->count() == 0)? deleteButton(route($this->mainRoute . ".destroy", $row->id)): '';
                        $replyButton = ($row->replies->count() <= 1 ) ? editButton(route("employee.hr-task-replies.create",['task_id'=>$row->id]),'','reply','',__('hr.replies'),'info') : '';
                    }

                    return $editButton . " " . $deleteButton . " " . $replyButton ;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("hr.tasks");
        $data["addButtonText"] = __("hr.tasks");
        $data["canPermission"] = __("hr-task-reply add");
//        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }


}
