<?php

namespace App\Http\Controllers\commissions;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Services\commissions\CommissionCalculationService;
use App\Services\commissions\CommissionProfileService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CommissionMarketerController extends Controller
{
    public function __construct(
        private readonly CommissionProfileService $profileService,
        private readonly CommissionCalculationService $calculationService
    ) {}

    public function index(Request $request)
    {
        $marketers = $this->profileService->getMarketersWithBalance();

        return view('commissions.marketers.index', compact('marketers'));
    }

    public function show(Request $request, Admin $admin)
    {
        abort_unless($this->isCommissionMarketer($admin), 404);

        $filters = $request->only([
            'date_filter', 'month', 'year', 'from_date', 'to_date',
            'service_type', 'commission_status',
        ]);

        $balance = $this->profileService->getMarketerBalance($admin->id);
        $summary = $this->profileService->getOperationSummary($admin->id);

        if ($request->ajax()) {
            $operations = $this->profileService->getOperations($admin->id, $filters);

            return DataTables::of($operations)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => $row['commissioned'] ? '' :
                    '<input type="checkbox" class="operation-check" name="operations[]" value="' . e($row['key']) .
                    '" data-amount="' . $row['base_amount'] . '">')
                ->editColumn('operation_date', fn ($row) => $row['operation_date']?->format('Y-m-d') ?? '-')
                ->editColumn('base_amount', fn ($row) => number_format($row['base_amount'], 2))
                ->addColumn('service_type_label', fn ($row) => $this->visaTypeLabel($row['service_type']))
                ->addColumn('is_paid_label', fn ($row) => $this->isPaidLabel($row['is_paid']))
                ->addColumn('commissioned_label', fn ($row) => $this->commissionStatusLabel($row))
                ->rawColumns(['checkbox', 'commissioned_label', 'is_paid_label'])
                ->make(true);
        }

        $balanceTransactions = CommissionBalanceTransaction::query()
            ->where('marketer_id', $admin->id)
            ->latest()
            ->take(10)
            ->get();

        $batches = CommissionBatch::query()
            ->where('marketer_id', $admin->id)
            ->with('items')
            ->latest()
            ->take(5)
            ->get();

        $visaTypeOptions = \App\Models\commissions\CommissionSetting::serviceTypeOptions();

        return view('commissions.marketers.show', compact(
            'admin', 'balance', 'summary', 'filters',
            'balanceTransactions', 'batches', 'visaTypeOptions'
        ));
    }

    public function preview(Request $request, Admin $admin)
    {
        abort_unless($this->isCommissionMarketer($admin), 404);

        $request->validate([
            'operations'   => ['required', 'array', 'min:1'],
            'operations.*' => ['required', 'string', 'max:100'],
        ]);

        $preview = $this->calculationService->previewBatch($admin->id, $request->operations);

        if ($preview['total_requests'] === 0) {
            return redirect()
                ->route('commissions.marketers.show', $admin->id)
                ->withErrors(['operations' => 'لم يتم العثور على عمليات مؤهلة للاحتساب.']);
        }

        return view('commissions.calculation.preview', compact('admin', 'preview'));
    }

    public function approve(Request $request, Admin $admin)
    {
        abort_unless($this->isCommissionMarketer($admin), 404);

        $request->validate([
            'operations'       => ['required', 'array', 'min:1'],
            'operations.*'     => ['required', 'string', 'max:100'],
            'methods'          => ['required', 'array'],
            'methods.*'        => ['required', 'in:percentage,fixed'],
            'values'           => ['required', 'array'],
            'values.*'         => ['required', 'numeric', 'min:0'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        if (count($request->operations) !== count($request->methods) ||
            count($request->operations) !== count($request->values)) {
            return back()->withErrors(['operations' => 'بيانات تفاصيل العمولة غير مكتملة.'])->withInput();
        }

        $adjustments = [];
        foreach ($request->operations as $index => $key) {
            $method = $request->methods[$index];
            $value = (float) $request->values[$index];
            if ($method === 'percentage' && $value > 100) {
                return back()->withErrors(['values' => 'نسبة العمولة لا يمكن أن تتجاوز 100%.'])->withInput();
            }
            $adjustments[$key] = ['method' => $method, 'value' => $value];
        }

        $batch = $this->calculationService->approveBatch(
            $admin->id,
            $request->operations,
            $adjustments,
            $request->notes,
            auth('admin')->id()
        );

        return redirect()
            ->route('commissions.marketers.show', $admin->id)
            ->with('success', "تم اعتماد العمولة بنجاح. إجمالي العمولة: " . number_format($batch->total_commission, 2) . " ريال");
    }

    private function visaTypeLabel(?string $type): string
    {
        return \App\Models\commissions\CommissionSetting::serviceTypeOptions()[$type] ?? ($type ?? '-');
    }

    private function isCommissionMarketer(Admin $admin): bool
    {
        return $admin->admin_type === 'customer_service'
            || $this->profileService->hasOperations($admin->id);
    }

    private function isPaidLabel(?bool $isPaid): string
    {
        if ($isPaid === null) {
            return '<span class="badge bg-light text-dark border">سداد العميل غير محدد</span>';
        }
        return $isPaid
            ? '<span class="badge bg-info text-dark">العميل مسدد</span>'
            : '<span class="badge bg-warning text-dark">العميل غير مسدد</span>';
    }

    private function commissionStatusLabel(array $operation): string
    {
        if ($operation['commission_reversed'] ?? false) {
            return '<span class="badge bg-danger">تم عكس العمولة</span>';
        }

        if ($operation['commission_paid_historically']) {
            return '<span class="badge bg-success">العمولة محتسبة ومسـددة سابقًا</span>';
        }

        if ($operation['commissioned']) {
            return '<span class="badge bg-primary">العمولة محتسبة — دفعة احتساب #'
                . $operation['commission_batch_id'] . '</span>';
        }

        return '<span class="badge bg-secondary">العمولة غير محتسبة</span>';
    }
}
