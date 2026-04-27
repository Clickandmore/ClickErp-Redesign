<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\Salary\EditSalaryRequest;
use Nami\Hr\Http\Requests\Salary\SalaryRequest;
use Nami\Hr\Models\Employee as objModel;
use Nami\Hr\Models\EmployeeAllowance;
use Nami\Hr\Models\EmployeeAttendances;
use Nami\Hr\Models\EmployeeDelay;
use Nami\Hr\Models\EmployeeSanction;
use Nami\Hr\Services\SalariesService as objService;

class SalariesController extends Controller
{
    public $folderPath = "hr::hr.salaries";
    public $postData = ['month_year', 'sanctions', 'employee_id', 'allowances', 'Insurances', 'tax', 'delays', 'attandance_days', 'final'];
    public $mainRoute = "salaries";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objService $action)
    {
        $employees = objModel::with('insurances', 'tax')
            ->when($request->month, function ($q) use ($request) {
                $q->whereDoesntHave('salary', function ($q) use ($request) {
                    $q->where('month_year', $request->month);
                });
            })->when($request->department, function ($q) use ($request) {
                $q->whereHas('departments', function ($q) use ($request) {
                    $q->where('deparment_id', $request->department);
                });
            })
            ->latest()
            ->get();
        $data = [];
        foreach ($employees as $employee) {
            $allowances = EmployeeAllowance::where('employee_id', $employee->id)->sum('value');
            $sanctions = EmployeeSanction::where('employee_id', $employee->id)->sum('value');
            $delays = EmployeeDelay::where('employee_id', $employee->id)->sum('value');
            $attandance_days = EmployeeAttendances::where('employee_id', $employee->id)
                ->where(function ($query) {
                    $query->where('type', 1)
                        ->orWhere('type', 2);
                })
                ->count();
            $insurances_sum = $employee->insurances()->sum('emp_value');
            $taxs_sum = $employee->tax()->sum('value');
            $salary = $employee->salary;
            if ($taxs_sum != 0) {
                $taxs = $salary * ($taxs_sum / 100);
            } else {
                $taxs = 0;
            }
            $net = $salary + $allowances - $sanctions - $delays - $insurances_sum;
            $data[] = [
                'employee' => $employee,
                'allowances' => $allowances,
                'sanctions' => $sanctions,
                'delays' => $delays,
                'net' => $net,
                'attandance_days' => $attandance_days,
                'insurances_sum' => $insurances_sum,
                'taxs' => $taxs,
            ];
        }
        $viewData = [
            "createRoute" => route($this->mainRoute . ".create"),
            "dataTableRoute" => route($this->mainRoute . ".index"),
            "bladeTitle" => __('hr::hr.salaries'),
            "addButtonText" => __('hr::hr.salaries'),
            "data" => $data,
            'departments' => $action->getdepartment(),
        ];
        if ($request->ajax()) {
            $table = view('hr::hr.salaries.table', $viewData)->render();
            return response()->json([
                'table' => $table,
            ]);
        }
        return view($this->folderPath . '.index', $viewData);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(SalaryRequest $request, objService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeSalary($dataInsert, $request);
        return jsonSuccess($data ["data"], $data ["message"], $data ["code"]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $action->find($id)
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(EditSalaryRequest $request, $id, ObjService $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->updateSalary($id, $dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $action)
    {
        $action->deleteSalary($id);
        return jsonSuccess();
    }
}
