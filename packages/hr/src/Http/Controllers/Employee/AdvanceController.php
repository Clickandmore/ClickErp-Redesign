<?php

namespace Nami\Hr\Http\Controllers\Employee;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Hr\Enums\AdvanceDeliveryMethod;
use Nami\Hr\Http\Requests\Advance\AdvanceRequest as ObjRequest;
use Nami\Hr\Models\Employee;
use Nami\Hr\Services\AdvanceService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class AdvanceController extends Controller
{
    public string $folderPath = "hr.employees.advances";
    public string $mainRoute = "employee.advances";

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
                ->editColumn('file', function ($row) {
                    if ($row->file != null){
                        return "<a target='_blank' href='".showFile($row->file)."'>عرض المرفق</a>".
                            '<a download href="'.showFile($row->file).'"> <i class="fa fa-download"></i></a>';
                    }
                    return "لا يوجد";
                })
                ->editColumn('payment_method', function ($row) {
                    return  $row->payment_method->lang();
                })
                ->editColumn('value', function ($row) {
                    return $row->value;
                })
                ->addColumn('payments', function ($row) {
                    return editButton(route($this->mainRoute . ".show", $row->id), $row->name, "eye");
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), __('hr.advances'));

                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("hr.advances_order");
        $data["addButtonText"] = __("hr.advances_order");
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
                "employees" => Employee::get(),
                "employee_id" => @auth('admin')->user()->employee->id,
                "advance_method" => $service->getAdvanceMethod(),
                "delivery_methods" => AdvanceDeliveryMethod::cases(),
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
        $data = $service->storeAdvance($dataInsert,$request);
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
                'updateRoute' => route($this->mainRoute . ".update", $id),
                "employees" => Employee::get(),
                "employee_id" => @auth('admin')->user()->employee->id,
                "delivery_methods" => AdvanceDeliveryMethod::cases(),
                "advance_method" => $service->getAdvanceMethod(),
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
        $data = $service->updateAdvance($id, $dataInsert,$request);
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
        $service->deleteAdvance($id);
        return jsonSuccess();
    }
}
