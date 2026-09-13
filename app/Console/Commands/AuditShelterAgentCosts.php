<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\ingaz\ShelterAgentCostRecoveryService;
use Illuminate\Console\Command;

/** Read-only report: no journal creation, no inferred opening balances. */
class AuditShelterAgentCosts extends Command
{
    protected $signature = 'finance:shelter-agent-cost-audit {--maid= : حصر المراجعة على عاملة}';
    protected $description = 'مراجعة تكلفة الوكيل والإعاشة والمراجع الناقصة قبل تفعيل الاسترداد';

    public function handle(ShelterAgentCostRecoveryService $service): int
    {
        $rows = [];
        foreach (Contract::query()->withoutGlobalScopes()->where('status', 'shelter')->whereIn('type', ['recruitment', 'maid'])
            ->when($this->option('maid'), fn ($q, $id) => $q->where('maid_id', $id))->cursor() as $contract) {
            $summary = $service->summary($contract);
            $issues = $summary['issues'];
            foreach ($summary['lots'] as $lot) {
                if ($lot['remaining'] > 0 && !$lot['valid']) { $issues[] = 'مرجع المكتب أو التكلفة غير مكتمل/تمت تسويته'; }
            }
            if ($summary['settled']) { $issues[] = 'تم عكس قيد المورد'; }
            if ($summary['available'] <= 0) { $issues[] = 'لا تكلفة وكيل متاحة'; }
            $rows[] = [$contract->id, $contract->maid_id, $summary['posted'], $summary['recovered'],
                $summary['available'], $summary['living'], implode('؛ ', array_unique($issues)) ?: 'قابل للمراجعة والاسترداد'];
        }
        $this->table(['العقد', 'العاملة', 'المثبت تاريخياً', 'صافي المسترد', 'المتبقي', 'الإعاشة', 'المراجعة'], $rows);
        $this->info('عرض فقط. طابق المستندات وكشف المكتب؛ لا يعني الرصيد إثبات التحصيل.');
        return self::SUCCESS;
    }
}
