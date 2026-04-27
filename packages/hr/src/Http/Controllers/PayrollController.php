<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\Payroll\PayrollRequest;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\Payroll;
use Nami\Hr\Services\PayrollService;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month');

        $payrolls = Payroll::query()
            ->when($month, function ($query) use ($month) {
                $query->where('month_year', $month);
            })
            ->latest('month_year')
            ->latest('id')
            ->get();

        return view('hr::hr.payrolls.index', [
            'bladeTitle' => 'مسير الرواتب',
            'selectedMonth' => $month,
            'payrolls' => $payrolls,
            'createRoute' => route('payrolls.create', array_filter(['month' => $month])),
        ]);
    }

    public function create(Request $request, \App\Http\Actions\ingaz\MaidAction $maidAction)
    {
        $maidAction->syncAllRentalMaidsToHr();

        [$monthYear, $monthDate, $monthDays] = $this->resolveMonthContext($request->input('month'));

        $rows = Employee::query()
            ->onlyMaids()
            ->whereNotNull('maid_id')
            ->whereHas('maid.rentalContracts', function ($query) {
                $query->active();
            })
            ->whereDoesntHave('payrollItems.payroll', function ($query) use ($monthYear) {
                $query->where('month_year', $monthYear);
            })
            ->with([
                'maid.nationality',
                'maid.rentalContracts' => function ($query) {
                    $query->active()
                        ->with('info')
                        ->latest('contract_date');
                },
                'nationality',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Employee $employee) use ($monthDays) {
                return $this->mapEligiblePayrollRow($employee, $monthDays);
            })
            ->values();

        return view('hr::hr.payrolls.create', [
            'bladeTitle' => 'إنشاء مسير راتب',
            'selectedMonth' => $monthYear,
            'monthLabel' => $monthDate->locale(app()->getLocale())->translatedFormat('F Y'),
            'monthDays' => $monthDays,
            'rows' => $rows,
            'storeRoute' => route('payrolls.store'),
            'indexRoute' => route('payrolls.index'),
        ]);
    }

    public function store(PayrollRequest $request, PayrollService $service)
    {
        $data = $service->createPayroll($request);

        return jsonSuccess($data['data'], $data['message'], $data['code']);
    }

    public function show(Payroll $payroll)
    {
        $payroll->load('items');

        return view('hr::hr.payrolls.show', [
            'bladeTitle' => 'تفاصيل مسير الرواتب',
            'payroll' => $payroll,
            'monthLabel' => Carbon::createFromFormat('Y-m', $payroll->month_year)
                ->locale(app()->getLocale())
                ->translatedFormat('F Y'),
        ]);
    }

    private function resolveMonthContext(?string $month): array
    {
        $monthDate = Carbon::hasFormat($month ?? '', 'Y-m')
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        return [
            $monthDate->format('Y-m'),
            $monthDate,
            $monthDate->daysInMonth,
        ];
    }

    private function mapEligiblePayrollRow(Employee $employee, int $monthDays): array
    {
        $basicSalary = $this->resolveBasicSalary($employee);
        $workDays = $monthDays;
        $absenceDays = 0;
        $incentive = 0.0;
        $otherAdditions = 0.0;
        $advance = 0.0;
        $penalty = 0.0;
        $otherDeductions = 0.0;
        $grossSalary = $this->calculateGrossSalary($workDays, $basicSalary, $incentive, $otherAdditions);
        $totalDeductions = $this->calculateTotalDeductions($advance, $penalty, $otherDeductions);

        return [
            'employee_id' => $employee->id,
            'maid_id' => $employee->maid_id,
            'employee_name' => $employee->name,
            'nationality_name' => $this->resolveNationalityName($employee),
            'month_days' => $monthDays,
            'work_days' => $workDays,
            'absence_days' => $absenceDays,
            'basic_salary' => $basicSalary,
            'incentive' => $incentive,
            'other_additions' => $otherAdditions,
            'gross_salary' => $grossSalary,
            'advance' => $advance,
            'penalty' => $penalty,
            'other_deductions' => $otherDeductions,
            'total_deductions' => $totalDeductions,
            'net_salary' => round($grossSalary - $totalDeductions, 2),
            'notes' => '',
        ];
    }

    private function resolveBasicSalary(Employee $employee): float
    {
        $contract = optional($employee->maid)->rentalContracts
            ?->sortByDesc(function ($item) {
                return $item->contract_date ?? $item->created_at;
            })
            ->first();

        return (float) (
            $contract?->info?->monthly_salary
            ?? $contract?->info?->salary_after_sponsorship
            ?? $employee->salary
            ?? $employee->maid?->resolved_salary
            ?? $employee->maid?->salary
            ?? 0
        );
    }

    private function resolveNationalityName(Employee $employee): string
    {
        if ($employee->maid?->nationality) {
            return $employee->maid->nationality->title
                ?? optional($employee->maid->nationality->translations->first())->title
                ?? '-';
        }

        return $employee->nationality?->name ?? '-';
    }

    private function calculateGrossSalary(float $workingDays, float $basicSalary, float $incentive, float $otherAdditions): float
    {
        return round(($workingDays * ($basicSalary / 30)) + $incentive + $otherAdditions, 2);
    }

    private function calculateTotalDeductions(float $advance, float $penalty, float $otherDeductions): float
    {
        return round($advance + $penalty + $otherDeductions, 2);
    }
}
