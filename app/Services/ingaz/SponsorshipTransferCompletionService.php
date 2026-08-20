<?php

namespace App\Services\ingaz;

use App\Models\ShelterLivingExpenseAllocation;
use App\Models\SponsorshipTransfer;
use App\Support\AutomaticJournalEntries;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

/**
 * القيد #16: إتمام نقل الخدمات
 *
 * يُسجّل عند تأكيد اكتمال النقل بعد انتهاء التجربة بنجاح.
 * - يُقفل الإيراد المؤجل (2291) ويعترف بإيراد نقل الخدمات (4311).
 * - يُقفل تكلفة إيواء التجربة (1541).
 * - يُثبت رسوم التأشيرة على العميل القديم (إن وُجدت).
 */
class SponsorshipTransferCompletionService
{
    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService
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

        $date = ($transfer->transferred_at ?? now())->toDateString();

        $activeYear = $this->yearService->checkDateRange($date);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ إتمام النقل.');
        }

        $nationalityName = $this->resolveNationalityName($transfer);
        if ($nationalityName === '') {
            throw new \RuntimeException('لا يمكن إنشاء القيد المحاسبي لأن جنسية العاملة غير محددة.');
        }

        $contractValue = $this->roundMoney((float) $transfer->contract_value);
        $housingCost   = $this->resolveHousingCost($transfer);
        $revenueAmount = $this->roundMoney($contractValue - $housingCost);
        $visaFee       = $this->roundMoney((float) ($transfer->visa_fee_amount ?? 0));

        $oldClientAccountId       = (int) ($transfer->oldSponsor?->account_id ?? 0);
        $advancePaymentsAccountId = $this->resolveAdvancePaymentsAccountId($nationalityName);
        $trialHousingAccountId    = $this->resolveTrialHousingAccountId();
        $transferRevenueAccountId = $this->resolveTransferRevenueAccountId($nationalityName);
        $visaFeesAccountId        = $visaFee > 0 ? $this->resolveVisaFeesAccountId() : null;
        $maidAccountId            = $visaFee > 0 ? $this->resolveMaidAccountId($transfer) : null;

        if (!$advancePaymentsAccountId) {
            throw new \RuntimeException('لا يوجد حساب دفعات مقدمة نقل خدمات مرتبط بالجنسية: ' . $nationalityName);
        }
        if (!$trialHousingAccountId) {
            throw new \RuntimeException('لا يوجد حساب مصروفات مدفوعة مقدماً - إيواء التجربة.');
        }
        if (!$transferRevenueAccountId) {
            throw new \RuntimeException('لا يوجد حساب إيرادات نقل خدمات مرتبط بالجنسية: ' . $nationalityName);
        }

        $statement = 'إتمام نقل الخدمات #' . $transfer->id;
        $lines = [];

        // 1. إقفال الإيراد المؤجل والاعتراف بإيراد النقل
        $this->appendLine($lines, $advancePaymentsAccountId, 'debtor', $contractValue, 'إتمام النقل');

        if ($housingCost > 0) {
            $this->appendLine($lines, $trialHousingAccountId, 'creditor', $housingCost, 'إقفال تكلفة الإيواء');
        }

        if ($revenueAmount > 0) {
            $this->appendLine($lines, $transferRevenueAccountId, 'creditor', $revenueAmount, 'الاعتراف بإيراد النقل');
        }

        // 2. رسوم التأشيرة (إن وُجدت)
        if ($visaFee > 0 && $oldClientAccountId && $visaFeesAccountId) {
            // تسوية رسوم التأشيرة مع العاملة
            if ($maidAccountId) {
                $this->appendLine($lines, $oldClientAccountId, 'debtor', $visaFee, 'تسوية رسوم التأشيرة');
                $this->appendLine($lines, $maidAccountId, 'creditor', $visaFee, 'تسوية رسوم التأشيرة');
            }

            // رسوم التأشيرة للشركة
            $this->appendLine($lines, $visaFeesAccountId, 'creditor', $visaFee, 'رسوم التأشيرة');
            $this->appendLine($lines, $oldClientAccountId, 'debtor', $visaFee, 'رسوم التأشيرة');
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

        $transfer->updateQuietly(['completion_constraint_id' => $constraint->id]);

        return $constraint;
    }

    private function resolveHousingCost(SponsorshipTransfer $transfer): float
    {
        $contractId = $transfer->contract_id;
        if (!$contractId) {
            return 0.0;
        }

        return $this->roundMoney(
            (float) ShelterLivingExpenseAllocation::where('contract_id', $contractId)->sum('amount')
        );
    }

    private function resolveNationalityName(SponsorshipTransfer $transfer): string
    {
        $maid = $transfer->contract?->maid;
        $nationality = $maid?->nationality ?? $transfer->contract?->requiredMaidNationality;
        return trim((string) ($nationality?->sub_title ?? $nationality?->title ?? ''));
    }

    private function resolveMaidAccountId(SponsorshipTransfer $transfer): ?int
    {
        $maid = $transfer->contract?->maid;
        if (!$maid) {
            return null;
        }

        // محاولة جلب حساب العاملة من جدول المستخدمين
        $accountId = \DB::table('users')
            ->where('id', $maid->id)
            ->value('account_id');

        return $accountId ? (int) $accountId : null;
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

    private function resolveTrialHousingAccountId(): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'مصروفات مدفوعة مقدما - ايواء التجربة',
                'مصروفات مدفوعة مقدماً - إيواء التجربة',
                'ايواء التجربة',
            ],
            [],
            fn ($q) => $q->where('name', 'like', '%ايواء التجربة%')
                         ->orWhere('name', 'like', '%إيواء التجربة%')
        );
    }

    private function resolveTransferRevenueAccountId(string $nationalityName): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'ايرادات نقل خدمات ' . $nationalityName,
                'إيرادات نقل خدمات ' . $nationalityName,
                'ايرادات نقل خدمات (' . $nationalityName . ')',
                'إيرادات نقل خدمات',
                'ايرادات نقل خدمات',
            ],
            ['إيراد نقل الخدمات', 'ايرادات نقل خدمات'],
            fn ($q) => $q->where('name', 'like', '%نقل خدمات%')
        );
    }

    private function resolveVisaFeesAccountId(): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'رسوم تأشيرات العملاء',
                'رسوم التأشيرات',
                'رسوم تأشيرة',
            ],
            [],
            fn ($q) => $q->where('name', 'like', '%تأشير%')
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
