<?php

namespace App\Console\Commands;

use App\Models\SponsorshipTransfer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Services\ConstraintService;

/**
 * يزيل زوج التحصيل المكرر من قيد بداية تجربة نقل الخدمات.
 *
 * الخلل: قيد بداية التجربة كان يثبت الدفعة المحصّلة مرة ثانية بجانب سند القبض،
 * وكان يبحث عن حساب البنك بمطابقة الاسم فيقع على حساب «عمولات ورسوم بنكية».
 * سند القبض هو المصدر المحاسبي الوحيد للتحصيل، فتُحذف السطور المكررة من قيد التجربة.
 */
class RepairSponsorshipTransferTrialPayment extends Command
{
    private const DUPLICATE_DEBIT_STATEMENT = 'وارد دفعة مقدمة من العميل الجديد';
    private const DUPLICATE_CREDIT_STATEMENT = 'سداد دفعة مقدمة';

    protected $signature = 'finance:repair-sponsorship-transfer-trial-payment
        {transfer?* : أرقام طلبات نقل الخدمات، ويمكن تمرير أكثر من رقم}
        {--pending : اكتشاف كل الطلبات المتأثرة تلقائياً بدل تمرير الأرقام}
        {--apply : تنفيذ الإصلاح فعلياً؛ بدونه يكون الأمر معاينة فقط}';

    protected $description = 'إزالة زوج التحصيل المكرر من قيد بداية تجربة نقل الخدمات';

