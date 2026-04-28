<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ShelterLivingExpense;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        protected YearService $yearService
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

            if (!$this->isPaymentAccount($paymentAccount)) {
                throw new \RuntimeException('حساب الصرف يجب أن يكون حساب بنك أو صندوق.');
            }

            $contracts = $this->eligibleContracts($data['shelter_id'] ?? null, $data['contract_id'] ?? null)->get();
            if ($contracts->isEmpty()) {
                throw new \RuntimeException('لا توجد عاملات مطابقة داخل الإيواء لتسجيل مصروف الإعاشة.');
            }

            $totalAmount = $this->roundMoney((float) $data['total_amount']);
            if ($totalAmount <= 0) {
                throw new \RuntimeException('قيمة مصروف الإعاشة يجب أن تكون أكبر من صفر.');
            }

            $workerCount = $contracts->count();
            $allocations = $this->splitAmount($totalAmount, $workerCount);
            $entryDate = (string) $data['expense_date'];
            $activeYear = $this->resolveYear($entryDate);
            $expenseShelterId = $data['shelter_id'] ?? ($workerCount === 1 ? $contracts->first()?->info?->shelter_id : null);

            $expense = ShelterLivingExpense::query()->create([
                'shelter_id' => $expenseShelterId,
                'expense_account_id' => $expenseAccount->id,
                'payment_account_id' => $paymentAccount->id,
                'expense_date' => $entryDate,
                'title' => $data['title'] ?: 'إعاشة الإيواء',
                'total_amount' => $totalAmount,
                'worker_count' => $workerCount,
                'amount_per_worker' => $this->roundMoney($totalAmount / $workerCount),
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::guard('admin')->id() ?: Auth::id(),
            ]);

            $lines = [];
            foreach ($contracts as $index => $contract) {
                $amount = $allocations[$index] ?? 0;
                $costCenter = $this->ensureMaidCostCenter($contract);
                $statement = $this->allocationStatement($expense, $contract);

                $expense->allocations()->create([
                    'contract_id' => $contract->id,
                    'maid_id' => $contract->maid_id,
                    'cost_center_id' => $costCenter->id,
                    'amount' => $amount,
                ]);

                $lines[] = [
                    'account_id' => $expenseAccount->id,
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

            $constraint = $this->storeConstraint($lines, $this->constraintStatement($expense), $entryDate, $totalAmount, $activeYear);
            $expense->update(['constraint_id' => $constraint?->id]);

            return $expense->fresh(['constraint']);
        });
    }

    private function eligibleContracts(?int $shelterId, ?int $contractId = null)
    {
        return Contract::query()
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
            ->with(['maid', 'info.shelter.translations'])
            ->orderBy('id');
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

    private function ensureMaidCostCenter(Contract $contract): CostCenter
    {
        $root = $this->ensureShelterRootCostCenter();
        $maidId = (int) $contract->maid_id;

        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->where('parent_id', $root->id)
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', $maidId)
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        $title = 'إيواء عاملة #' . $maidId;
        $maidName = trim((string) ($contract->maid?->name ?? ''));
        if ($maidName !== '') {
            $title .= ' - ' . $maidName;
        }

        return CostCenter::query()->create([
            'title' => $title,
            'parent_id' => $root->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => $maidId,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function ensureShelterRootCostCenter(): CostCenter
    {
        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->whereNull('parent_id')
            ->where('title', 'عاملات الإيواء')
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        return CostCenter::query()->create([
            'title' => 'عاملات الإيواء',
            'parent_id' => null,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function nextCostCenterCode(): string
    {
        return (string) (((int) CostCenter::query()->lockForUpdate()->max('code')) + 1);
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

    private function isPaymentAccount(Account $account): bool
    {
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
