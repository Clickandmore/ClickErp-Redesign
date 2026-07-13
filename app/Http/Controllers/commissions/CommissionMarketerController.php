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
        $filters = $request->only(['date_filter', 'month', 'year', 'from_date', 'to_date']);

        $balance = $this->profileService->getMarketerBalance($admin->id);
        $totalCount = $this->profileService->getTotalDelegationsCount($admin->id);

        if ($request->ajax()) {
            $query = $this->profileService->getDelegationsQuery($admin->id, $filters);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', fn ($row) => $row->commissioned_at ? '' : '<input type="checkbox" class="delegation-check" name="delegation_ids[]" value="' . $row->id . '">')
                ->editColumn('delegation_date_gregorian', fn ($row) => $row->delegation_date_gregorian?->format('Y-m-d') ?? '-')
                ->addColumn('nationality_name', fn ($row) => optional($row->nationality)->name ?? '-')
                ->addColumn('country_name', fn ($row) => optional($row->country)->name ?? '-')
                ->addColumn('profession_name', fn ($row) => optional($row->profession)->name ?? '-')
                ->addColumn('client_name', fn ($row) => optional($row->user)->name ?? '-')
                ->addColumn('visa_type_label', fn ($row) => $this->visaTypeLabel($row->visa_type))
                ->addColumn('total_due', fn ($row) => number_format((float) optional($row->invoice)->total_due, 2))
                ->addColumn('is_paid_label', fn ($row) => $this->isPaidLabel($row->invoice))
                ->addColumn('commissioned_label', fn ($row) => $row->commissioned_at
                    ? '<span class="badge bg-success">محتسبة</span>'
                    : '<span class="badge bg-secondary">غير محتسبة</span>')
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
            'admin', 'balance', 'totalCount', 'filters',
            'balanceTransactions', 'batches', 'visaTypeOptions'
        ));
    }

    public function preview(Request $request, Admin $admin)
    {
        $request->validate([
            'delegation_ids'   => ['required', 'array', 'min:1'],
            'delegation_ids.*' => ['integer'],
        ]);

        $preview = $this->calculationService->previewBatch($admin->id, $request->delegation_ids);

        return view('commissions.calculation.preview', compact('admin', 'preview'));
    }

    public function approve(Request $request, Admin $admin)
    {
        $request->validate([
            'delegation_ids'   => ['required', 'array', 'min:1'],
            'delegation_ids.*' => ['integer'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        $batch = $this->calculationService->approveBatch(
            $admin->id,
            $request->delegation_ids,
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

    private function isPaidLabel($invoice): string
    {
        if (! $invoice) {
            return '<span class="badge bg-secondary">لا توجد فاتورة</span>';
        }
        return $invoice->is_paid
            ? '<span class="badge bg-success">مسدد</span>'
            : '<span class="badge bg-warning text-dark">غير مسدد</span>';
    }
}
