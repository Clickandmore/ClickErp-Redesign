<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Services\OperationalSummaryExportService;
use Nami\Finance\Services\OperationalSummaryService;

class OperationalSummaryController extends Controller
{
    public function index(Request $request, OperationalSummaryService $service)
    {
        $request->merge([
            'from_date' => $request->input('from_date') ?: now()->startOfYear()->toDateString(),
            'to_date' => $request->input('to_date') ?: now()->toDateString(),
        ]);

        $input = $request->validate([
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d'],
            'scope' => ['nullable', Rule::in([OperationalSummaryService::SCOPE_PERIOD, OperationalSummaryService::SCOPE_OUTSTANDING])],
            'musaned_status' => ['nullable', Rule::in(OperationalSummaryService::MUSANED_STATUS_OPTIONS)],
            'per_page' => ['nullable', 'integer', Rule::in(OperationalSummaryService::PER_PAGE_OPTIONS)],
            'rental_page' => ['nullable', 'integer', 'min:1'],
            'recruitment_page' => ['nullable', 'integer', 'min:1'],
            'sponsorship_page' => ['nullable', 'integer', 'min:1'],
            'export' => ['nullable', 'in:xlsx'],
            'print' => ['nullable', 'in:1'],
        ]);

        if ($input['from_date'] > $input['to_date']) {
            throw ValidationException::withMessages([
                'from_date' => __('finance::finance.from_date_after_to_date'),
            ]);
        }

        $scope = $input['scope'] ?? OperationalSummaryService::SCOPE_PERIOD;
        $musanedStatus = $input['musaned_status'] ?? OperationalSummaryService::MUSANED_STATUS_ALL;
        $perPage = (int) ($input['per_page'] ?? OperationalSummaryService::DEFAULT_PER_PAGE);
        $exporting = ($input['export'] ?? null) === 'xlsx';
        $printing = ($input['print'] ?? null) === '1';

        // التصدير والطباعة يحملان كل الصفوف، لا صفحة واحدة منها.
        $report = $service->report($input['from_date'], $input['to_date'], [
            'scope' => $scope,
            'musaned_status' => $musanedStatus,
            'per_page' => $exporting || $printing ? null : $perPage,
            'rental_page' => (int) ($input['rental_page'] ?? 1),
            'recruitment_page' => (int) ($input['recruitment_page'] ?? 1),
            'sponsorship_page' => (int) ($input['sponsorship_page'] ?? 1),
        ]);

        if ($exporting) {
            return app(OperationalSummaryExportService::class)->download($report);
        }

        $report['print'] = $printing;

        // كل رابط صفحة يحتفظ بالفلاتر وبصفحة الجدول الآخر.
        $base = ['from_date' => $input['from_date'], 'to_date' => $input['to_date'], 'scope' => $scope, 'musaned_status' => $musanedStatus, 'per_page' => $perPage];
        $this->linkPaginator($report['rental_receivables'], $base + ['recruitment_page' => (int) ($input['recruitment_page'] ?? 1), 'sponsorship_page' => (int) ($input['sponsorship_page'] ?? 1)], 'rental_page');
        $this->linkPaginator($report['recruitment_receivables'], $base + ['rental_page' => (int) ($input['rental_page'] ?? 1), 'sponsorship_page' => (int) ($input['sponsorship_page'] ?? 1)], 'recruitment_page');
        $this->linkPaginator($report['sponsorship_receivables'], $base + [
            'rental_page' => (int) ($input['rental_page'] ?? 1),
            'recruitment_page' => (int) ($input['recruitment_page'] ?? 1),
        ], 'sponsorship_page');

        return view('finance::finance.operational_summary.index', [
            'bladeTitle' => __('finance::finance.operational_summary'),
            'report' => $report,
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function linkPaginator(array $section, array $query, string $pageName): void
    {
        $section['paginator']?->setPath(route('finance.operational-summary.index'))
            ->setPageName($pageName)
            ->appends($query);
    }
}
