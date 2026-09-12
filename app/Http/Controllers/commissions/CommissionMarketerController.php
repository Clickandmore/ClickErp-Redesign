<?php

namespace App\Http\Controllers\commissions;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatchItem;
use App\Services\commissions\CommissionCalculationService;
use App\Services\commissions\CommissionProfileService;
use App\Services\commissions\RecruitmentCommissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class CommissionMarketerController extends Controller
{
    public function __construct(
        private readonly CommissionProfileService $profileService,
        private readonly CommissionCalculationService $calculationService,
        private readonly RecruitmentCommissionService $recruitmentCommissionService
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

        if ($request->ajax()) {
            $operations = $this->profileService->getOperations($admin->id, $filters);

            return DataTables::of($operations)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => ($row['commissioned'] || ! $row['commission_eligible']) ? '' :
                    '<input type="checkbox" class="operation-check" name="operations[]" value="' . e($row['key']) .
                    '" data-amount="' . $row['base_amount'] . '">')
                ->editColumn('operation_date', fn ($row) => $row['operation_date']?->format('Y-m-d') ?? '-')
                ->editColumn('base_amount', fn ($row) => number_format($row['base_amount'], 2))
                ->addColumn('service_type_label', fn ($row) => $this->visaTypeLabel($row['service_type']))
                ->addColumn('is_paid_label', fn ($row) => $this->isPaidLabel($row['is_paid']))
                ->addColumn('billing_label', fn ($row) => $this->billingLabel($row))
                ->addColumn('commissioned_label', fn ($row) => $this->commissionStatusLabel($row))
                ->rawColumns(['checkbox', 'billing_label', 'commissioned_label', 'is_paid_label'])
                ->make(true);
        }

        $balance = $this->profileService->getMarketerBalance($admin->id);
        $summary = $this->profileService->getOperationSummary($admin->id);

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

        if ($request->isMethod('get')) {
            return redirect()
                ->route('commissions.marketers.show', $admin->id)
                ->with('info', 'حدد العمليات المطلوبة ثم اضغط «مراجعة وتحديد العمولة» لفتح المعاينة.');
        }

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
            throw ValidationException::withMessages([
                'operations' => 'بيانات تفاصيل العمولة غير مكتملة.',
            ]);
        }

        $adjustments = [];
        foreach ($request->operations as $index => $key) {
            $method = $request->methods[$index];
            $value = (float) $request->values[$index];
            if ($method === 'percentage' && $value > 100) {
                throw ValidationException::withMessages([
                    'values' => 'نسبة العمولة لا يمكن أن تتجاوز 100%.',
                ]);
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

        $message = 'تم اعتماد العمولة وإنشاء قيد الاستحقاق #'
            . ($batch->accrualConstraint?->code ?? $batch->accrual_constraint_id)
            . '. إجمالي العمولة: ' . number_format($batch->total_commission, 2) . ' ريال';
        $redirect = route('commissions.marketers.show', $admin->id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirect,
            ]);
        }

        return redirect($redirect)->with('success', $message);
    }

    public function resolveReturnedCommission(Request $request, Admin $admin, CommissionBatchItem $item)
    {
        abort_unless($this->isCommissionMarketer($admin), 404);
        abort_unless((int) $item->batch?->marketer_id === (int) $admin->id, 404);

        $validated = $request->validate([
            'decision' => ['required', 'in:deducted,zeroed'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->recruitmentCommissionService->resolveSettlement(
            $item,
            $validated['decision'],
            (int) auth('admin')->id(),
            $validated['notes'] ?? null
        );

        $message = $validated['decision'] === RecruitmentCommissionService::SETTLEMENT_DEDUCTED
            ? 'تم اعتماد خصم العمولة المستردة من رصيد المسوق.'
            : 'تم إقفال العمولة المستردة بصفر دون خصم من رصيد المسوق.';

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : back()->with('success', $message);
    }

    private function visaTypeLabel(?string $type): string
    {
        return \App\Models\commissions\CommissionSetting::serviceTypeOptions()[$type] ?? ($type ?? '-');
    }

    private function isCommissionMarketer(Admin $admin): bool
    {
        return $this->profileService->marketersQuery()->whereKey($admin->id)->exists();
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

    private function billingLabel(array $operation): string
    {
        $billing = $operation['billing'] ?? null;
        if ($billing === null) {
            return '<span class="text-muted">—</span>';
        }

        if (! $billing['invoiced']) {
            return '<span class="badge bg-warning text-dark">لم تُفوتر</span>';
        }

        if (($billing['kind'] ?? null) === 'extension_period') {
            $periodStart = optional($billing['period_start'] ?? null)?->format('Y-m-d') ?: '-';
            $periodEnd = optional($billing['period_end'] ?? null)?->format('Y-m-d') ?: '-';
            $parts = [
                '<span class="badge bg-info text-dark">تمديد #' . e((string) $billing['extension_id'])
                    . ' · الفترة ' . e((string) $billing['period_sequence'])
                    . '/' . e((string) $billing['period_count']) . '</span>',
                '<span class="small d-block mt-1">فترة الاستحقاق: ' . e($periodStart) . ' — ' . e($periodEnd) . '</span>',
            ];

            if ($billing['credit_note_amount'] > 0) {
                $parts[] = '<span class="badge bg-danger">إشعار دائن −'
                    . number_format($billing['credit_note_amount'], 2) . '</span>';
            }

            $parts[] = '<span class="small d-block mt-1">قيمة فترة التمديد: '
                . number_format($billing['net_amount'], 2) . ' ر.س</span>';

            return implode(' ', $parts);
        }

        $parts = [
            '<span class="badge bg-success">فاتورة #' . e((string) $billing['invoice_number']) . '</span>',
        ];

        if ($billing['credit_note_amount'] > 0) {
            $parts[] = '<span class="badge bg-danger">إشعار دائن −'
                . number_format($billing['credit_note_amount'], 2) . '</span>';
        }

        $parts[] = '<span class="small d-block mt-1">صافي الفوترة: '
            . number_format($billing['net_amount'], 2) . ' ر.س</span>';

        return implode(' ', $parts);
    }

    private function commissionStatusLabel(array $operation): string
    {
        if ($operation['commission_covered_by_legacy_batch'] ?? false) {
            return '<span class="badge bg-primary">مشمولة ضمن احتساب سابق للعقد — دفعة #'
                . e((string) $operation['commission_batch_id']) . '</span>';
        }

        if ($operation['commission_reversed'] ?? false) {
            return '<span class="badge bg-danger">تم عكس العمولة — مستحق للشركة</span>';
        }

        if (! ($operation['commission_eligible'] ?? true)
            && ! ($operation['commissioned'] ?? false)
            && ! empty($operation['commission_ineligible_reason'])) {
                return '<span class="badge bg-secondary">'
                    . e($operation['commission_ineligible_reason']) . '</span>';
        }

        if (! ($operation['commission_eligible'] ?? true)) {
            if (($operation['return_settlement_status'] ?? null) === RecruitmentCommissionService::SETTLEMENT_PENDING) {
                $url = route('commissions.marketers.returned-commissions.resolve', [
                    'admin' => request()->route('admin')->id,
                    'item' => $operation['commission_item_id'],
                ]);

                return '<div class="d-flex flex-column gap-1 align-items-start">'
                    . '<span class="badge bg-warning text-dark">بانتظار اعتماد تسوية العمولة — الرصيد لم يتغير</span>'
                    . '<span class="small text-muted">العمولة السابقة: ' . number_format($operation['commission_amount'], 2) . ' ر.س</span>'
                    . '<div class="d-flex gap-1">'
                    . '<button type="button" class="btn btn-xs btn-danger returned-commission-decision" data-url="' . e($url) . '" data-decision="deducted">اعتماد الخصم</button>'
                    . '<button type="button" class="btn btn-xs btn-outline-secondary returned-commission-decision" data-url="' . e($url) . '" data-decision="zeroed">إقفال بصفر</button>'
                    . '</div></div>';
            }

            if (($operation['return_settlement_status'] ?? null) === RecruitmentCommissionService::SETTLEMENT_ZEROED) {
                return '<span class="badge bg-secondary">عقد مسترد/مرتجع — أُقفل بصفر دون خصم</span>';
            }

            if (! ($operation['commissioned'] ?? false) || (float) ($operation['commission_amount'] ?? 0) <= 0) {
                return '<span class="badge bg-dark">عقد مسترد/مرتجع — العمولة 0.00</span>';
            }
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
