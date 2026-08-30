<?php

namespace App\Console\Commands;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\commissions\CommissionBatchItem;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\RecruitmentCommissionService;
use Illuminate\Console\Command;

class ReconcileReturnedRecruitmentCommissions extends Command
{
    protected $signature = 'commissions:reconcile-returned-recruitment
                            {--dry-run : عرض الأثر فقط دون تعديل قاعدة البيانات}
                            {--admin= : رقم المستخدم الذي سيظهر في سجل التسوية}';

    protected $description = 'إقفال عمولات عقود الاستقدام المستردة/المرتجعة بصفر أو عكس العمولة المحتسبة';

    public function handle(RecruitmentCommissionService $service): int
    {
        $query = Contract::query()->whereIn('status', [
            ContractStatusEnum::Refunded->value,
            ContractStatusEnum::Back->value,
        ]);

        $contracts = (clone $query)->with('order.details')->get();
        $contractIds = $contracts->pluck('id');
        $items = CommissionBatchItem::query()
            ->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
            ->whereIn('source_id', $contractIds)
            ->get()
            ->keyBy('source_id');

        $withoutItem = $contracts->reject(fn ($contract) => $items->has($contract->id));
        $toZero = $withoutItem->filter(fn ($contract) =>
            (int) ($contract->marketer_id ?: $contract->order?->resolveResponsibleAdminId()) > 0
        );
        $withoutMarketer = $withoutItem->count() - $toZero->count();
        $toReverse = $items->filter(fn ($item) =>
            (float) $item->commission_amount > 0 && ! $item->commission_reversal_transaction_id
                && ! $service->isZeroed($item)
        );

        $this->table(
            ['العقود', 'مقفلة سابقًا', 'إقفال بصفر', 'تسوية معلقة', 'قيمة العمولة محل القرار', 'بلا مسوق'],
            [[
                $contractIds->count(),
                $items->count(),
                $toZero->count(),
                $toReverse->count(),
                number_format((float) $toReverse->sum('commission_amount'), 2),
                $withoutMarketer,
            ]]
        );

        if ($this->option('dry-run')) {
            $this->info('معاينة فقط: لم يتم تعديل قاعدة البيانات.');
            return self::SUCCESS;
        }

        $adminId = $this->option('admin') !== null ? (int) $this->option('admin') : null;
        $reviewed = 0;
        $query->orderBy('id')->chunkById(100, function ($contracts) use ($service, $adminId, &$reviewed) {
            foreach ($contracts as $contract) {
                if ($service->settleReturnedContract(
                    $contract,
                    $adminId,
                    'تسوية عقد مسترد/مرتجع ضمن مراجعة العمولات.'
                )) {
                    $reviewed++;
                }
            }
        });

        $changed = $toZero->count() + $toReverse->count();
        $this->info("اكتملت المراجعة: {$reviewed} عقدًا مرتبطًا بعمولة، و{$changed} تسوية جديدة دون تكرار القيود القائمة.");

        return self::SUCCESS;
    }
}
