<?php

namespace Nami\Hr\Services;

use App\Services\BranchAccessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\Payroll;
use Nami\Hr\Models\PayrollItem;

class PayrollService
{
    public function createPayroll($request): array
    {
        $branchAccess = app(BranchAccessService::class);
        $branchId = $branchAccess->selectedBranchId($request);

        if ($branchAccess->enabled() && ! $branchId) {
            return [
                'code' => 422,
                'data' => null,
                'message' => 'يرجى تحديد الفرع قبل إنشاء مسير الرواتب.',
            ];
        }

        $selectedRows = $request->input('selected_rows', []);

        if (empty($selectedRows)) {
            return [
                'code' => 402,
                'data' => null,
                'message' => 'يرجى تحديد العاملات المراد إضافتهن إلى مسير الرواتب.',
            ];
        }

        $monthYear = $request->input('month_year');
        $monthDays = $this->resolveMonthDays(
            $request->input('month_days_display_option'),
            Carbon::createFromFormat('Y-m', $monthYear)->daysInMonth
        );

        $selectedEmployeeIds = collect($request->input('employee_id', []))
            ->filter(fn ($employeeId, $key) => isset($selectedRows[$key]))
            ->map(fn ($employeeId) => (int) $employeeId)
            ->filter()
            ->unique()
            ->values();

        $validEmployeeIds = Employee::query()
            ->onlyMaids()
            ->withoutBranchScope()
            ->whereIn('id', $selectedEmployeeIds)
            ->when($branchId, function ($query) use ($branchId, $branchAccess) {
                $query->where(function ($branchQuery) use ($branchId, $branchAccess) {
                    $branchQuery->where('branch_id', $branchId);

                    if ($branchAccess->isMainBranchId($branchId)) {
                        $branchQuery->orWhereNull('branch_id');
                    }
                });
            })
            ->pluck('id')
            ->map(fn ($employeeId) => (int) $employeeId);

        if ($selectedEmployeeIds->diff($validEmployeeIds)->isNotEmpty()) {
            return [
                'code' => 422,
                'data' => null,
                'message' => 'تتضمن القائمة موظفاً لا يتبع الفرع المحدد.',
            ];
        }

        if (Payroll::query()
            ->withoutBranchScope()
            ->where('month_year', $monthYear)
            ->when($branchId, function ($query) use ($branchId, $branchAccess) {
                $query->where(function ($branchQuery) use ($branchId, $branchAccess) {
                    $branchQuery->where('branch_id', $branchId);

                    if ($branchAccess->isMainBranchId($branchId)) {
                        $branchQuery->orWhereNull('branch_id');
                    }
                });
            }, fn ($query) => $query->whereNull('branch_id'))
            ->exists()) {
            return [
                'code' => 401,
                'data' => null,
                'message' => 'تم إنشاء مسير رواتب لهذا الشهر من قبل.',
            ];
        }

        $payroll = DB::transaction(function () use ($request, $selectedRows, $monthYear, $monthDays, $branchId) {
            $payroll = Payroll::create([
                'branch_id' => $branchId,
                'month_year' => $monthYear,
                'month_days' => $monthDays,
                'workers_count' => 0,
                'total_gross_salary' => 0,
                'total_deductions' => 0,
                'total_net_salary' => 0,
                'notes' => $request->input('payroll_notes'),
            ]);

            $workersCount = 0;
            $totalGross = 0.0;
            $totalDeductions = 0.0;
            $totalNet = 0.0;

            foreach ($request->input('employee_id', []) as $key => $employeeId) {
                if (!isset($selectedRows[$key])) {
                    continue;
                }

                $workDays = max(0, min($monthDays, $this->toFloat($request->input("work_days.$key"))));
                $basicSalary = $this->toFloat($request->input("basic_salary.$key"));
                $incentive = $this->toFloat($request->input("incentive.$key"));
                $otherAdditions = $this->toFloat($request->input("other_additions.$key"));
                $advance = $this->toFloat($request->input("advance.$key"));
                $penalty = $this->toFloat($request->input("penalty.$key"));
                $otherDeductions = $this->toFloat($request->input("other_deductions.$key"));
                $absenceDays = max($monthDays - $workDays, 0);

                $grossSalary = round(($workDays * ($basicSalary / $monthDays)) + $incentive + $otherAdditions, 2);
                $rowTotalDeductions = round($advance + $penalty + $otherDeductions, 2);
                $netSalary = round($grossSalary - $rowTotalDeductions, 2);

                PayrollItem::create([
                    'branch_id' => $branchId,
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employeeId,
                    'maid_id' => $request->input("maid_id.$key"),
                    'employee_name' => $request->input("employee_name.$key"),
                    'nationality_name' => $request->input("nationality_name.$key"),
                    'basic_salary' => $basicSalary,
                    'work_days' => $workDays,
                    'absence_days' => $absenceDays,
                    'incentive' => $incentive,
                    'other_additions' => $otherAdditions,
                    'gross_salary' => $grossSalary,
                    'advance' => $advance,
                    'penalty' => $penalty,
                    'other_deductions' => $otherDeductions,
                    'total_deductions' => $rowTotalDeductions,
                    'net_salary' => $netSalary,
                    'notes' => $request->input("notes.$key"),
                ]);

                $workersCount++;
                $totalGross += $grossSalary;
                $totalDeductions += $rowTotalDeductions;
                $totalNet += $netSalary;
            }

            $payroll->update([
                'workers_count' => $workersCount,
                'total_gross_salary' => round($totalGross, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_net_salary' => round($totalNet, 2),
            ]);

            return $payroll;
        });

        return [
            'code' => 201,
            'data' => [
                'url' => route('payrolls.show', $payroll->id),
            ],
            'message' => 'تم حفظ مسير الرواتب بنجاح.',
        ];
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) $value;
    }

    private function resolveMonthDays(?string $option, int $actualMonthDays): int
    {
        if (in_array($option, ['30', '28', '31'], true)) {
            return (int) $option;
        }

        return $actualMonthDays;
    }
}
