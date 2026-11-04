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
use App\Models\auth\Admin;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Nami\Hr\Http\Requests\Employee\EditEmployeeRequest;
use Nami\Hr\Http\Requests\Employee\EmployeeRequest;
use Nami\Hr\Models\Employee as objModel;
use Nami\Hr\Services\EmployeeService as objService;
use Nami\Hr\Services\VacationYearService;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    public $folderPath = "hr::hr.employees";
    public $postData = ['name', 'image', 'code', 'address', 'phone', 'birthday', 'email', 'national_num', 'identity_expiry_date', 'country_id', 'deparment_id'
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

    public function employedMaids(Request $request, objService $action)
    {
        return $this->renderListing($request, $action, objModel::CATEGORY_EMPLOYEE, true);
    }

    protected function renderListing(Request $request, objService $action, string $category, bool $employedMaids = false)
    {
        $isMaidListing = $category === objModel::CATEGORY_MAID;
        $costCenterService = null;
        $class = 'Nami\\Finance\\Services\\CostCenterService';

        if (class_exists($class)) {
            $costCenterService = app($class);
        }
        if ($request->ajax()) {
            $dataTable = $action->GetFilterEmployees($request, $category, $employedMaids);
            $listingIds = $dataTable->pluck('id');
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
            $dataTable = $dataTable->whereIn('id', $listingIds)->values();

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
                ->addColumn('branch', function ($row) {
                    return $row->branch?->name ?? '-';
                })
                ->addColumn('status', function ($row) {
                    return updateStatusButton('hr_employee', $row->status, $row->id);
                })
                ->addColumn('actions', function ($row) use ($costCenterService, $request, $isMaidListing) {
                    if ($isMaidListing) {
                        $actions = [];

                        if ($row->maid_id && \Illuminate\Support\Facades\Route::has('showMaidDetails')) {
                            $actions[] = '<a type="button" class="btn btn-info" title="' . __('hr::hr.view_maid_details') . '" href="' . route('showMaidDetails', $row->maid_id) . '">
                                <i class="fa fa-eye"></i>
                            </a>';
                        }

                        if ($row->maid_id && \Illuminate\Support\Facades\Route::has('maids.edit')) {
                            $actions[] = '<a type="button" class="btn btn-primary" title="' . __('hr::hr.edit_maid') . '" href="' . route('maids.edit', $row->maid_id) . '">
                                <i class="fa fa-pencil"></i>
                            </a>';
                        }

                        if ($row->maid_id && \Illuminate\Support\Facades\Route::has('hr.maids.employ')) {
                            $actions[] = '<form method="POST" action="' . route('hr.maids.employ', $row->maid_id) . '" class="d-inline-block">'
                                . csrf_field()
                                . '<button type="submit" class="btn btn-success" title="' . __('hr::hr.employ_maid') . '">'
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

        $data["createRoute"] = $isMaidListing || $employedMaids ? null : route($this->mainRoute . ".create");
        $data["dataTableRoute"] = $employedMaids ? route('hr.maids.employees') : ($isMaidListing ? route('hr.maids.index') : route($this->mainRoute . ".index"));
        $data["bladeTitle"] = $employedMaids ? __('hr::hr.employed_maids') : ($isMaidListing ? __('hr::hr.maids') : __('hr::hr.employees'));
        $data["addButtonText"] = $isMaidListing ? __('hr::hr.maid') : __('hr::hr.employee');
        $data['isMaidListing'] = $isMaidListing;
        $data['filterCountries'] = \Illuminate\Support\Facades\DB::table('countries')
            ->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);
        $data["identityColumnLabel"] = $isMaidListing || $employedMaids
            ? __('hr::hr.maid_identity')
            : __('hr::hr.Employee ID number');
        $data['departments'] = $isMaidListing || $employedMaids
            ? $action->getDepartmentsForCategory($category)
            : $action->getdepartment()->reject(function ($department) {
                return $department->title === 'قسم العاملات';
            })->values();
        $data['jobs'] = $isMaidListing || $employedMaids
            ? $action->getJobsForCategory($category)
            : $action->getJob()->reject(function ($job) {
                return $job->title === 'عاملة';
            })->values();
        return view($this->folderPath . '.index', $data);
    }

    public function employMaid(int $maidId, \App\Http\Actions\ingaz\MaidAction $maidAction)
    {
        if (!class_exists(\App\Models\ingaz\Maid::class)) {
            return back()->with('error', __('hr::hr.maid_record_unavailable'));
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('hr_employee')
            || !objModel::hasEmployeeCategoryColumn()) {
            return back()->with('error', __('hr::hr.hr_migrations_required'));
        }

        $maid = \App\Models\ingaz\Maid::with(['jobTypes', 'nationality', 'office'])->findOrFail($maidId);

        $maidAction->syncRentalMaidToHrRecord($maid);

        $employee = objModel::query()
            ->withAllCategories()
            ->where('maid_id', $maid->id)
            ->first();

        if (!$employee) {
            return back()->with('error', __('hr::hr.maid_hr_record_create_failed'));
        }

        $employee->update([
            'employee_category' => objModel::CATEGORY_EMPLOYEE,
        ]);

        return redirect()
            ->route('employees.edit', $employee->id)
            ->with('success', __('hr::hr.maid_promoted_to_employee'));
    }


    public function create(Request $request, objService $action)
    {
        $sourceAdmin = null;

        if ($request->filled('admin_id')) {
            $sourceAdmin = Admin::query()
                ->whereNull('deleted_at')
                ->find((int) $request->input('admin_id'));
        }

        return view($this->folderPath . ".create")->with([
            'employees' => $action->getEmployeesName(),
            'hrCountries' => $action->getCountries(),
            'departments' => $action->getdepartment(),
            'jobs' => $action->getJob(),
            'shifts' => $action->getShift(),
            'allowances' => $action->getAllowance(),
            'vacations' => $action->getVacation(),
            'insurances' => $action->getActiveInsurance(),
            'taxs' => $action->getActiveTax(),
            'sourceAdmin' => $sourceAdmin,
            // "admin_typeis"=> $action->getAdminTypies(),
        ]);
    }

    public function store(EmployeeRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        if (Schema::hasColumn('hr_employee', 'admin_id') && $request->filled('admin_id')) {
            $dataInsert['admin_id'] = (int) $request->input('admin_id');
        }
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
            'hrCountries' => $action->getCountries(),
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
