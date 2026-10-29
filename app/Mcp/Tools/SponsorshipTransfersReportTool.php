<?php

namespace App\Mcp\Tools;

use App\Mcp\Tools\Concerns\ReportsOnPeriods;
use App\Models\auth\Admin;
use App\Models\SponsorshipTransfer;

/**
 * تقرير نقل الكفالة (نقل الخدمات) لفترة حسب تاريخ الإنشاء. SponsorshipTransfer عليه BranchScoped
 * وSoftDeletes. العاملة تأتي عبر العقد المصدر (لا يوجد maid_id).
 */
class SponsorshipTransfersReportTool extends McpTool
{
    use ReportsOnPeriods;

    public function name(): string
    {
        return 'sponsorship_transfers_report';
    }

    public function description(): string
    {
        return 'تقرير نقل الكفالة (نقل الخدمات) لفترة: الإجمالي، العدد حسب الحالة وحالة الدفع والمصدر (عادي/من الإيجار)، '
            . 'قيمة العقود والمدفوع والمتبقي، المسوّق، الفرع، الاتجاه الشهري، وما تم نقله أو فشل في الفترة. '
            . 'Sponsorship transfers report: totals by status and payment, money, marketer, branch, transferred and failed.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => $this->periodProperties('تاريخ إنشاء الطلب') + [
                'status' => [
                    'type' => 'string',
                    'enum' => ['pending', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'trial_started', 'approved', 'trial', 'transferred', 'failed', 'rejected', 'completed'],
                ],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'SponsorshipTransfer.index';
    }

    public function category(): string
    {
        return 'contracts';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $period = $this->resolvePeriod($arguments);
        $base = SponsorshipTransfer::query();

        $query = $this->applyPeriod(clone $base, 'sponsorship_transfers.created_at', $period);
        if (!empty($arguments['status'])) {
            $query->where('sponsorship_transfers.status', (string) $arguments['status']);
        }

        $money = (clone $query)->toBase()
            ->selectRaw('COALESCE(SUM(contract_value), 0) as value, COALESCE(SUM(paid_amount), 0) as paid, COALESCE(SUM(remaining_amount), 0) as remaining')
            ->first();

        return $this->periodSummary($period) + [
            'total' => (clone $query)->count(),
            'by_status' => $this->labeled($this->countBy($query, 'sponsorship_transfers.status'), fn ($status) => $this->statusLabel($status)),
            'by_payment_status' => $this->countBy($query, 'sponsorship_transfers.payment_status'),
            'by_source' => $this->countBy($query, 'sponsorship_transfers.workflow_source'),
            'money' => [
                'currency' => 'SAR',
                'contracts_value' => round((float) $money->value, 2),
                'paid' => round((float) $money->paid, 2),
                'remaining' => round((float) $money->remaining, 2),
            ],
            'by_marketer' => $this->countByJoin($query, 'sponsorship_transfers', fn ($q) => $q
                ->leftJoin('admins as marketer', 'marketer.id', '=', 'sponsorship_transfers.marketer_id'), 'marketer.name'),
            'by_branch' => $this->countByJoin($query, 'sponsorship_transfers', fn ($q) => $q
                ->leftJoin('branches', 'branches.id', '=', 'sponsorship_transfers.branch_id'), 'branches.name_ar'),
            'monthly' => $this->monthlyTrend($query, 'sponsorship_transfers.created_at'),
            'transferred_in_period' => $this->applyPeriod((clone $base)->whereNotNull('sponsorship_transfers.transferred_at'), 'sponsorship_transfers.transferred_at', $period)->count(),
            'failed_in_period' => $this->applyPeriod((clone $base)->whereNotNull('sponsorship_transfers.failed_at'), 'sponsorship_transfers.failed_at', $period)->count(),
            'notes' => 'by_source: standard = نقل عادي، rental = نقل من عقد إيجار. trial وtrial_started كلاهما فترة تجربة.',
        ];
    }

    private function statusLabel(string $status): ?string
    {
        return (new SponsorshipTransfer())->forceFill(['status' => $status])->status_text;
    }
}
