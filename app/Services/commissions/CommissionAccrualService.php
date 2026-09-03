<?php

namespace App\Services\commissions;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBatch;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class CommissionAccrualService
{
    private const EXPENSE_ACCOUNT_CODE = 5417;
    private const PAYABLE_ACCOUNT_CODE = 223;
    private const ALRAFEDAIN_EXPENSE_ACCOUNT_CODE = 52149;
    private const MARKETERS_PARENT_ACCOUNT_CODE = 144;

    public function __construct(
        private readonly ConstraintService $constraintService,
        private readonly YearService $yearService
    ) {}

    public function createForBatch(CommissionBatch $batch, int $adminId): Constraint
    {
        if ($batch->accrual_constraint_id) {
            throw ValidationException::withMessages([
                'accrual' => 'تم إنشاء قيد استحقاق لهذه الدفعة بالفعل.',
            ]);
        }

        $expenseAccount = $this->expenseAccount();
        $payableAccount = $this->payableAccountForMarketer((int) $batch->marketer_id);

        $date = $batch->approved_at?->toDateString() ?? now()->toDateString();
        $year = $this->yearService->checkDateRange($date);
        if (! $year) {
            throw ValidationException::withMessages([
                'accrual' => 'تاريخ اعتماد العمولة لا يقع داخل سنة مالية نشطة.',
            ]);
        }

        $amount = round((float) $batch->total_commission, 2);
        $marketerName = $batch->marketer?->name ?? ('#' . $batch->marketer_id);
        $statement = mb_substr(
            "استحقاق عمولة الموظف {$marketerName} - دفعة عمولات #{$batch->id}",
            0,
            500
        );
        $costCenterId = null;

        $data = [
            'date' => $date,
            'statement' => $statement,
            'value' => $amount,
            'created_by_id' => $adminId,
            'account_id' => [$expenseAccount->id, $payableAccount->id],
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [$amount, 0],
            'creditor_value' => [0, $amount],
            'statement_account' => [$statement, $statement],
            'cost_center_id' => [$costCenterId, null],
        ];

        $constraint = $this->constraintService->storeConstraint(
            $data,
            $year,
            ConstraintTypiesEnum::MarketerEntry
        );
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);

        $batch->update([
            'accrual_constraint_id' => $constraint->id,
            'expense_account_id' => $expenseAccount->id,
            'payable_account_id' => $payableAccount->id,
            'cost_center_id' => $costCenterId,
        ]);

        return $constraint->fresh('constraintDetails');
    }

    public function deleteForBatch(CommissionBatch $batch): void
    {
        if (! $batch->accrual_constraint_id) {
            return;
        }

        $constraint = Constraint::withTrashed()->find($batch->accrual_constraint_id);
        if (! $constraint || $constraint->trashed()) {
            return;
        }

        if ($constraint->bond()->exists()) {
            throw ValidationException::withMessages([
                'batch' => 'لا يمكن إرجاع الاعتماد لأن قيد الاستحقاق مرتبط بسند مالي.',
            ]);
        }

        $this->constraintService->deleteConstraint($constraint->id);
    }

    private function expenseAccount(): Account
    {
        $account = Account::query()
            ->whereIn('code', [self::ALRAFEDAIN_EXPENSE_ACCOUNT_CODE, self::EXPENSE_ACCOUNT_CODE])
            ->where('status', 'debtor')
            ->whereDoesntHave('children')
            ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [self::ALRAFEDAIN_EXPENSE_ACCOUNT_CODE])
            ->first();

        $account ??= Account::query()
            ->where('name', 'عمولات مسوقين')
            ->where('status', 'debtor')
            ->whereDoesntHave('children')
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'accrual' => 'تعذر اعتماد العمولة: حساب مصروف «عمولات مسوقين» غير موجود أو ليس حسابًا نهائيًا.',
            ]);
        }

        return $account;
    }

    public function payableAccountForMarketer(int $marketerId): Account
    {
        $marketer = Admin::withTrashed()->find($marketerId, ['id', 'name', 'commission_payable_account_id']);
        $marketerName = trim((string) $marketer?->name);
        $parent = Account::query()
            ->where(function ($query) {
                $query->where('code', self::MARKETERS_PARENT_ACCOUNT_CODE)
                    ->orWhere('name', 'المسوقين');
            })
            ->first();

        if ($marketer?->commission_payable_account_id) {
            $linkedAccount = Account::query()
                ->whereKey($marketer->commission_payable_account_id)
                ->where('parent_id', $parent?->id)
                ->where('status', 'creditor')
                ->whereDoesntHave('children')
                ->first();

            if (! $linkedAccount) {
                throw ValidationException::withMessages([
                    'accrual' => "تعذر اعتماد العمولة: الحساب المالي المرتبط بالمسوق «{$marketerName}» غير صالح أو ليس حسابًا نهائيًا تحت حساب المسوقين رقم "
                        . self::MARKETERS_PARENT_ACCOUNT_CODE . '.',
                ]);
            }

            return $linkedAccount;
        }

        $candidates = $parent && $marketerName !== ''
            ? Account::query()
                ->where('parent_id', $parent->id)
                ->where('status', 'creditor')
                ->whereDoesntHave('children')
                ->get()
            : collect();

        $account = $candidates
            ->first(fn (Account $candidate) => $this->normalizeName($candidate->name) === $this->normalizeName($marketerName));

        $account ??= Account::query()
            ->where('code', self::PAYABLE_ACCOUNT_CODE)
            ->where('type', AccountTypesEnum::Marketer->value)
            ->where('status', 'creditor')
            ->whereDoesntHave('children')
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'accrual' => "تعذر اعتماد العمولة: لا يوجد حساب مالي نهائي للمسوق «{$marketerName}» تحت حساب المسوقين رقم "
                    . self::MARKETERS_PARENT_ACCOUNT_CODE . '.',
            ]);
        }

        return $account;
    }

    private function normalizeName(?string $name): string
    {
        $name = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', trim((string) $name));
        $name = str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $name);

        return preg_replace('/[^\p{Arabic}\p{L}\p{N}]+/u', '', mb_strtolower($name));
    }
}
