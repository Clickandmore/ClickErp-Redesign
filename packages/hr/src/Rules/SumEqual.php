<?php

namespace Nami\Hr\Rules;

use Illuminate\Contracts\Validation\Rule;

class SumEqual implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $targetValue;
    protected $type;

    public function __construct($targetValue,$type)
    {
        $this->targetValue = $targetValue;
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
      if(request()->{$this->type} == 'monthly_installment'){
          return true;
      }

        $sum = array_sum($value);
//      dd($sum === $this->targetValue);
        return (int)$sum == (int)$this->targetValue;
    }
    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('hr.The sum of the amounts must equal total value.');
    }

}
