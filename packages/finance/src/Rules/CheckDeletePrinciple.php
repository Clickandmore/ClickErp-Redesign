<?php

namespace Nami\Finance\Rules;

use Illuminate\Contracts\Validation\Rule;
use Nami\Finance\Models\Principle;

class CheckDeletePrinciple implements Rule
{
    public function passes($attribute, $value): bool
    {
        $principle_id = $value;
        $account = Principle::find($principle_id)?->account;

        return ! ($account && $account->constraintDetails()->exists());
    }

    public function message(): string
    {
        return __('finance.this_principle_has_account_that_has_constraints');
    }
}
