<?php

namespace Nami\Finance\Rules;

use Illuminate\Contracts\Validation\Rule;

class ChronologicalDates implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $type;

    public function __construct($type = null)
    {
        $this->type = $type;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if(isset($this->type) && request()->{$this->type} == 'monthly_installment'){
            return true;
        }
        // Convert all date strings to DateTime objects
        $dates = array_map(function ($date) {
            return new \DateTime($date);
        }, $value);

        // Check chronological order
        for ($i = 0; $i < count($dates); $i++) {
            if (isset($dates[$i+1]) && $dates[$i+1]->getTimestamp() <= $dates[$i]->getTimestamp()) {
                return false;
            }
        }

        return true;
    }
    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('hr.check chronological dates');
    }
}
