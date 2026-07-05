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
        ]);
    }

    public function export(Request $request, string $report)
    {
        $format = $request->query('format', 'pdf');
        abort_unless(in_array($format, ['pdf', 'excel'], true), 422, 'صيغة التصدير غير مدعومة.');

        return $this->reportService->export($report, $format, $request);
    }
}
