<?php

namespace Nami\Hr\Http\Controllers;

$class = 'Nami\\Finance\\Services\\CostCenterService';

if (class_exists($class)) {
    try {
        $service = app()->make($class);
    } catch (BindingResolutionException $e) {

    }
    if (method_exists($service, 'doSomething')) {
        $service->doSomething();
    }
}


use App\Http\Controllers\admin\Controller;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\Employee\EditEmployeeRequest;
use Nami\Hr\Http\Requests\Employee\EmployeeRequest;
use Nami\Hr\Models\Employee as objModel;
use Nami\Hr\Services\EmployeeService as objService;
use Nami\Hr\Services\VacationYearService;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    public $folderPath = "hr::hr.employees";
    public $postData = ['name', 'image', 'code', 'address', 'phone', 'birthday', 'email', 'national_num', 'country_id', 'deparment_id'
        , 'job_id', 'hired_date', 'contract_end_date', 'salary', 'responsible_employee_id', 'net_salary'];
    public $mainRoute = "employees";

    public function index(Request $request, objService $action)
    {
        return $this->renderListing($request, $action, objModel::CATEGORY_EMPLOYEE);
    }

    public function maids(Request $request, objService $action, \App\Http\Actions\ingaz\MaidAction $maidAction)
    {
        if (!$request->ajax()) {
            $maidAction->syncAllRentalMaidsToHr();
        }

        return $this->renderListing($request, $action, objModel::CATEGORY_MAID);
    }

    protected function renderListing(Request $request, objService $action, string $category)
    {
        $isMaidListing = $category === objModel::CATEGORY_MAID;
        $costCenterService = null;
        $class = 'Nami\\Finance\\Services\\CostCenterService';

        if (class_exists($class)) {
            $costCenterService = app($class);
        }
        if ($request->ajax()) {
            $dataTable = $action->GetFilterEmployees($request, $category);
            if ($request->notification == "true") {
                if (!$isMaidListing && $costCenterService) {
                    if ($request->has('employees_has_cost_center')) {
                        if ($request->employees_has_cost_center == 1) {
                            $dataTable = $costCenterService->getEmployeesWithCostCenter();
                        } else {
                            $dataTable = $costCenterService->getEmployeesWithNoCostCenter();
                        }
                    } else {
                        $dataTable = $costCenterService->getEmployees();
                    }
                }
            }
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('image', function ($row) {
                    return '<img class="avatar" src=' . showUserImage($row->image == null ? '../admin/assets/img/profile_av.png' : $row->image) . '>';
                })
                ->editColumn('name', function ($row) use ($isMaidListing) {
                    if ($isMaidListing && $row->maid_id && \Illuminate\Support\Facades\Route::has('showMaidDetails')) {
                        return '<a href="' . route('showMaidDetails', $row->maid_id) . '">' . e($row->name) . '</a>';
                    }

                    return '<a href="' . route('employee.employee-profile.index') . '?user_id=' . $row->id . '">' . e($row->name) . '</a>';
                })
                ->editColumn('national_num', function ($row) {
                    return $row->national_num;
                })
                ->editColumn('hired_date', function ($row) {
                    return $row->hired_date;
                })
                ->editColumn('job_id', function ($row) {
                    return optional($row->jobs)->title ?? '-';
                })
                ->editColumn('deparment_id', function ($row) {
                    return optional($row->departments)->title ?? '-';
                })
                ->addColumn('status', function ($row) {
                    return updateStatusButton('hr_employee', $row->status, $row->id);
                })
                ->addColumn('actions', function ($row) use ($costCenterService, $request, $isMaidListing) {
                    if ($isMaidListing) {
                        $actions = [];

                        if ($row->maid_id && \Illuminate\Support\Facades\Route::has('showMaidDetails')) {
                            $actions[] = '<a type="button" class="btn btn-info" href="' . route('showMaidDetails', $row->maid_id) . '">
                                <i class="fa fa-eye"></i>
                            </a>';
                        }

                        if ($row->maid_id && \Illuminate\Support\Facades\Route::has('maids.edit')) {
                            $actions[] = '<a type="button" class="btn btn-primary" href="' . route('maids.edit', $row->maid_id) . '">
                                <i class="fa fa-pencil"></i>
                            </a>';
                        }

                        if ($row->maid_id && \Illuminate\Support\Facades\Route::has('hr.maids.employ')) {
                            $actions[] = '<form method="POST" action="' . route('hr.maids.employ', $row->maid_id) . '" class="d-inline-block">'
                                . csrf_field()
                                . '<button type="submit" class="btn btn-success" title="توظيف العاملة">'
                                . '<i class="fa fa-user-plus"></i>'
                                . '</button>'
                                . '</form>';
                        }

                        return implode(' ', $actions) ?: '<span class="text-muted">-</span>';
                    }

                    $editButton = '';
                    $deleteButton = '';

                    // if (Auth::guard('admin')->user()->hasPermission('hr_employee-update')) {
                    $editButton = '<a type="button" class="btn btn-primary"
                        href="' . route($this->mainRoute . ".edit", $row->id) . '">
                                <i class="fa fa-pencil"></i>
                            </a>';
                    // }

                    // if (Auth::guard('admin')->user()->hasPermission('hr_employee-delete')) {
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    // }
                    $costCenter = "";
                    if ($costCenterService != null) {
                        $employees = $costCenterService->getWhere(['table_name' => 'employees'])->pluck('table_related_id')->toArray();
                        if (!in_array($row->id, $employees)) {
                            $costCenter = customButton(route("finance.cost-centers.create") . '?table_name=employees&name=' . $row->name . '&table_related_id=' . $row->id, __("finance.fn-cost-centers"), 'fa fa-money-bill', 'warning');
                        }
                        if ($request->notification == "true") {
                            return $costCenter;
                        }
                    }

                    return $costCenter . " " . $editButton . " " . $deleteButton;
                })->rawColumns(['name', 'image', 'actions', 'status', 'job_id', 'deparment_id'])->make(true);
        }

        $data["createRoute"] = $isMaidListing ? null : route($this->mainRoute . ".create");
        $data["dataTableRoute"] = $isMaidListing ? route('hr.maids.index') : route($this->mainRoute . ".index");
        $data["bladeTitle"] = $isMaidListing ? __('hr::hr.maids') : __('hr::hr.employees');
        $data["addButtonText"] = $isMaidListing ? __('hr::hr.maid') : __('hr::hr.employee');
        $data["identityColumnLabel"] = $isMaidListing
            ? __('hr::hr.maid_identity')
            : __('hr::hr.Employee ID number');
        $data['departments'] = $action->getdepartment();
        $data['jobs'] = $action->getJob();
        return view($this->folderPath . '.index', $data);
    }

    public function employMaid(int $maidId, \App\Http\Actions\ingaz\MaidAction $maidAction)
    {
        if (!class_exists(\App\Models\ingaz\Maid::class)) {
            return back()->with('error', 'تعذر الوصول إلى سجل العاملة.');
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('hr_employee')
            || !objModel::hasEmployeeCategoryColumn()) {
            return back()->with('error', 'يلزم تشغيل ترحيلات الموارد البشرية أولاً.');
        }

        $maid = \App\Models\ingaz\Maid::with(['jobTypes', 'nationality', 'office'])->findOrFail($maidId);

        $maidAction->syncRentalMaidToHrRecord($maid);

        $employee = objModel::query()
            ->withAllCategories()
            ->where('maid_id', $maid->id)
            ->first();

        if (!$employee) {
            return back()->with('error', 'تعذر إنشاء سجل الموارد البشرية لهذه العاملة.');
        }

        $employee->update([
            'employee_category' => objModel::CATEGORY_EMPLOYEE,
        ]);

        return redirect()
            ->route('employees.edit', $employee->id)
            ->with('success', 'تم تحويل العاملة إلى موظفة داخل الشركة.');
    }


    public function create(Request $request, objService $action)
    {
        return view($this->folderPath . ".create")->with([
            'employees' => $action->getEmployeesName(),
            'countries' => $action->getCountries(),
            'departments' => $action->getdepartment(),
            'jobs' => $action->getJob(),
            'shifts' => $action->getShift(),
            'allowances' => $action->getAllowance(),
            'vacations' => $action->getVacation(),
            'insurances' => $action->getActiveInsurance(),
            'taxs' => $action->getActiveTax(),
            // "admin_typeis"=> $action->getAdminTypies(),
        ]);
    }

    public function store(EmployeeRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeEmployee($dataInsert, $request);
        return jsonSuccess($data, null, 201);
    }


    public function show($id, ObjService $action)
    {
        $employee = $action->find($id);
        $month_days = $employee->shift->shift->month_days;
        return response()->json(['salary' => $employee->net_salary, 'month_days' => $month_days]);
    }


    public function edit($id, Request $request, ObjService $action, VacationYearService $vacationYearService, \App\Http\Actions\ingaz\MaidAction $maidAction)
    {
        $employee = $action->find($id);

        if ($employee->maid_id && class_exists(\App\Models\ingaz\Maid::class)) {
            $maid = \App\Models\ingaz\Maid::with(['nationality.translations'])->find($employee->maid_id);

            if ($maid) {
                $resolvedCountryId = $maidAction->resolveHrCountryIdForMaid($maid);

                if ($resolvedCountryId && (int) $employee->country_id !== (int) $resolvedCountryId) {
                    $employee->update(['country_id' => $resolvedCountryId]);
                    $employee = $action->find($id);
                }
            }
        }

        return view($this->folderPath . ".edit")->with([
            "obj" => $employee,
            'employees' => $action->getEmployeesName(),
            'countries' => $action->getCountries(),
            'departments' => $action->getdepartment(),
            'jobs' => $action->getJob(),
            'shifts' => $action->getShift(),
            'allowances' => $action->getAllowance(),
            'vacations' => $action->getVacation(),
            'insurances' => $action->getInsurance(),
            'responsibles' => $action->getEmployeeWithoutMe($id),
            'taxs' => $action->getTax(),
            // "admin_typeis"=> $action->getAdminTypies(),
        ]);
    }


    public function update(EditEmployeeRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateEmployee($id, $dataInsert, $request);
        return jsonSuccess($data, null, 201);
    }

    public function destroy($id, ObjService $action)
    {
        $action->deleteEmployee($id);
        return jsonSuccess();
    }
}
