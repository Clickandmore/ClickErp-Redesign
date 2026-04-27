<?php

namespace Nami\Hr\Http\Controllers\Employee;

use Nami\Hr\Services\ComplaintReplyService as ObjService;
use Nami\Hr\Http\Requests\ComplaintReplyRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Finance\Http\MainController;
use Yajra\DataTables\DataTables;

class ComplaintReplyController extends MainController
{
    public function __construct(ObjService $action, ObjRequest $request)
    {
        $this->folderPath = "hr.employees.complaint_replies";
        $this->mainRoute = "employee.complaint-replies";
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
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('admin_id', function ($row) {
                    return $row->admin->name ?? "";
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);

                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create",['complaint_id'=>request()->complaint_id]);
        $data["dataTableRoute"] = route($this->mainRoute . ".index",['complaint_id'=>$request->complaint_id]);
        $data["bladeTitle"] = __("hr.daily-work-reports");
        $data["addButtonText"] = __("hr.daily-work-reports");
        $data["canPermission"] = __("dailyWorkReports add");
        return view($this->folderPath . '.index', $data);
    }


}
