<?php

namespace Nami\Finance\Rules;

use Illuminate\Contracts\Validation\Rule;

class CommaSeparatedNumeric implements Rule
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
        // Split the string by commas and trim any extra spaces
        $items = array_map('trim', explode(',', $value));

        // Check if all items are numeric
        return array_reduce($items, function ($carry, $item) {
            return $carry && is_numeric($item);
        }, true);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'All items must be numeric.';
    }
}
