<?php

namespace Nami\Finance\Rules;

use Illuminate\Contracts\Validation\Rule;
use Nami\Finance\Models\BankAccount;

class HasBankAccount implements Rule
{
    protected string $message = 'This bank cannot be deleted because it has branches.';

    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?int $bankId = null
    ) {}

    public function passes($attribute, $value): bool
    {
        $query = BankAccount::query();

        if ($this->bankId !== null) {
            $query->where('bank_id', $this->bankId);
        }

        if (! $query->exists()) {
            return true;
        }

        $this->message = $this->bankId
            ? 'هذا البنك له فروع غير مسموح بالحذف .'
            : 'This bank cannot be deleted because it has branches.';

        return false;
    }

    public function message(): string
    {
        return $this->message;
    }
}
