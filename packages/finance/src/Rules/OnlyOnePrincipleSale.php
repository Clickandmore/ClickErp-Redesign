<?php

namespace Nami\Finance\Rules;



use Closure;
use Illuminate\Contracts\Validation\Rule;
use Nami\Finance\Models\PrincipleSale;

class OnlyOnePrincipleSale implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $principle_id = $value;

        $principle = PrincipleSale::where('principle_id', $principle_id)->first();

        return !$principle;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('finance::finance.this_principle_is_sold');
    }
}
