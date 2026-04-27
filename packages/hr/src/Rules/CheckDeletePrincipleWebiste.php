<?php

namespace Nami\Hr\Rules;


use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Nami\Finance\Models\PrincipleWebsite;

class CheckDeletePrincipleWebiste implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $principle_website_id = $value;
        $principle_website = PrincipleWebsite::find($principle_website_id);
        if ($principle_website && $principle_website->principles()->exists()) {
            $fail(__('finance.this_principle_website_has_principles'));
        }
    }
}
