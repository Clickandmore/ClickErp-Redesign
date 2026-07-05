<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ingaz\Setting;
use Nami\Hr\Http\Requests\Payroll\PayrollRequest;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\Payroll;
use Nami\Hr\Services\PayrollService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        [$monthYear, $monthDate, $actualMonthDays] = $this->resolveMonthContext($request->input('month'));
        [$monthDaysDisplayOption, $monthDays] = $this->resolveMonthDaysDisplayOption(
            $request->input('month_days_display_option'),
            $actualMonthDays
        );
        $passportNumber = trim((string) $request->input('passport_num', ''));
        $residenceNumber = trim((string) $request->input('residence_num', ''));
        $workerScope = $request->input('worker_scope') === 'all' ? 'all' : 'current';

        $rows = Employee::query()
            ->onlyMaids()
            ->whereNotNull('maid_id')
            ->when($passportNumber !== '', function ($query) use ($passportNumber) {
                $query->whereHas('maid', function ($maidQuery) use ($passportNumber) {
                    $maidQuery->where('passport_num', 'like', '%' . $passportNumber . '%');
                });
            })
            ->when($residenceNumber !== '', function ($query) use ($residenceNumber) {
                $query->whereHas('maid', function ($maidQuery) use ($residenceNumber) {
                    $maidQuery->where('id_number', 'like', '%' . $residenceNumber . '%');
                });
            })
            ->when($workerScope === 'current', function ($query) {
                $query->whereHas('maid.rentalContracts', function ($contractQuery) {
                    $contractQuery->active();
                });
            })
            ->when($workerScope === 'all', function ($query) {
                $query->whereHas('maid');
            })
            ->whereDoesntHave('payrollItems.payroll', function ($query) use ($monthYear) {
                $query->where('month_year', $monthYear);
            })
            ->with([
                'maid.nationality',
                'maid.rentalContracts' => function ($query) use ($workerScope) {
                    $query->when($workerScope === 'current', function ($contractQuery) {
                            $contractQuery->active();
                        })
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
            'passportNumber' => $passportNumber,
            'residenceNumber' => $residenceNumber,
            'workerScope' => $workerScope,
            'monthDaysDisplayOption' => $monthDaysDisplayOption,
            'monthLabel' => $monthDate->locale(app()->getLocale())->translatedFormat('F Y'),
            'monthDays' => $monthDays,
            'actualMonthDays' => $actualMonthDays,
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
        $payroll->load('items.maid');

        return view('hr::hr.payrolls.show', [
            'bladeTitle' => 'تفاصيل مسير الرواتب',
            'payroll' => $payroll,
            'exportRoute' => route('payrolls.export', ['payroll_id' => $payroll->id]),
            'headerLogoUrl' => $this->resolveHeaderLogoUrl(),
            'monthLabel' => Carbon::createFromFormat('Y-m', $payroll->month_year)
                ->locale(app()->getLocale())
                ->translatedFormat('F Y'),
        ]);
    }

    public function export(Request $request)
    {
        $payrollIds = collect((array) $request->input('payroll_ids', []))
            ->push($request->input('payroll_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $payrolls = Payroll::query()
            ->when($payrollIds->isNotEmpty(), function ($query) use ($payrollIds) {
                $query->whereIn('id', $payrollIds->all());
            })
            ->when($payrollIds->isEmpty() && $request->filled('month'), function ($query) use ($request) {
                $query->where('month_year', $request->input('month'));
            })
            ->with(['items.maid'])
            ->latest('month_year')
            ->latest('id')
            ->get();

        if ($payrolls->isEmpty()) {
            return back()->with('error', 'لا توجد مسيرات رواتب للتصدير.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payrolls');
        $sheet->setRightToLeft(true);

        $headers = [
            '#',
            'الشهر',
            'اسم العاملة',
            'رقم الجواز',
            'رقم الإقامة',
            'الجنسية',
            'أيام العمل',
            'الراتب الأساسي',
            'حافز',
            'إضافات أخرى',
            'إجمالي الراتب',
            'غياب (أيام)',
            'سلفة',
            'جزاء',
            'خصومات أخرى',
            'إجمالي الخصومات',
            'صافي الراتب',
            'ملاحظة',
        ];

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEF2F7'],
            ],
        ]);

        $rowNumber = 2;
        $serial = 1;

        foreach ($payrolls as $payroll) {
            $monthLabel = Carbon::createFromFormat('Y-m', $payroll->month_year)
                ->locale(app()->getLocale())
                ->translatedFormat('F Y');

            foreach ($payroll->items as $item) {
                $sheet->fromArray([
                    $serial++,
                    $monthLabel,
                    $item->employee_name,
                    $item->maid?->passport_num,
                    $item->maid?->id_number,
                    $item->nationality_name,
                    $item->work_days + 0,
                    (float) $item->basic_salary,
                    (float) $item->incentive,
                    (float) $item->other_additions,
                    (float) $item->gross_salary,
                    $item->absence_days + 0,
                    (float) $item->advance,
                    (float) $item->penalty,
                    (float) $item->other_deductions,
                    (float) $item->total_deductions,
                    (float) $item->net_salary,
                    $item->notes,
                ], null, 'A' . $rowNumber);

                $rowNumber++;
            }
        }

        foreach (range(1, count($headers)) as $index) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setWidth(20);
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . '1');

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'payrolls_xlsx_');
        $writer->save($tempFile);

        return response()
            ->download(
                $tempFile,
                'payrolls_' . now()->format('Y_m_d_H_i_s') . '.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
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

    private function resolveMonthDaysDisplayOption(?string $option, int $actualMonthDays): array
    {
        $allowedOptions = ['30', '28', '31', 'current'];
        $option = in_array($option, $allowedOptions, true) ? $option : 'current';

        return [
            $option,
            $option === 'current' ? $actualMonthDays : (int) $option,
        ];
    }

    private function resolveHeaderLogoUrl(): ?string
    {
        $settings = Setting::query()->first();

        return $settings?->logo_header ? showFile($settings->logo_header) : null;
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
        $grossSalary = $this->calculateGrossSalary($workDays, $basicSalary, $incentive, $otherAdditions, $monthDays);
        $totalDeductions = $this->calculateTotalDeductions($advance, $penalty, $otherDeductions);

        return [
            'employee_id' => $employee->id,
            'maid_id' => $employee->maid_id,
            'employee_name' => $employee->name,
            'passport_num' => $employee->maid?->passport_num ?? '',
            'residence_num' => $employee->maid?->id_number ?? '',
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

    private function calculateGrossSalary(float $workingDays, float $basicSalary, float $incentive, float $otherAdditions, int $monthDays): float
    {
        return round(($workingDays * ($basicSalary / $monthDays)) + $incentive + $otherAdditions, 2);
    }

    private function calculateTotalDeductions(float $advance, float $penalty, float $otherDeductions): float
    {
        return round($advance + $penalty + $otherDeductions, 2);
    }
}
