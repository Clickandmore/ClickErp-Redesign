<?php

namespace Nami\Hr\Rules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Nami\Finance\Models\BankAccount;

class HasBankAccount implements ValidationRule
{

    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?int $bankId = null
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = BankAccount::query();


        if ($this->bankId !== null) {
            $query->where('bank_id', $this->bankId);
        }
        if ($query->exists()) {
            $fail($this->bankId
                ? 'هذا البنك له فروع غير مسموح بالحذف .'
                : 'This bank cannot be deleted because it has branches.'
            );
        }
    }
}
