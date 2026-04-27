<?php

namespace Nami\Hr\Rules;


use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Nami\Finance\Models\DepreciationPrinciple;

class OnlyOnePrincipleManualDeprecationInDay implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $principle_id = $value;
        $depreciationPrinciple = DepreciationPrinciple::query()
            ->where('principle_id', $principle_id)
            ->whereDate('created_at', '=', date('Y-m-d'))
            ->first();
        if ($depreciationPrinciple) {
            $fail(__('finance.you_cannot_depreciation_same_principle_in_day'));
        }


    }
}
