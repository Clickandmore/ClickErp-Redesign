<?php

namespace Nami\Finance\Rules;


use Illuminate\Contracts\Validation\Rule;
use Nami\Finance\Models\PrincipleWebsite;

class CheckDeletePrincipleWebiste implements Rule
{
    public function passes($attribute, $value): bool
    {
        $principle_website_id = $value;
        $principle_website = PrincipleWebsite::find($principle_website_id);

        return ! ($principle_website && $principle_website->principles()->exists());
    }

    public function message(): string
    {
        return __('finance.this_principle_website_has_principles');
    }
}
