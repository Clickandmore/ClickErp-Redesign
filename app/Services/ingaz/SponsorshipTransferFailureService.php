<?php

namespace App\Services\ingaz;

use App\Models\SponsorshipTransfer;
use App\Support\AutomaticJournalEntries;
use Carbon\Carbon;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

/**
 * القيد #17: فشل التجربة — رجوع العاملة للإيواء
 *
 * يُسجّل عند فشل فترة التجربة وإلغاء النقل.
 * - يعكس قيد بداية التجربة: يُقفل الإيراد المؤجل (2291) ويُعيد القيمة لذمة الكفيل القديم.
 * - تكلفة الإيواء تعود بقيد مستقل من ShelterCostLedgerService (1541 → 1521) بنفس مبلغ الخروج.
 * - يُثبت خصم أيام الاستخدام من قيمة العقد.
 */
class SponsorshipTransferFailureService
{
    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected ShelterCostLedgerService $shelterLedger
    ) {}

    public function record(SponsorshipTransfer $transfer): ?Constraint
    {
        if ($transfer->isRentalWorkflow()) {
            return null;
        }

        if (!AutomaticJournalEntries::enabled()) {
            return null;
        }

        $transfer->loadMissing([
            'contract.maid.nationality',
            'contract.requiredMaidNationality',
            'oldSponsor',
        ]);

        $date = ($transfer->failed_at ?? now())->toDateString();

        $activeYear = $this->yearService->checkDateRange($date);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ فشل التجربة.');
        }

        $nationalityName = $this->resolveNationalityName($transfer);
        if ($nationalityName === '') {
            throw new \RuntimeException('لا يمكن إنشاء القيد المحاسبي لأن جنسية العاملة غير محددة.');
        }

        $oldClientAccountId = (int) ($transfer->oldSponsor?->account_id ?? 0);
        if (!$oldClientAccountId) {
            throw new \RuntimeException('لا يوجد حساب محاسبي للكفيل القديم.');
        }

        $contractValue = $this->roundMoney((float) $transfer->contract_value);
        $dayDeduction  = $this->calculateDayDeduction($transfer, $contractValue);

        $advancePaymentsAccountId = $this->resolveAdvancePaymentsAccountId($nationalityName);
        $salesRecoveryAccountId   = $this->resolveSalesRecoveryAccountId();

        if (!$advancePaymentsAccountId) {
            throw new \RuntimeException('لا يوجد حساب دفعات مقدمة نقل خدمات مرتبط بالجنسية: ' . $nationalityName);
        }
        if (!$salesRecoveryAccountId) {
            throw new \RuntimeException('لا يوجد حساب إيراد استرداد مبيعات في النظام.');
        }

        $statement = 'فشل التجربة — رجوع العاملة للإيواء #' . $transfer->id;
        $lines = [];

        // 1. عكس قيد النقل: إعادة قيمة العقد من الإيراد المؤجل إلى ذمة الكفيل القديم
        $this->appendLine($lines, $advancePaymentsAccountId, 'debtor', $contractValue, 'عكس قيد النقل');
        $this->appendLine($lines, $oldClientAccountId, 'creditor', $contractValue, 'رد قيمة عقد النقل');

        // 2. تكلفة الإيواء تعود بقيد مستقل من دفتر الإيواء بنفس مبلغ الخروج (1541 → 1521).
        $this->shelterLedger->returnFromTrial($transfer, $date);

        // 3. خصم أيام الاستخدام من قيمة العقد (مدين على الكفيل القديم)
        if ($dayDeduction > 0) {
            $this->appendLine($lines, $oldClientAccountId, 'debtor', $dayDeduction, 'خصم أيام الاستخدام');
            $this->appendLine($lines, $salesRecoveryAccountId, 'creditor', $dayDeduction, 'خصم أيام الاستخدام');
        }

        if (empty($lines)) {
            return null;
        }

        $value = $this->roundMoney(array_sum(array_column($lines, 'debtor_value')));
        $data = [
            'code'                 => $this->constraintService->lastCode(),
            'date'                 => $date,
            'statement'            => $statement,
            'account_id'           => array_column($lines, 'account_id'),
            'account_type'         => array_column($lines, 'account_type'),
            'debtor_value'         => array_column($lines, 'debtor_value'),
            'creditor_value'       => array_column($lines, 'creditor_value'),
            'statement_account'    => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id'       => array_column($lines, 'cost_center_id'),
            'value'                => $value,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        $transfer->updateQuietly(['failure_constraint_id' => $constraint->id]);

        return $constraint;
    }

    private function calculateDayDeduction(SponsorshipTransfer $transfer, float $contractValue): float
    {
        $trialDays = (int) ($transfer->trial_days ?? 0);
        if ($trialDays <= 0 || $contractValue <= 0) {
            return 0.0;
        }

        $trialStarted = $transfer->trial_started_at ?? Carbon::parse($transfer->trial_start_date);
        $failedAt     = $transfer->failed_at ?? now();

        if (!$trialStarted) {
            return 0.0;
        }

        $daysUsed = max(0, (int) Carbon::parse($trialStarted)->startOfDay()->diffInDays(
            Carbon::parse($failedAt)->startOfDay(),
            false
        ));

        if ($daysUsed <= 0) {
            return 0.0;
        }

        $dailyRate = $contractValue / $trialDays;
        return $this->roundMoney($dailyRate * $daysUsed);
    }


    private function resolveNationalityName(SponsorshipTransfer $transfer): string
    {
        $maid = $transfer->contract?->maid;
        $nationality = $maid?->nationality ?? $transfer->contract?->requiredMaidNationality;
        return trim((string) ($nationality?->sub_title ?? $nationality?->title ?? ''));
    }

    private function resolveAdvancePaymentsAccountId(string $nationalityName): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'دفعات مقدمة نقل خدمات - ' . $nationalityName,
                'دفعات مقدمة نقل خدمات (' . $nationalityName . ')',
                'دفعات مقدمة نقل خدمات',
            ],
            ['دفعات مقدمة نقل خدمات'],
            fn ($q) => $q->where('name', 'like', '%دفعات مقدمة نقل خدمات%')
        );
    }



    private function resolveSalesRecoveryAccountId(): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'إيراد استرداد مبيعات',
                'إيراد استرداد',
                'إيراد الاسترداد',
            ],
            [],
            fn ($q) => $q->where('name', 'like', '%إيراد استرداد%')
        );
    }

    private function resolveAccountByCandidates(array $directCandidates, array $rootCandidates, callable $descendantFilter): ?int
    {
        foreach ($directCandidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }
            $accountId = Account::query()->where('name', $candidate)->value('id');
            if ($accountId) {
                return (int) $accountId;
            }
        }

        foreach ($rootCandidates as $rootName) {
            $root = Account::query()->with('childrenRecursive')->where('name', $rootName)->first();
            if (!$root) {
                continue;
            }
            $relatedIds = array_merge([$root->id], $root->getAllChildrenIds());
            $query = Account::query()->whereIn('id', $relatedIds);
            $descendantFilter($query);
            $accountId = $query->orderBy('id')->value('id');
            if ($accountId) {
                return (int) $accountId;
            }
        }

        if (!empty($directCandidates)) {
            $query = Account::query()->whereDoesntHave('children');
            $descendantFilter($query);
            $accountId = $query->orderBy('id')->value('id');
            if ($accountId) {
                return (int) $accountId;
            }
        }

        return null;
    }

    private function appendLine(array &$lines, int $accountId, string $type, float $amount, string $statement, ?int $costCenterId = null): void
    {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id'     => $accountId,
            'account_type'   => $type,
            'debtor_value'   => $type === 'debtor'   ? $amount : 0,
            'creditor_value' => $type === 'creditor' ? $amount : 0,
            'statement'      => $statement,
            'cost_center_id' => $costCenterId,
        ];
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
