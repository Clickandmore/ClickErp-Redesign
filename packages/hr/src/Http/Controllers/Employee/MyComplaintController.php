<?php

namespace Nami\Hr\Http\Controllers\Employee;

use Nami\Hr\Services\ComplaintService as ObjService;
use Nami\Hr\Http\Requests\ComplaintRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Hr\Http\MainController;
use Yajra\DataTables\DataTables;

class MyComplaintController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "hr.employees.my_complaints";
        $this->mainRoute = "employee.my-complaints";
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
            $dataTable = $service->getMyDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('employee_id', function ($row) {
                    return $row->employee->name;
                })
                ->editColumn('directed_to', function ($row) {
                    return @$row->directed_to->name ?? '';
                })
                ->editColumn('replies', function ($row) {
                    $replyButton = editButton(route('employee.complaint-replies.create',['complaint_id'=>$row->id]), $row->name,'reply');
                    $showRepliesButton = viewButton(route('employee.complaint-replies.index' ,['complaint_id'=>$row->id]), $row->name,'eye');

                    return $replyButton . " " . $showRepliesButton;
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("hr.my-complaints");
        $data["addButtonText"] = __("hr.my-complaints");
        return view($this->folderPath . '.index', $data);
    }



}
