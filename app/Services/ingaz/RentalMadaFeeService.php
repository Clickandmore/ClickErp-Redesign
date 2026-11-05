<?php

namespace App\Services\ingaz;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Services\TaxAccountResolver;
use Nami\Finance\Support\AccountCodeAliases;

class RentalMadaFeeService
{
    public function amounts(float $gross): array
    {
        $fee = round(round($gross, 2) * 0.008, 2);
        $tax = round($fee * 0.15, 2);
        return ['fee' => $fee, 'tax' => $tax, 'total' => round($fee + $tax, 2)];
    }

    /** Keep the customer's gross receipt intact; the bank alone bears the fee deduction. */
    public function post(Bond $bond): void
    {
        DB::transaction(function () use ($bond) {
            $amounts = $this->amounts((float) $bond->value);
            if ($amounts['total'] <= 0) {
                return;
            }
            $constraint = Constraint::query()->lockForUpdate()->findOrFail($bond->constraint_id);
            $statement = 'رسوم مدى — سند قبض تأجير #' . $bond->id;
            if ($constraint->constraintDetails()->where('statement', $statement)->exists()) {
                return;
            }
            $feeId = ConfirmSetting::query()->where('title', RentalContractFinanceService::RENTAL_MADA_FEE_ACCOUNT_SETTING)->latest('updated_at')->value('value');
            $taxId = ConfirmSetting::query()->where('title', RentalContractFinanceService::RENTAL_MADA_TAX_ACCOUNT_SETTING)->latest('updated_at')->value('value');
            $feeAccount = $feeId ? Account::query()->find($feeId) : Account::query()->where('code', AccountCodeAliases::current('52162'))->first();
            $taxAccount = $taxId ? Account::query()->find($taxId) : (app(TaxAccountResolver::class)->resolve('expense_tax_account')
                ?: Account::query()->where('code', AccountCodeAliases::current('1282'))->first());
            foreach ([$feeAccount, $taxAccount] as $account) {
                $status = $account?->status;
                $status = $status instanceof \BackedEnum ? $status->value : (string) $status;
                if (!$account || $status !== 'debtor' || $account->children()->exists()) {
                    throw new \RuntimeException('يلزم ضبط حساب رسوم نقاط بيع البنك الأهلي (52162) وحساب ضريبة المشتريات قبل تسجيل دفعة مدى. يجب أن يكونا حسابين مدينين نهائيين.');
                }
            }
            if (count(array_unique([(int) $feeAccount->id, (int) $taxAccount->id, (int) $bond->debit_account_id])) !== 3) {
                throw new \RuntimeException('حساب رسوم مدى وضريبتها وحساب الاستلام يجب أن تكون حسابات مختلفة.');
            }
            foreach ([[$feeAccount->id, $amounts['fee'], 0], [$taxAccount->id, $amounts['tax'], 0], [$bond->debit_account_id, 0, $amounts['total']]] as [$accountId, $debit, $credit]) {
                if ($debit == 0 && $credit == 0) {
                    continue;
                }
                $constraint->constraintDetails()->create([
                    'account_id' => $accountId, 'type' => $debit > 0 ? 'debtor' : 'creditor',
                    'debtor_value' => $debit, 'creditor_value' => $credit, 'statement' => $statement,
                    'date' => $constraint->date, 'year_id' => $constraint->year_id, 'branch_id' => $constraint->branch_id,
                ]);
            }
            $constraint->update(['value' => $constraint->constraintDetails()->sum('debtor_value')]);
        });
    }
}
