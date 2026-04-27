<?php

namespace Nami\Hr\Services;

use Nami\Hr\MainService;
use Nami\Hr\Models\Department;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeeAttendances;
use Nami\Hr\Models\EmployeePermission;
use Nami\Hr\Models\EmployeeSalary;
use Nami\Hr\Models\EmployeeSanction;
use Nami\Hr\Models\EmployeeVacation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class HomeService extends MainService
{

    public function __construct(private DepartmentService $departmentService,
                                private AdvanceService $advanceService,
                                private EmployeeVacationService $vacationService,
                                private EmployeePermissionService $permissionService,
                                private DailyWorkReportService $dailyWorkReportService,
                                private TaskService $taskService,
                                private EmployeeTaskService $employeeTaskService,
                                private EmployeeAttendancesService $employeeAttendancesService,
                                private EmployeeRewardService $employeeRewardService,
                                private EmployeeService $employeeService)
    {

    }

    private function countDelaysByType($type)
    {
        $today = Carbon::now()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // Count delays for today
        $delaysToday = EmployeeAttendances::where('type', $type)
        ->whereDate('date', $today)
        ->count();

        // Count delays for the current month
        $delaysThisMonth = EmployeeAttendances::where('type', $type)
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->count();

        // Get delays grouped by day for the current month for chart data
        $delaysChart = EmployeeAttendances::selectRaw('DATE(date) as date, COUNT(*) as count')
            ->where('type', $type)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->groupBy('date')
            ->pluck('count', 'date');

        // Prepare the data for the chart
        $daysInMonth = [];
        $delayCounts = [];

        for ($date = Carbon::now()->startOfMonth(); $date <= Carbon::now()->endOfMonth(); $date->addDay()) {
            $daysInMonth[] = $date->format('Y-m-d');
            $delayCounts[] = $delaysChart->get($date->format('Y-m-d'), 0);
        }
        $data['delaysToday'.$type] =  $delaysToday;
        $data['delaysThisMonth'.$type] = $delaysThisMonth;
        $data['delayCounts'.$type] = $delayCounts;
        $data['daysInMonth'.$type] = $daysInMonth;

        return $data;
    }

    public function index()
    {
        $currentMonthStart = Carbon::now()->startOfMonth()->toDateString();
        $currentMonthEnd = Carbon::now()->endOfMonth()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $previousMonthStart = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth()->toDateString();
        $today = Carbon::today();
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');

        $data['totalSalaries'] = DB::table('hr_employee_salary')
            ->where('month_year', $previousMonth) // تصفية البيانات للشهر السابق فقط
            ->sum('final');

        $months = [
            'Jan', 'Feb', 'Mar', 'Apr',
            'May', 'Jun', 'Jul', 'Aug',
            'Sep', 'Oct', 'Nov', 'Dec'
        ];

        $data['salaries_average'] = round(EmployeeSalary::avg('final'), 2) ?? 0;
        $data['total_salaries'] = round(EmployeeSalary::sum('final'), 2) ?? 0;
        $data['departments_salaries'] = Employee::join('hr_employee_salary', 'hr_employee_salary.employee_id', '=', 'hr_employee.id')
            ->with('departments')
            ->select(
                'deparment_id',
                'hr_employee_salary.month_year', // استخدام عمود month_year
                DB::raw('sum(hr_employee_salary.final) as total_salary')
            )
//            ->whereYear('hr_employee_salary.month_year', $currentYear) // تصفية للسنة الحالية فقط
            ->groupBy('deparment_id', 'hr_employee_salary.month_year')
            ->orderBy('hr_employee_salary.month_year')
            ->get();
        $data['employees_counts'] = Employee::count();
        $data['departments_counts'] = $this->departmentService->count();
        $data['attendances_today'] = EmployeeAttendances::whereDate('date', $today)->count();
        $data['new_employees_counts'] = 0;
        $data['employees_contract_end'] = Employee::whereMonth('contract_end_date', date('m'))->get();
        $data['employee_vacations_back'] = EmployeeVacation::whereDay('to_date', date('d'))->get();
        $data['employees_vacations_today'] = EmployeeVacation::where('from_date', '<=', $today)
            ->where('to_date', '>=', $today)
            ->get();
        $data['vacationData'] = EmployeeVacation::select(
            DB::raw('SUM(CASE WHEN from_date >= "' . $currentMonthStart . '" AND to_date <= "' . $currentMonthEnd . '" THEN days ELSE 0 END) as current_month_days'),
            DB::raw('SUM(CASE WHEN from_date >= "' . $previousMonthStart . '" AND to_date <= "' . $previousMonthEnd . '" THEN days ELSE 0 END) as previous_month_days'),
            DB::raw('CASE
                WHEN SUM(CASE WHEN from_date >= "' . $previousMonthStart . '" AND to_date <= "' . $previousMonthEnd . '" THEN days ELSE 0 END) > 0
                THEN (SUM(CASE WHEN from_date >= "' . $currentMonthStart . '" AND to_date <= "' . $currentMonthEnd . '" THEN days ELSE 0 END)
                / SUM(CASE WHEN from_date >= "' . $previousMonthStart . '" AND to_date <= "' . $previousMonthEnd . '" THEN days ELSE 0 END)) * 100
                ELSE 0
             END as percentage'
            )
        )->first();
        $data['sanctionsData'] = EmployeeSanction::select(
            DB::raw('SUM(CASE WHEN date >= "' . $currentMonthStart . '" AND date <= "' . $currentMonthEnd . '" THEN value ELSE 0 END) as current_month_value'),
            DB::raw('SUM(CASE WHEN date >= "' . $previousMonthStart . '" AND date <= "' . $previousMonthEnd . '" THEN value ELSE 0 END) as previous_month_value'),
            DB::raw('CASE
                WHEN SUM(CASE WHEN date >= "' . $previousMonthStart . '" AND date <= "' . $previousMonthEnd . '" THEN value ELSE 0 END) > 0
                THEN (SUM(CASE WHEN date >= "' . $currentMonthStart . '" AND date <= "' . $currentMonthEnd . '" THEN value ELSE 0 END)
                / SUM(CASE WHEN date >= "' . $previousMonthStart . '" AND date <= "' . $previousMonthEnd . '" THEN value ELSE 0 END)) * 100
                ELSE 0
             END as percentage'
            )
        )->first();

        $data['permissionsData'] = EmployeePermission::select(
            DB::raw('COUNT(CASE WHEN date >= "' . $currentMonthStart . '" AND date <= "' . $currentMonthEnd . '" THEN 1 ELSE NULL END) as current_month_count'),
            DB::raw('COUNT(CASE WHEN date >= "' . $previousMonthStart . '" AND date <= "' . $previousMonthEnd . '" THEN 1 ELSE NULL END) as previous_month_count'),
            DB::raw('CASE
                WHEN COUNT(CASE WHEN date >= "' . $previousMonthStart . '" AND date <= "' . $previousMonthEnd . '" THEN 1 ELSE NULL END) > 0
                THEN (COUNT(CASE WHEN date >= "' . $currentMonthStart . '" AND date <= "' . $currentMonthEnd . '" THEN 1 ELSE NULL END)
                / COUNT(CASE WHEN date >= "' . $previousMonthStart . '" AND date <= "' . $previousMonthEnd . '" THEN 1 ELSE NULL END)) * 100
                ELSE 0
             END as percentage'
            )
        )->first();

        // Fetch permissions grouped by date, count the number of permissions per day
        $permissions = EmployeePermission::selectRaw('DATE(date) as date, COUNT(*) as count')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status' , 1)
            ->groupBy('date')
            ->pluck('count', 'date');
//        dd($permissions->get(date('Y-m-d'), 0));

        // Prepare the data for the chart
        $data['daysPInMonth'] = [];
        $data['permissionCounts'] = [];


        // Loop through each day of the month
        for ($date = clone $startOfMonth; $date <= $endOfMonth; $date->addDay()) {
//            echo ($permissions->get($date->format('Y-m-d'), 0));
            $data['daysPInMonth'][] = $date->format('Y-m-d');
            $data['permissionCounts'][] = $permissions->get($date->format('Y-m-d'), 0);
        }

        $data['sanctions'] = EmployeeSanction::selectRaw('DATE(date) as date, COUNT(*) as count')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status' , 1)
            ->groupBy('date')
            ->pluck('count', 'date');

        $data['daysSInMonth'] = [];
        $data['sanctionCounts'] = [];

        // Loop through each day of the month
        for ($date = clone $startOfMonth; $date <= $endOfMonth; $date->addDay()) {
            $data['daysSInMonth'][] = $date->format('Y-m-d');
            $data['sanctionCounts'][] = $data['sanctions']->get($date->format('Y-m-d'), 0); // default to 0 if no data
        }
        $employees = Employee::all();
        $series = [];

        $delays = $this->countDelaysByType(5);
        $attendances_absences = $this->countDelaysByType(3);

        foreach ($employees as $employee) {
            $attendance_data = [];
            for ($day = 1; $day <= date('t'); $day++) {
                $currentDate = date('Y-m-d', strtotime(date('Y-m') . '-' . $day));
                $attendAndLeave = EmployeeAttendances::where('type', 1)
                    ->where('employee_id', $employee->id)
                    ->where('date', $currentDate)
                    ->get();
                $attendances = EmployeeAttendances::where('type', 2)
                    ->where('employee_id', $employee->id)
                    ->where('date', $currentDate)
                    ->get();
                $absences = EmployeeAttendances::where('type', 3)
                    ->where('employee_id', $employee->id)
                    ->where('date', $currentDate)
                    ->get();
                $weekends = EmployeeAttendances::where('type', 4)
                    ->where('employee_id', $employee->id)
                    ->where('date', $currentDate)
                    ->get();
                $lates = EmployeeAttendances::where('type', 5)
                    ->where('employee_id', $employee->id)
                    ->where('date', $currentDate)
                    ->get();
                $vacations = EmployeeAttendances::where('type', 6)
                    ->where('employee_id', $employee->id)
                    ->where('date', $currentDate)
                    ->get();

                if ($attendances->count() > 0) {
                    $attendance_data[] = 1;
                }
                elseif ($attendAndLeave->count() > 0){
                    $attendance_data[] = 3;
                }
                elseif ($absences->count() > 0) {
                    $attendance_data[] = 0;
                }
                elseif ($weekends->count() > 0) {
                    $attendance_data[] = 4;
                }
                elseif ($lates->count() > 0) {
                    $attendance_data[] = 5;
                }
                elseif ($vacations->count() > 0) {
                    $attendance_data[] =6;
                }
                else {
                    $attendance_data[] = 2;
                }
            }

            $series[] = [
                'name' => $employee->name,
                'data' => $attendance_data
            ];
        }
        $data['series'] = $series;

        $array_department_labels = [];
        $array_department_employees = [];
        $array_department_salaries = [];
//        $array_department_employees[] = $one_salary->departments->employees->count() ?? 0;

        $department_data = [];
        $all_departments = []; // لتجميع أسماء الأقسام الفريدة


        foreach ($data['departments_salaries'] as $salary) {
            $department = $salary->departments->title ?? 'Unknown Department';
            $month = date('M', strtotime($salary->month_year)); // تحويل month_year إلى اسم الشهر فقط

            // تجميع أسماء الأقسام
            if (!in_array($department, $all_departments)) {
                $all_departments[] = $department;
            }

            // تهيئة بيانات القسم
            if (!isset($department_data[$department])) {
                $department_data[$department] = array_fill_keys($months, 0); // تهيئة الأشهر بقيم 0
            }

            // وضع قيمة المرتب في الشهر المناسب
            $department_data[$department][$month] = $salary->total_salary;
        }

        // تجهيز البيانات للرسم البياني
        $department_series = [];
        foreach ($all_departments as $department) {
            $series_data = array_values($department_data[$department]);
            $department_series[] = [
                'name' => $department, // اسم القسم
                'data' => $series_data, // القيم الشهرية
            ];
        }
        $department_labels = [
            'department_labels' => $months,
            'department_series' => $department_series,
        ];
        $departments = Department::all();
        if ($departments) {
            foreach ($departments as $one_department) {
                $array_department_labels[] = $one_department->title ?? "not found";
                $array_department_employees[] = $one_department->employees->count() ?? 0;
            }
        }

        $data['array_department_labels'] = $array_department_labels;
        $data['array_department_employees'] = $array_department_employees;
        $data['array_department_salaries'] = $array_department_salaries;
        $data =   array_merge($data, $delays);
        $data =   array_merge($data, $attendances_absences);
        $data =   array_merge($data, $department_labels);
        return $data;
    }

    public function profile($user_id = null)
    {
        if($user_id == null){
            $user_id = auth('admin')->user()->id;
        }
        $data['advances'] = $this->advanceService->count(['employee_id' => $user_id]);
        $data['advances_data'] = $this->advanceService->getWhere(['employee_id' => $user_id]);
        $data['vacations'] = $this->vacationService->count(['employee_id' => $user_id]);
        $data['vacations_data'] = $this->vacationService->getWhere(['employee_id' => $user_id]);
        $data['permissions'] = $this->permissionService->count(['employee_id' => $user_id]);
        $data['permissions_data'] = $this->permissionService->getWhere(['employee_id' => $user_id]);
        $data['daily_work_reports'] = $this->dailyWorkReportService->count(['employee_id' => $user_id]);
        $data['daily_work_reports_data'] = $this->dailyWorkReportService->getWhere(['employee_id' => $user_id]);
        $data['tasks'] = $this->employeeTaskService->count(['employee_id' => $user_id]);
        $data['tasks_data'] = $this->taskService->getMyDataTable()->get();
        $data['rewards'] = $this->employeeRewardService->getWhere(['employee_id' => $user_id]);
        $data['employee'] = $this->employeeService->find($user_id);
        $data['bladeTitle'] = __('auth.profile');
        return $data;
    }

}
