<?php

namespace Nami\Finance\Rules;


use Illuminate\Contracts\Validation\Rule;
use Nami\Finance\Models\DepreciationPrinciple;

class OnlyOnePrincipleManualDeprecationInDay implements Rule
{
    public function passes($attribute, $value): bool
    {
        $principle_id = $value;
        return ! DepreciationPrinciple::query()
            ->where('principle_id', $principle_id)
            ->whereDate('created_at', '=', date('Y-m-d'))
            ->first();
    }

    public function message(): string
    {
        return __('finance.you_cannot_depreciation_same_principle_in_day');
    }
}
