<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Hr\Models\EmployeeVacationBalance;
use Nami\Hr\Services\VacationYearService;

class EmployeeVacationBalanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }


    public function show(Request $request, VacationYearService $vacationYearService)
    {
        $employee_id = $request->input('employee_id');
        $vacation_id = $request->input('vacation_id');
        $vacation_year_id = $vacationYearService->getActiveYear()->id ?? null;

        // الحالة دي علشان اجيب انواع الاجازة اللي فيها رصيد للموظف ده
        if (isset($employee_id) && !isset($vacation_id)){
            $vacation_types = EmployeeVacationBalance::where('employee_id', $employee_id)
                ->where('vacation_year_id', $vacation_year_id)
                ->where('remain_days','>','0')
                ->with('vacation')
                ->get();
            return response()->json(['vacation_types' => $vacation_types]);
        }

        // البحث عن رصيد إجازات الموظف لنوع الإجازة المطلوب
        $employee_vacation_balance = EmployeeVacationBalance::where('employee_id', $employee_id)
            ->where('vacation_id', $vacation_id)
            ->first();

        // لو الموظف كان واخد نوع الاجازة دي قبل كدا شوف لو مخلص الرصيد
        if ($employee_vacation_balance) {
            $check = EmployeeVacationBalance::where('employee_id', $employee_id)
                ->where('vacation_id', $vacation_id)
                ->where('vacation_year_id', $vacation_year_id)->first();
            if ($check){
                return $check;
            }
            else {
                $newBalance = EmployeeVacationBalance::create(
                    [
                        'employee_id' => $employee_id,
                        'vacation_id' => $vacation_id,
                        'total_days' => $employee_vacation_balance->total_days,
                        'token_days' => 0,
                        'remain_days' => $employee_vacation_balance->total_days,
                        'vacation_year_id' => $vacation_year_id,
                    ]);
                return response()->json(['remain_days' => $newBalance->remain_days]);
            }
//            if ($employee_vacation_balance->remain_days == 0) {
//                return response()->json(['error' => __('hr.Employee vacation balance not found')]);
//            }
//
//            // لو الموظف كان واخد نوع الاجازة دي قبل كدا شوف لو مخلص الرصيد
//            if ($employee_vacation_balance->remain_days > 0) {
//                return response()->json(['remain_days' => $employee_vacation_balance->remain_days]);
//            }
        }

        // لو مش واخدها يبقي قوله ان الموظف ده مينفعش ياخد نوع الاجازة دي
        $vacationYearTitle = $vacationYearService->getActiveYear()->title;
        return response()->json(['error' => __('hr.Employee not assigned to this vacation').$vacationYearTitle]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
