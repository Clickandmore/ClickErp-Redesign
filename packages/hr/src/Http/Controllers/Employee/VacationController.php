<?php

namespace Nami\Hr\Http\Controllers\Employee;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\EmployeeVacation\EmployeeVacationRequest as ObjRequest;
use Nami\Hr\Services\EmployeeVacationService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class VacationController extends Controller
{
    public string $folderPath = "hr::hr.vacations";
    public string $mainRoute = "vacations";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('employee_id', function ($row) {
                    return $row->employee->name ?? "";
                })
                ->editColumn('vacation_id', function ($row) {
                    return $row->vacation->title ?? "";
                })
                ->editColumn('file2', function ($row) {
                    if ($row->file2 != null){
                        return "<a href='".showFile($row->file2)."'>عرض المرفق</a>";
                    }
                    return "لا يوجد";
                })
                ->editColumn('return_date', function ($row) {
                    $return_date = "";
                    if ($row->status == 0){
                        return $return_date;
                    }
                    if ($row->status != 2) {
                        if ($row->return_date == null) {
                            $return_date = editButton(route("employee.vacations-return-date.edit", $row->id), $row->name, "pencil", '', __('hr.employeeVacations'));
                        } else {
                            return $row->return_date;
                        }
                    }
                    return $return_date;
                })
                ->editColumn('file', function ($row) {
                    if ($row->status != 2) {
                        if ($row->file != null) {
                            return '<a href=" ' . showFile($row->file) . ' " download><i class="fa fa-download"></i></a>';
                        }
                    }
                    return "";
                })
                ->editColumn('status', function ($row) {
                        return $row->status->lang();
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), __('hr.advances'));

//                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("hr::hr.vacations_order");
        $data["addButtonText"] = __("hr::hr.vacations_order");
        // $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                'storeRoute' => route($this->mainRoute . ".store"),
                "employee_id" => @auth('admin')->user()->employee->id,
                "employee_vacation_status" => $service->getEmployeeVacationStatus(),
                'employees' => $service->getEmployee(),
                'vacations' => $service->getVacation(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ObjRequest $request
     * @param ObjService $service
     * @return JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->storeEmployeeVacation($dataInsert,$request);
        if (isset($data['error'])){
            return jsonValid("",$data['error']);
        }
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $service->find($id),
                "employee_vacation_status" => $service->getEmployeeVacationStatus(),
                'employees' => $service->getEmployee(),
                'vacations' => $service->getVacation(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                "employee_id" => @auth('admin')->user()->employee->id,
                'updateRoute' => route($this->mainRoute . ".update", $id),
                "employee_vacation_status" => $service->getEmployeeVacationStatus(),
                'employees' => $service->getEmployee(),
                'vacations' => $service->getVacation(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ObjRequest $request
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->updateEmployeeVacation($id, $dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(int $id, ObjService $service)
    {
        $service->deleteEmployeeVacation($id);
        return jsonSuccess();
    }
}
