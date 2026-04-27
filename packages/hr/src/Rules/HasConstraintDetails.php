<?php

namespace Nami\Hr\Rules;


use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Nami\Finance\Models\BankAccount;
use Nami\Finance\Models\Box;
use Nami\Finance\Models\ConstraintDetails;

class HasConstraintDetails implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?int $account_id = null,
        protected ?int $box_id = null,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $query = ConstraintDetails::query();
        $box = null;
        $account_id = null;
        if ($this->account_id !== null) {
            $account_id = BankAccount::find($this->account_id)->account_id;
            $query->where('account_id', $account_id);
        }
        if ($this->box_id !== null) {
            $box = Box::find($this->box_id)->account_id;
            $query->where('account_id', $box);
        }
        if ($query->exists()) {
            $fail($account_id ?? $box
                ? 'تم العثور على تفاصيل قيود للبنك المحدد'
                : 'constraint details found in the system.'
            );
        }
    }
}
