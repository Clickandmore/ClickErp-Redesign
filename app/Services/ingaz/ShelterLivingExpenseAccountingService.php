<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ShelterLivingExpense;
use App\Services\BranchAccessService;
use App\Models\ShelterLivingExpenseAllocation;
use App\Support\AutomaticJournalEntries;
use App\Support\ShelterAccountingSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class ShelterLivingExpenseAccountingService
{
    public const EXPENSE_ACCOUNT_SETTING = 'shelter_living_expense_account';

    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected ShelterCostLedgerService $shelterLedger
    ) {
    }

    public static function confirmSettingTitles(): array
    {
        return [self::EXPENSE_ACCOUNT_SETTING];
    }

    public function defaultExpenseAccountId(): ?int
    {
        $configuredId = (int) (ConfirmSetting::query()
            ->where('title', self::EXPENSE_ACCOUNT_SETTING)
            ->latest('updated_at')
            ->value('value') ?? 0);

        if ($configuredId > 0 && $this->findLeafAccount($configuredId)) {
            return $configuredId;
        }

        return Account::query()
            ->whereDoesntHave('children')
            ->whereIn('code', ['5450', '527'])
            ->orderByRaw("FIELD(code, '5450', '527')")
            ->value('id');
    }

    public function create(array $data): ShelterLivingExpense
    {
        return DB::transaction(function () use ($data) {
            $expenseAccount = $this->findLeafAccount((int) $data['expense_account_id']);
            $paymentAccount = $this->findLeafAccount((int) $data['payment_account_id']);

            if (!$expenseAccount || !$paymentAccount) {
                throw new \RuntimeException('يجب اختيار حسابات محاسبية نهائية صحيحة للإعاشة والصرف.');
            }

            $paymentSourceType = (string) ($data['payment_source_type'] ?? 'bank');
            if (!$this->isAllowedPaymentAccount($paymentAccount, $paymentSourceType)) {
                throw new \RuntimeException(
                    $paymentSourceType === 'custody'
                        ? 'يجب اختيار عهدة نشطة مرتبطة بموظف.'
                        : 'حساب الصرف يجب أن يكون حساب بنك أو صندوق.'
                );
            }

            $contracts = $this->eligibleContracts($data['shelter_id'] ?? null, $data['contract_id'] ?? null)->get();
            if ($contracts->isEmpty()) {
                throw new \RuntimeException('لا توجد عاملات مطابقة داخل الإيواء لتسجيل مصروف الإعاشة.');
            }

            $totalAmount = $this->roundMoney((float) $data['total_amount']);
            if ($totalAmount <= 0) {
                throw new \RuntimeException('قيمة مصروف الإعاشة يجب أن تكون أكبر من صفر.');
            }

            $this->assertNotDuplicateSubmission($data, $totalAmount);
            $capitalize = ShelterAccountingSettings::livingExpenseCapitalized();

            $workerCount = $contracts->count();
            $allocations = $this->splitAmount($totalAmount, $workerCount);
            $entryDate = (string) $data['expense_date'];
            $automaticEntriesEnabled = AutomaticJournalEntries::enabled();
            $activeYear = $automaticEntriesEnabled ? $this->resolveYear($entryDate) : null;
            $expenseShelterId = $data['shelter_id'] ?? ($workerCount === 1 ? $contracts->first()?->info?->shelter_id : null);

            $expense = ShelterLivingExpense::query()->create([
                'shelter_id' => $expenseShelterId,
                'expense_account_id' => $expenseAccount->id,
                'payment_account_id' => $paymentAccount->id,
                'payment_source_type' => $paymentSourceType,
                'expense_date' => $entryDate,
                'title' => $data['title'] ?: 'إعاشة الإيواء',
                'total_amount' => $totalAmount,
                'worker_count' => $workerCount,
                'amount_per_worker' => $this->roundMoney($totalAmount / $workerCount),
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::guard('admin')->id() ?: Auth::id(),
            ]);

            $lines = [];
            $createdAllocations = [];
            foreach ($contracts as $index => $contract) {
                $amount = $allocations[$index] ?? 0;
                $costCenter = $this->shelterLedger->ensureMaidCostCenter($contract);
                $statement = $this->allocationStatement($expense, $contract);

                $allocation = $expense->allocations()->create([
                    'contract_id' => $contract->id,
                    'maid_id' => $contract->maid_id,
                    'cost_center_id' => $costCenter->id,
                    'amount' => $amount,
                ]);
                $createdAllocations[] = [$contract, $allocation, $amount];

                // عند الرسملة يُدان أصل الإيواء لجنسية العاملة (S2) بدل حساب مصروف الإعاشة،
                // فيحمل رصيدها كل ما أُنفق عليها ويُفرج عنه عند الخروج.
                $debitAccountId = $capitalize
                    ? $this->shelterLedger->assetAccountIdForContract($contract)
                    : $expenseAccount->id;

                $lines[] = [
                    'account_id' => $debitAccountId,
                    'account_type' => 'debtor',
                    'debtor_value' => $amount,
                    'creditor_value' => 0,
                    'statement' => $statement,
                    'cost_center_id' => $costCenter->id,
                ];
            }

            $lines[] = [
                'account_id' => $paymentAccount->id,
                'account_type' => 'creditor',
                'debtor_value' => 0,
                'creditor_value' => $totalAmount,
                'statement' => $this->constraintStatement($expense),
                'cost_center_id' => null,
            ];

            $constraint = $automaticEntriesEnabled
                ? $this->storeConstraint($lines, $this->constraintStatement($expense), $entryDate, $totalAmount, $activeYear)
                : null;
            $expense->update(['constraint_id' => $constraint?->id]);

            if ($capitalize) {
                foreach ($createdAllocations as [$contract, $allocation, $amount]) {
                    $this->shelterLedger->recordLivingExpenseAllocation($contract, (float) $amount, $constraint, $allocation);
                }
            }

            return $expense->fresh(['constraint']);
        });
    }

    /**
     * عكس مصروف إعاشة بقيد مقابل (لا حذف): دائن حسابات المدين الأصلية / مدين حساب الصرف،
     * مع إفراج مقابل في دفتر الإيواء إن كان المصروف مرسملاً.
     */
    public function reverse(ShelterLivingExpense $expense, ?string $reason = null): ?Constraint
    {
        return DB::transaction(function () use ($expense, $reason) {
            $expense = ShelterLivingExpense::query()->lockForUpdate()->findOrFail($expense->id);

            if (Schema::hasColumn('shelter_living_expenses', 'reversed_by_constraint_id') && $expense->reversed_by_constraint_id) {
                return Constraint::query()->find($expense->reversed_by_constraint_id);
            }

            $original = $expense->constraint_id ? Constraint::query()->with('constraintDetails')->find($expense->constraint_id) : null;
            $reversal = null;

            if ($original && AutomaticJournalEntries::enabled()) {
                $statement = 'عكس ' . $this->constraintStatement($expense) . ($reason ? ' — ' . $reason : '');
                $lines = $original->constraintDetails
                    ->map(fn ($detail) => [
                        'account_id' => (int) $detail->account_id,
                        'account_type' => (float) $detail->creditor_value > 0 ? 'debtor' : 'creditor',
                        'debtor_value' => $this->roundMoney((float) $detail->creditor_value),
                        'creditor_value' => $this->roundMoney((float) $detail->debtor_value),
                        'statement' => $statement,
                        'cost_center_id' => $detail->cost_center_id,
                    ])
                    ->sortByDesc('debtor_value')
                    ->values()
                    ->all();

                $reversal = $this->storeConstraint(
                    $lines,
                    $statement,
                    now()->toDateString(),
                    (float) $expense->total_amount,
                    $this->resolveYear(now()->toDateString())
                );
            }

            $expense->loadMissing('allocations.contract');
            foreach ($expense->allocations as $allocation) {
                if ($allocation->contract && $this->shelterLedger->findEntry($allocation, \App\Models\ShelterCostEntry::KIND_LIVING_EXPENSE)) {
                    $this->shelterLedger->recordLivingExpenseReversal($allocation->contract, (float) $allocation->amount, $reversal, $allocation);
                }
            }

            if (Schema::hasColumn('shelter_living_expenses', 'reversed_by_constraint_id')) {
                $expense->forceFill([
                    'reversed_by_constraint_id' => $reversal?->id,
                    'reversed_at' => now(),
                ])->save();
            }

            return $reversal;
        });
    }

    /**
     * حماية من الإرسال المزدوج: نفس مكان الإيواء والتاريخ والمبلغ من نفس المستخدم خلال دقيقة.
     */
    private function assertNotDuplicateSubmission(array $data, float $totalAmount): void
    {
        $duplicate = ShelterLivingExpense::query()
            ->where('expense_date', (string) $data['expense_date'])
            ->where('total_amount', $totalAmount)
            ->where('created_by', Auth::guard('admin')->id() ?: Auth::id())
            ->when($data['shelter_id'] ?? null, fn ($q, $shelterId) => $q->where('shelter_id', $shelterId))
            ->where('created_at', '>=', now()->subMinute())
            ->exists();

        if ($duplicate) {
            throw new \RuntimeException('تم تسجيل مصروف إعاشة مطابق قبل أقل من دقيقة. تحقق من القائمة قبل إعادة الإرسال.');
        }
    }

    private function eligibleContracts(?int $shelterId, ?int $contractId = null)
    {
        $query = Contract::query()
            ->where('status', ContractStatusEnum::Shelter->value)
            ->when($contractId, function ($query) use ($contractId) {
                $query->whereKey($contractId);
            })
            ->whereHas('info', function ($query) use ($shelterId) {
                $query->where(function ($insideQuery) {
                    $insideQuery->where('is_outside_shelter', false)
                        ->orWhereNull('is_outside_shelter');
                });

                if ($shelterId) {
                    $query->where('shelter_id', $shelterId);
                }
            })
            ->whereNotNull('maid_id')
            ->whereHas('maid')
            ->with(['maid', 'info.shelter.translations'])
            ->orderBy('id');

        return app(BranchAccessService::class)->applyToQuery(
            $query,
            request(),
            'contracts'
        );
    }

    private function splitAmount(float $totalAmount, int $count): array
    {
        $base = floor(($totalAmount / $count) * 100) / 100;
        $amounts = array_fill(0, $count, $base);
        $remainingHalalas = (int) round(($totalAmount - ($base * $count)) * 100);

        for ($i = 0; $i < $remainingHalalas; $i++) {
            $amounts[$i] = $this->roundMoney($amounts[$i] + 0.01);
        }

        return $amounts;
    }




    private function resolveYear(string $date)
    {
        $activeYear = $this->yearService->checkDateRange($date);
        if ($activeYear) {
            return $activeYear;
        }

        $activeYear = $this->yearService->getActiveYear();
        if (!$activeYear) {
            throw new \RuntimeException('لا توجد سنة مالية نشطة لإنشاء قيد الإعاشة.');
        }

        return $activeYear;
    }

    private function storeConstraint(array $lines, string $statement, string $date, float $value, $activeYear): ?Constraint
    {
        $data = [
            'code' => $this->constraintService->lastCode(),
            'date' => $date,
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'value' => $this->roundMoney($value),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ];

        $constraint = $this->constraintService->storeConstraint($data, $activeYear);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);

        return $constraint;
    }

    private function findLeafAccount(int $accountId): ?Account
    {
        if ($accountId <= 0) {
            return null;
        }

        return Account::query()
            ->whereKey($accountId)
            ->whereDoesntHave('children')
            ->first();
    }

    private function isAllowedPaymentAccount(Account $account, string $sourceType): bool
    {
        if ($sourceType === 'custody') {
            $isCustodyChartAccount = $account->parent()
                ->where(function ($query) {
                    $query
                        ->where('type', AccountTypesEnum::Custody->value)
                        ->orWhere('code', 126);
                })
                ->exists();

            if ($isCustodyChartAccount) {
                return true;
            }

            return Schema::hasTable('hr_employee_custodies')
                && DB::table('hr_employee_custodies')
                ->where('account_id', $account->id)
                ->where('status', 'active')
                ->exists();
        }

        if ($sourceType !== 'bank') {
            return false;
        }

        $type = $account->type;
        $typeValue = $type instanceof AccountTypesEnum ? $type->value : (int) $type;

        return in_array($typeValue, [
            AccountTypesEnum::Bank->value,
            AccountTypesEnum::Box->value,
        ], true);
    }

    private function constraintStatement(ShelterLivingExpense $expense): string
    {
        return $expense->title . ' بتاريخ ' . $expense->expense_date->format('Y-m-d')
            . ' - توزيع على ' . $expense->worker_count . ' عاملة';
    }

    private function allocationStatement(ShelterLivingExpense $expense, Contract $contract): string
    {
        $maidName = trim((string) ($contract->maid?->name ?? ''));

        return $expense->title . ' / عقد #' . $contract->id
            . ($maidName !== '' ? ' / ' . $maidName : '');
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
