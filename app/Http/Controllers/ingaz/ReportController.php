<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\Maid;
use App\Services\ingaz\ReportExportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ReportExportService $reportService
    ) {
    }

    public function index(Request $request)
    {
        $reports = $this->reportService->availableReports();

        abort_if(empty($reports), 403, 'ليس لديك صلاحية للوصول إلى إدارة التقارير.');

        $selectedReport = $request->get('report', array_key_first($reports));
        if (!isset($reports[$selectedReport])) {
            $selectedReport = array_key_first($reports);
        }

        return view('admin.ingaz.reports.index', [
            'reports' => $reports,
            'selectedReport' => $selectedReport,
            'filterOptions' => $this->reportService->filterOptions(),
            'reportFormatPermissions' => $this->reportService->formatPermissions(),
            'requestTypeOptions' => Maid::getRequestTypeOptions(),
            'maidStatusOptions' => $this->reportService->maidStatusOptions(),
            'orderStatusOptions' => $this->reportService->orderStatusOptions(),
            'clientStatusOptions' => $this->reportService->clientStatusOptions(),
            'recruitmentStatusOptions' => $this->reportService->recruitmentStatusOptions(),
            'warrantyStatusOptions' => $this->reportService->warrantyStatusOptions(),
            'rentStatusOptions' => $this->reportService->rentStatusOptions(),
            'rentExtensionOptions' => $this->reportService->rentExtensionFilterOptions(),
            'shelterGuaranteeStatusOptions' => $this->reportService->shelterGuaranteeStatusOptions(),
            'shelterWorkWillingnessOptions' => $this->reportService->shelterWorkWillingnessOptions(),
            'shelterInstantReceiptOptions' => $this->reportService->shelterInstantReceiptOptions(),
            'shelterServiceCategoryOptions' => $this->reportService->shelterServiceCategoryOptions(),
            'shelterReceiptSourceOptions' => $this->reportService->shelterReceiptSourceOptions(),
            'sponsorshipTransferStatusOptions' => $this->reportService->sponsorshipTransferStatusOptions(),
            'travelTypeOptions' => $this->reportService->travelTypeOptions(),
            'travelWithOptions' => $this->reportService->travelWithOptions(),
            'travelStatusOptions' => $this->reportService->travelStatusOptions(),
            'travelAirportOptions' => $this->reportService->travelAirportOptions(),
            'complaintStatusOptions' => $this->reportService->complaintStatusOptions(),
            'complaintPriorityOptions' => $this->reportService->complaintPriorityOptions(),
            'officeStatusOptions' => $this->reportService->officeStatusOptions(),
            'officeLinkedStateOptions' => $this->reportService->officeLinkedStateOptions(),
            'officeCostStateOptions' => $this->reportService->officeCostStateOptions(),
            'financialRequestTypeOptions' => $this->reportService->financialRequestTypeOptions(),
            'financialRequestStatusOptions' => $this->reportService->financialRequestStatusOptions(),
            'financialRequestPriorityOptions' => $this->reportService->financialRequestPriorityOptions(),
            'financialRequestScopeOptions' => $this->reportService->financialRequestScopeOptions(),
        ]);
    }

    public function arrivals(Request $request)
    {
        abort_unless(isset($this->reportService->availableReports()['recruitment_contracts']), 403);
        return view('admin.ingaz.reports.arrivals', [
            'payload' => $this->reportService->arrivalReport($request),
            'filterOptions' => $this->reportService->filterOptions(),
            'formats' => $this->reportService->formatPermissions()['recruitment_contracts'],
            'statuses' => $this->reportService->recruitmentStatusOptions(),
            'branchSelector' => app(\App\Services\BranchAccessService::class)->selectorData($request),
            'canDocumentDelivery' => AdminGuardHasAnyPerm([\App\NewPermKeys::REC_CONTRACTS_EDIT, \App\NewPermKeys::REC_CONTRACTS_MANAGE]),
        ]);
    }

    public function documentDelivery(Request $request, int $contract)
    {
        abort_unless(AdminGuardHasAnyPerm([\App\NewPermKeys::REC_CONTRACTS_EDIT, \App\NewPermKeys::REC_CONTRACTS_MANAGE]), 403);
        $validated = $request->validate([
            'client_delivered_at' => 'required|date|before_or_equal:now',
            'client_delivery_note' => 'nullable|string|max:1000',
        ]);
        abort_unless(\Illuminate\Support\Facades\Schema::hasColumn('contracts', 'client_delivered_at'), 503, 'يرجى تطبيق تحديث قاعدة البيانات لتوثيق التسليم.');
        \Illuminate\Support\Facades\DB::transaction(function () use ($contract, $validated) {
            $row = \App\Models\Contract::query()->whereIn('type', ['recruitment', 'maid'])
                ->with(['travelHistory', 'completedArrivalTravel', 'contract_status_logs', 'maid'])
                ->lockForUpdate()->findOrFail($contract);
            if ($row->completedArrivalTravel) $row->setRelation('travelHistory', $row->completedArrivalTravel);
            $arrival = $row->resolveRecruitmentArrivalDateTime();
            if (!$arrival && $row->contract_status_logs->contains('status', 'arrival')) {
                $arrival = \App\Support\RecruitmentArrivalMetrics::date($row->info?->arrival_date)
                    ?: \App\Support\RecruitmentArrivalMetrics::date($row->contract_status_logs->where('status', 'arrival')->sortBy('id')->first()?->date);
            }
            $delivery = \Carbon\Carbon::parse($validated['client_delivered_at']);
            if (!$arrival || $delivery->lt($arrival)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'client_delivered_at' => 'يجب توثيق وصول فعلي أولًا، وأن يكون التسليم في وقت الوصول أو بعده.',
                ]);
            }
            $row->update([
                'client_delivered_at' => $delivery,
                'client_delivery_recorded_by' => auth('admin')->id(),
                'client_delivery_note' => $validated['client_delivery_note'] ?? null,
            ]);
        });
        return redirect()->back()->with('success', 'تم توثيق تسليم العاملة للعميل.');
    }
    public function export(Request $request, string $report)
    {
        $format = $request->query('format', 'pdf');
        abort_unless(in_array($format, ['pdf', 'excel'], true), 422, 'صيغة التصدير غير مدعومة.');

        return $this->reportService->export($report, $format, $request);
    }
}