    public function handle(ConstraintService $constraintService): int
    {
        $ids = $this->resolveTargetIds();
        if ($ids === null) {
            return self::FAILURE;
        }

        if ($ids === []) {
            $this->info('لا توجد طلبات نقل خدمات تحتوي قيود تجربتها على زوج تحصيل مكرر.');
            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');
        $this->line($apply
            ? '>> وضع التنفيذ الفعلي على ' . count($ids) . ' طلب.'
            : '>> وضع المعاينة على ' . count($ids) . ' طلب. أضف ‎--apply‎ للتنفيذ.');

        $rows = [];
        $repaired = 0;
        $failed = 0;
        $totalRemoved = 0.0;

        foreach ($ids as $id) {
            try {
                $plan = DB::transaction(
                    fn () => $this->repairOne($constraintService, (int) $id, $apply)
                );

                $repaired++;
                $totalRemoved += (float) $plan['amount_recorded_only_by_receipt'];
                $rows[] = [
                    $id,
                    $plan['trial_constraint_code'],
                    number_format((float) $plan['amount_recorded_only_by_receipt'], 2),
                    number_format((float) $plan['old_value'], 2) . ' ← ' . number_format((float) $plan['new_value'], 2),
                    $apply ? 'تم الإصلاح' : 'صالح للإصلاح',
                ];
            } catch (\Throwable $exception) {
                $failed++;
                $rows[] = [$id, '—', '—', '—', 'تخطٍّ: ' . $exception->getMessage()];
            }
        }

        $this->table(
            ['الطلب', 'رقم القيد', 'المبلغ المكرر', 'قيمة القيد', 'الحالة'],
            $rows
        );

        $this->line('الإجمالي المعالَج: ' . number_format($totalRemoved, 2) . ' ريال عبر ' . $repaired . ' طلب.');
        if ($failed > 0) {
            $this->warn($failed . ' طلب تم تخطيه دون أي تعديل؛ راجع سبب كل واحد أعلاه.');
        }
        if (!$apply && $repaired > 0) {
            $this->warn('لم يُنفَّذ أي تعديل. أعد الأمر نفسه مع ‎--apply‎ بعد اعتماد المحاسب.');
        }

        return $failed > 0 && $repaired === 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * أرقام الطلبات المستهدفة، أو null إذا كان الإدخال غير صالح.
     */
    private function resolveTargetIds(): ?array
    {
        $arguments = array_values(array_filter((array) $this->argument('transfer'), 'strlen'));
        $pending = (bool) $this->option('pending');

        if ($pending && $arguments !== []) {
            $this->error('استخدم ‎--pending‎ أو أرقام الطلبات، وليس الاثنين معاً.');
            return null;
        }

        if ($pending) {
            return $this->discoverAffectedTransferIds();
        }

        if ($arguments === []) {
            $this->error('مرّر رقم طلب واحداً على الأقل، أو استخدم ‎--pending‎ لاكتشاف الطلبات المتأثرة.');
            return null;
        }

        $ids = [];
        foreach ($arguments as $argument) {
            if (!ctype_digit((string) $argument) || (int) $argument < 1) {
                $this->error('رقم الطلب يجب أن يكون عدداً صحيحاً موجباً: ' . $argument);
                return null;
            }
            $ids[(int) $argument] = (int) $argument;
        }

        return array_values($ids);
    }

    /**
     * كل طلب ما زال قيد تجربته يحمل سطر التحصيل المكرر.
     */
    private function discoverAffectedTransferIds(): array
    {
        return SponsorshipTransfer::query()
            ->whereNotNull('trial_start_constraint_id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('fn_constraint_details')
                    ->whereColumn('fn_constraint_details.constraint_id', 'sponsorship_transfers.trial_start_constraint_id')
                    ->where('fn_constraint_details.statement', self::DUPLICATE_DEBIT_STATEMENT);
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * يفحص طلباً واحداً ويصلحه عند التنفيذ الفعلي. يرمي استثناءً إذا اختل أي شرط أمان.
     */
    private function repairOne(ConstraintService $constraintService, int $transferId, bool $apply): array
    {
        $transfer = SponsorshipTransfer::query()->lockForUpdate()->findOrFail($transferId);
        if (!$transfer->trial_start_constraint_id || !$transfer->receipt_constraint_id) {
            throw new \RuntimeException('الطلب يحتاج قيد تجربة وسند قبض مرتبطاً معاً.');
        }

        $trial = Constraint::without('admin')->lockForUpdate()->findOrFail((int) $transfer->trial_start_constraint_id);
        $receipt = Constraint::without('admin')->lockForUpdate()->findOrFail((int) $transfer->receipt_constraint_id);
        $trialLines = ConstraintDetails::query()->where('constraint_id', $trial->id)->lockForUpdate()->get();
        $receiptLines = ConstraintDetails::query()->where('constraint_id', $receipt->id)->lockForUpdate()->get();

        $amount = round((float) $transfer->paid_amount, 2);
        $clientAccountId = (int) ($transfer->newSponsor()->value('account_id') ?? 0);
        $receiptAccountId = (int) ($transfer->receipt_account_id ?? 0);
        if ($amount <= 0 || !$clientAccountId || !$receiptAccountId) {
            throw new \RuntimeException('المبلغ المدفوع أو حساب العميل أو حساب القبض غير متوفر.');
        }

        $receiptDebit = $receiptLines->first(fn ($line) => (int) $line->account_id === $receiptAccountId
            && $this->moneyEquals($line->debtor_value, $amount) && $this->moneyEquals($line->creditor_value, 0));
        $receiptCredit = $receiptLines->first(fn ($line) => (int) $line->account_id === $clientAccountId
            && $this->moneyEquals($line->creditor_value, $amount) && $this->moneyEquals($line->debtor_value, 0));
        $duplicateDebit = $trialLines->first(fn ($line) => $line->statement === self::DUPLICATE_DEBIT_STATEMENT
            && $this->moneyEquals($line->debtor_value, $amount) && $this->moneyEquals($line->creditor_value, 0));
        $duplicateCredit = $trialLines->first(fn ($line) => (int) $line->account_id === $clientAccountId
            && $line->statement === self::DUPLICATE_CREDIT_STATEMENT
            && $this->moneyEquals($line->creditor_value, $amount) && $this->moneyEquals($line->debtor_value, 0));

        if (!$receiptDebit || !$receiptCredit) {
            throw new \RuntimeException('سند القبض المرتبط لا يحتوي زوج البنك/العميل المتوقع.');
        }
        if (!$duplicateDebit || !$duplicateCredit) {
            throw new \RuntimeException('قيد التجربة لا يحتوي زوج التحصيل المكرر بنفس المبلغ؛ لم يُعدَّل شيء.');
        }

        $remaining = $trialLines->reject(fn ($line) => in_array($line->id, [$duplicateDebit->id, $duplicateCredit->id], true));
        $debitTotal = round((float) $remaining->sum('debtor_value'), 2);
        $creditTotal = round((float) $remaining->sum('creditor_value'), 2);
        if ($debitTotal <= 0 || !$this->moneyEquals($debitTotal, $creditTotal)) {
            throw new \RuntimeException('القيد المتبقي لن يكون متوازناً؛ لم يُعدَّل شيء.');
        }

        $plan = [
            'transfer_id' => $transfer->id,
            'trial_constraint_id' => $trial->id,
            'trial_constraint_code' => $trial->code,
            'receipt_constraint_id' => $receipt->id,
            'receipt_constraint_code' => $receipt->code,
            'removed_detail_ids' => [$duplicateDebit->id, $duplicateCredit->id],
            'removed_debit_account_id' => (int) $duplicateDebit->account_id,
            'old_value' => (float) $trial->value,
            'new_value' => $debitTotal,
            'amount_recorded_only_by_receipt' => $amount,
        ];

        if (!$apply) {
            return $plan;
        }

        $auditPath = storage_path('app/sponsorship-transfer-trial-payment-repair-'
            . $transfer->id . '-' . now()->format('Ymd-His') . '.json');
        if (file_put_contents($auditPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            throw new \RuntimeException('تعذّرت كتابة ملف تدقيق الإصلاح.');
        }

        ConstraintDetails::query()->whereKey([$duplicateDebit->id, $duplicateCredit->id])->delete();
        $trial->update(['value' => $debitTotal]);
        $constraintService->logConstraintActivity(
            $trial,
            'duplicate_sponsorship_transfer_payment_removed',
            'إزالة تحصيل مكرر من قيد بدء تجربة نقل الخدمات؛ التحصيل مثبت بسند القبض المرتبط.',
            $plan + ['audit_file' => $auditPath]
        );

        return $plan;
    }

    private function moneyEquals(mixed $left, mixed $right): bool
    {
        return abs(round((float) $left, 2) - round((float) $right, 2)) < 0.005;
    }
}
