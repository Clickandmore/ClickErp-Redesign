<?php

namespace App\Services\commissions;

use App\Models\commissions\CommissionBatch;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class CommissionAccrualService
{
    private const EXPENSE_ACCOUNT_CODE = 5417;
    private const PAYABLE_ACCOUNT_CODE = 223;

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

        $expenseAccount = $this->accountByCode(
            self::EXPENSE_ACCOUNT_CODE,
            'حساب عمولات المسوقين'
        );
        $payableAccount = $this->accountByCode(
            self::PAYABLE_ACCOUNT_CODE,
            'حساب مصروفات عمولات المسوقين المستحقة'
        );

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

    private function accountByCode(int $code, string $label): Account
    {
        $account = Account::query()
            ->where('code', $code)
            ->whereDoesntHave('children')
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'accrual' => "تعذر إنشاء قيد الاستحقاق تلقائيًا: {$label} رقم {$code} غير موجود أو ليس حسابًا نهائيًا.",
            ]);
        }

        return $account;
    }
}
