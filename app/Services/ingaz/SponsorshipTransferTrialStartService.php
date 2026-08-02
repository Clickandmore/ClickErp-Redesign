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
 * القيد #15: خروج العاملة للتجربة — نقل الخدمات
 *
 * يُسجّل عند تسليم العاملة للكفيل الجديد وبداية فترة التجربة.
 * - يُثبت قيمة العقد كإيراد مؤجل في حساب الدفعات المقدمة (2291).
 * - ينقل تكلفة الإيواء من حساب إيواء الجنسية (1521) إلى إيواء التجربة (1541).
 * - يُثبت الدفعة المقدمة المحصّلة من البنك وخصمها من ذمة العميل الجديد.
 */
class SponsorshipTransferTrialStartService
{
    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService
    ) {}

    public function record(SponsorshipTransfer $transfer): ?Constraint
    {
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
        $paidAmount    = $this->roundMoney((float) $transfer->paid_amount);
        $housingCost   = $this->resolveHousingCost($transfer);

        $advancePaymentsAccountId = $this->resolveAdvancePaymentsAccountId($nationalityName);
        $trialHousingAccountId    = $this->resolveTrialHousingAccountId();
        $mainHousingAccountId     = $this->resolveMainHousingAccountId($nationalityName);
        $bankAccountId            = $this->resolveBankAccountId();

        if (!$advancePaymentsAccountId) {
            throw new \RuntimeException('لا يوجد حساب دفعات مقدمة نقل خدمات مرتبط بالجنسية: ' . $nationalityName);
        }
        if (!$trialHousingAccountId) {
            throw new \RuntimeException('لا يوجد حساب مصروفات مدفوعة مقدماً - إيواء التجربة.');
        }
        if (!$mainHousingAccountId) {
            throw new \RuntimeException('لا يوجد حساب مصروفات مدفوعة مقدماً - إيواء مرتبط بالجنسية: ' . $nationalityName);
        }
        if (!$bankAccountId) {
            throw new \RuntimeException('لا يوجد حساب بنكي رئيسي في النظام.');
        }

        $statement = 'خروج العاملة للتجربة — نقل الخدمات #' . $transfer->id;
        $lines = [];

        // 1. تحميل قيمة العقد على العميل الجديد
        $this->appendLine($lines, $newClientAccountId, 'debtor', $contractValue, 'قيمة عقد نقل الخدمات');

        // 2. إيراد مؤجل في حساب الدفعات المقدمة نقل خدمات
        $this->appendLine($lines, $advancePaymentsAccountId, 'creditor', $contractValue, 'إيراد مؤجل لحن إتمام النقل');

        // 3. نقل تكلفة الإيواء إلى حساب إيواء التجربة
        if ($housingCost > 0) {
            $this->appendLine($lines, $trialHousingAccountId, 'debtor', $housingCost, 'نقل تكلفة الإيواء إلى التجربة');
            $this->appendLine($lines, $mainHousingAccountId, 'creditor', $housingCost, 'تخفيض أصل الإيواء');
        }

        // 4. تسجيل الدفعة المقدمة المحصّلة من البنك
        if ($paidAmount > 0) {
            $this->appendLine($lines, $bankAccountId, 'debtor', $paidAmount, 'وارد دفعة مقدمة من العميل الجديد');
            $this->appendLine($lines, $newClientAccountId, 'creditor', $paidAmount, 'سداد دفعة مقدمة');
        }

        if (empty($lines)) {
            throw new \RuntimeException(
                'لا توجد قيمة عقد أو دفعة محصلة أو تكلفة إيواء أكبر من صفر لإنشاء قيد بداية التجربة.'
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

    private function resolveMainHousingAccountId(string $nationalityName): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'مصروفات مدفوعة مقدما - الايواء - ' . $nationalityName,
                'مصروفات مدفوعة مقدما - الايواء (' . $nationalityName . ')',
                'مصروفات مدفوعة مقدماً - إيواء ' . $nationalityName,
            ],
            ['مصروفات مدفوعة مقدما - الايواء', 'إيواء ' . $nationalityName],
            fn ($q) => $q->where(function ($inner) use ($nationalityName) {
                $inner->where('name', 'like', '%ايواء%' )
                      ->orWhere('name', 'like', '%إيواء%');
            })->where('name', 'like', '%' . $nationalityName . '%')
        );
    }

    private function resolveBankAccountId(): ?int
    {
        return $this->resolveAccountByCandidates(
            ['البنك الأهلي رئيسي', 'البنك الرئيسي', 'بنك'],
            [],
            fn ($q) => $q->where('status', 'debtor')
                         ->where(function ($inner) {
                             $inner->where('name', 'like', '%بنك%')
                                   ->orWhere('name', 'like', '%bank%');
                         })->whereDoesntHave('children')
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
