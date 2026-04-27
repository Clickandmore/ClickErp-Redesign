<?php

namespace Nami\Hr\Rules;

use Closure;
use Nami\Finance\Models\Principle;

class CheckDeletePrinciple implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $principle_id = $value;
        $account = Principle::find($principle_id)->account;
        if ($account && $account->constraintDetails()->exists()) {
            $fail(__('finance.this_principle_has_account_that_has_constraints'));
        }
    }
}
