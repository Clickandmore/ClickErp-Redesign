<?php

namespace App\Console\Commands;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatchItem;
use App\Services\commissions\CommissionAccrualService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCommissionReversalConstraints extends Command
{
    protected $signature = 'commissions:backfill-reversal-constraints
                            {--apply : إنشاء القيود فعليًا؛ بدونه يعمل الأمر بوضع المعاينة}
                            {--marketer= : حصر المعالجة على رقم مسوق واحد}
                            {--transaction= : حصر المعالجة على رقم حركة عكس واحدة}';

    protected $description = 'إنشاء القيود المحاسبية الناقصة لحركات عكس العمولات السابقة دون تكرار';

    public function handle(CommissionAccrualService $accrualService): int
    {
        $query = DB::table('commission_balance_transactions as transactions')
            ->join('commission_batch_items as items', function ($join) {
                $join->on('items.id', '=', 'transactions.reference_id')
                    ->where('transactions.reference_type', '=', 'commission_reversal');
            })
            ->whereNull('items.commission_reversal_constraint_id')
            ->select('transactions.id')
            ->orderBy('transactions.id');

        if ($this->option('marketer') !== null) {
            $query->where('transactions.marketer_id', (int) $this->option('marketer'));
        }
        if ($this->option('transaction') !== null) {
            $query->where('transactions.id', (int) $this->option('transaction'));
        }

        $transactionIds = $query->pluck('transactions.id');
        $apply = (bool) $this->option('apply');
        $this->line($apply ? 'وضع التنفيذ' : 'وضع المعاينة فقط (أضف --apply للتنفيذ)');

        if ($transactionIds->isEmpty()) {
            $this->info('لا توجد حركات عكس عمولة تحتاج إلى قيد محاسبي.');

            return self::SUCCESS;
        }

        $rows = [];
        $ready = [];
        foreach ($transactionIds as $transactionId) {
            $transaction = CommissionBalanceTransaction::withoutGlobalScopes()->find($transactionId);
            $item = $transaction
                ? CommissionBatchItem::withoutGlobalScopes()->find($transaction->reference_id)
                : null;
            $batch = $item?->batch()->withoutGlobalScopes()->first();
            $marketer = $batch
                ? Admin::withTrashed()->find($batch->marketer_id)
                : null;
            $actorId = (int) ($transaction?->created_by ?: $batch?->approved_by ?: $batch?->created_by);
            $hasFinancialOrigin = (bool) ($batch?->accrual_constraint_id || $batch?->payable_account_id
                || $marketer?->commission_payable_account_id);
            $status = ! $transaction || ! $item || ! $batch
                ? 'بيانات الربط ناقصة'
                : (! $hasFinancialOrigin
                    ? 'لا يوجد أصل مالي أو حساب مسوق مربوط'
                    : ($actorId <= 0 || ! Admin::withTrashed()->whereKey($actorId)->exists()
                        ? 'منفذ العملية غير صالح'
                        : 'جاهز'));

            $rows[] = [
                $transactionId,
                $marketer?->id ?: '-',
                $marketer?->name ?: '-',
                $item?->id ?: '-',
                number_format((float) ($transaction?->amount ?? 0), 2),
                optional($transaction?->created_at)->format('Y-m-d') ?: '-',
                $status,
            ];
            if ($status === 'جاهز') {
                $item->setRelation('batch', $batch);
                $ready[] = [$transaction, $item, $actorId];
            }
        }

        $this->table(
            ['حركة العكس', 'المسوق', 'الاسم', 'بند العمولة', 'المبلغ', 'التاريخ', 'الحالة'],
            $rows
        );

        if (! $apply) {
            $this->info('معاينة فقط: لم يتم تعديل قاعدة البيانات. الجاهز للمعالجة: ' . count($ready));

            return self::SUCCESS;
        }

        $created = 0;
        $failed = 0;
        foreach ($ready as [$transaction, $item, $actorId]) {
            try {
                $constraint = $accrualService->createReversalForItem(
                    $item,
                    (float) $transaction->amount,
                    $actorId,
                    (string) ($item->commission_reversal_reason ?: $transaction->notes),
                    $transaction->created_at
                );
                $this->info("حركة #{$transaction->id}: أُنشئ/وُجد القيد #{$constraint->code}.");
                $created++;
            } catch (\Throwable $exception) {
                report($exception);
                $this->error("حركة #{$transaction->id}: {$exception->getMessage()}");
                $failed++;
            }
        }

        $skipped = count($rows) - count($ready);
        $this->line("النتيجة: {$created} قيد، {$skipped} متخطى، {$failed} فشل.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
