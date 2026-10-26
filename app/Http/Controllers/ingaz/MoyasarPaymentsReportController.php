<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\PermKeys;
use App\Services\PaymentGateways\MoyasarPaymentsReport;
use App\Services\PaymentGateways\ReportExporter;
use App\Services\PaymentGateways\ReportPeriod;
use App\Support\MoyasarSettings;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/** لوحة مدفوعات ميسر لكل الخدمات (تأجير، تجديد، نقل خدمات، ساعات، تطبيق)؛ للعرض والتصدير فقط. */
class MoyasarPaymentsReportController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request, MoyasarPaymentsReport $report)
    {
        abort_unless(MoyasarSettings::reportsEnabled(), 404);
        abort_unless(@AdminGuardHasPerm(PermKeys::PAYMENTS_MOYASAR_VIEW), 403);
        $period = ReportPeriod::fromRequest($request);
        $filters = $request->only(['service', 'status', 'search']);
        $rows = $report->rows($period);

        return view('admin.payment_gateways.moyasar', [
            'period' => $period,
            'filters' => $filters,
            'summary' => $report->summary($period, $rows),
            'charts' => $report->charts($period, $rows),
            'payments' => $this->paginate($report->table($period, $rows, $filters), $request),
            'services' => MoyasarPaymentsReport::SERVICES,
            'statuses' => MoyasarPaymentsReport::STATUSES,
        ]);
    }

    /** تصدير ما تعرضه الصفحة بنفس الفترة والفلاتر: ملخص المؤشرات + كل العمليات (بلا ترقيم صفحات). */
    public function export(Request $request, MoyasarPaymentsReport $report, ReportExporter $exporter)
    {
        abort_unless(MoyasarSettings::reportsEnabled(), 404);
        abort_unless(@AdminGuardHasPerm(PermKeys::PAYMENTS_MOYASAR_EXPORT), 403);
        $request->validate(['format' => ['required', 'in:xlsx,pdf']]);
        $format = $request->input('format');
        $period = ReportPeriod::fromRequest($request);
        $filters = $request->only(['service', 'status', 'search']);
        $rows = $report->rows($period);
        $summary = $report->summary($period, $rows);
        $money = fn ($value) => number_format((float) $value, 2) . ' ' . __('payment_gateways.chart.currency');

        $document = [
            'title' => __('payment_gateways.moyasar.title'),
            'period' => $period->label(),
            'filters' => $this->filterLabels($filters),
            'summary' => [
                [__('payment_gateways.kpi.billed'), $money($summary['billed'])],
                [__('payment_gateways.kpi.collected'), $money($summary['collected'])],
                [__('payment_gateways.chart.refunded'), $money($summary['refunded'])],
                [__('payment_gateways.kpi.net'), $money($summary['net'])],
                [__('payment_gateways.kpi.pending'), $money($summary['pending'])],
                [__('payment_gateways.kpi.conversion'), $summary['conversion'] . '%'],
                [__('payment_gateways.kpi.average'), $money($summary['average'])],
                [__('payment_gateways.export.operations'), (string) $summary['count']],
            ],
            'sheets' => [[
                'name' => __('payment_gateways.moyasar.table'),
                'columns' => [
                    __('payment_gateways.table.reference'), __('payment_gateways.table.service'), __('payment_gateways.table.customer'),
                    __('payment_gateways.export.phone'), __('payment_gateways.moyasar.gateway_id'), __('payment_gateways.table.amount'),
                    __('payment_gateways.table.refunded'), __('payment_gateways.table.status'), __('payment_gateways.table.created'),
                    __('payment_gateways.table.paid_at'),
                ],
                'money' => [5, 6],
                'rows' => $report->table($period, $rows, $filters)->map(fn ($row) => [
                    $row['reference'], __('payment_gateways.service.' . $row['service']), $row['customer'] ?: '—', $row['phone'] ?: '—',
                    $row['gateway_id'] ?: '—', $row['amount'], $row['refunded'], __('payment_gateways.status.' . $row['status']),
                    $row['created_at']?->format('Y-m-d H:i') ?? '—', $row['paid_at']?->format('Y-m-d H:i') ?? '—',
                ])->all(),
            ]],
        ];

        $filename = 'moyasar-payments-' . ($period->mode === 'month' ? $period->month() : $period->from->format('Ymd') . '-' . $period->to->format('Ymd'));

        return $format === 'pdf'
            ? $exporter->pdf($document, 'moyasar', $filename)
            : $exporter->xlsx($document, 'moyasar', $filename);
    }

    /** الفلاتر المطبقة بصيغة مقروءة لرأس الملف. */
    private function filterLabels(array $filters): array
    {
        $labels = [];
        if (in_array($filters['service'] ?? null, MoyasarPaymentsReport::SERVICES, true)) {
            $labels[__('payment_gateways.filters.service')] = __('payment_gateways.service.' . $filters['service']);
        }
        if (in_array($filters['status'] ?? null, MoyasarPaymentsReport::STATUSES, true)) {
            $labels[__('payment_gateways.filters.status')] = __('payment_gateways.status.' . $filters['status']);
        }
        if (trim((string) ($filters['search'] ?? '')) !== '') {
            $labels[__('payment_gateways.filters.search')] = trim($filters['search']);
        }

        return $labels;
    }

    private function paginate(Collection $rows, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));

        return new LengthAwarePaginator($rows->forPage($page, self::PER_PAGE)->values(), $rows->count(), self::PER_PAGE, $page, [
            'path' => $request->url(),
            'query' => $request->except('page'),
        ]);
    }
}
