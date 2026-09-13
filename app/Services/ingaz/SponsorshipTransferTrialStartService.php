<?php

namespace App\Services\ingaz;

use App\Models\SponsorshipTransfer;
use App\Support\AutomaticJournalEntries;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

/**
 * القيد #15: خروج العاملة للتجربة — نقل الخدمات
 *
 * يُسجّل عند تسليم العاملة للكفيل الجديد وبداية فترة التجربة.
 * - يُثبت قيمة العقد كإيراد مؤجل في حساب الدفعات المقدمة (2291).
 * - تكلفة الإيواء تخرج بقيد S3 مستقل من ShelterCostLedgerService (1521 → 1541) برصيد العاملة الفعلي.
 * التحصيل لا يُسجّل هنا؛ سند القبض الخاص بالطلب هو المصدر المحاسبي الوحيد له.
 */
class SponsorshipTransferTrialStartService
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
            throw new \RuntimeException(
                'إنشاء القيود المحاسبية التلقائية غير مفعّل من إعدادات الحسابات.'
            );
        }

        $transfer->loadMissing([
            'contract.maid.nationality',
            'contract.requiredMaidNationality',
            'newSponsor',
        ]);

        $date = ($transfer->trial_started_at ?? now())->toDateString();

        $activeYear = $this->yearService->checkDateRange($date);
        if (!$activeYear) {
            throw new \RuntimeException('لا يوجد سنة مالية نشطة لتاريخ بداية التجربة.');
        }

        $nationalityName = $this->resolveNationalityName($transfer);
        if ($nationalityName === '') {
            throw new \RuntimeException('لا يمكن إنشاء القيد المحاسبي لأن جنسية العاملة غير محددة.');
        }

        $newClientAccountId = (int) ($transfer->newSponsor?->account_id ?? 0);
        if (!$newClientAccountId) {
            throw new \RuntimeException('لا يوجد حساب محاسبي للكفيل الجديد.');
        }

        $contractValue = $this->roundMoney((float) $transfer->contract_value);

        $advancePaymentsAccountId = $this->resolveAdvancePaymentsAccountId($nationalityName);

        if (!$advancePaymentsAccountId) {
            throw new \RuntimeException('لا يوجد حساب دفعات مقدمة نقل خدمات مرتبط بالجنسية: ' . $nationalityName);
        }
        $statement = 'خروج العاملة للتجربة — نقل الخدمات #' . $transfer->id;
        $lines = [];

        // 1. تحميل قيمة العقد على العميل الجديد
        $this->appendLine($lines, $newClientAccountId, 'debtor', $contractValue, 'قيمة عقد نقل الخدمات');

        // 2. إيراد مؤجل في حساب الدفعات المقدمة نقل خدمات
        $this->appendLine($lines, $advancePaymentsAccountId, 'creditor', $contractValue, 'إيراد مؤجل لحن إتمام النقل');

        // 3. تكلفة الإيواء: قيد S3 مستقل يكتبه دفتر الإيواء (مدين إيواء التجربة / دائن أصل الإيواء برصيد العاملة).
        $this->shelterLedger->releaseToTrial($transfer, $date);

        if (empty($lines)) {
            throw new \RuntimeException(
                'لا توجد قيمة عقد أكبر من صفر لإنشاء قيد بداية التجربة.'
            );
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

        $transfer->updateQuietly(['trial_start_constraint_id' => $constraint->id]);

        return $constraint;
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
