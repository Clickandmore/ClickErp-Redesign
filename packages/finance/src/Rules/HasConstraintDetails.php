<?php

namespace Nami\Finance\Rules;


use Illuminate\Contracts\Validation\Rule;
use Nami\Finance\Models\BankAccount;
use Nami\Finance\Models\Box;
use Nami\Finance\Models\ConstraintDetails;

class HasConstraintDetails implements Rule
{
    protected string $message = 'constraint details found in the system.';

    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected ?int $account_id = null,
        protected ?int $box_id = null,
    ) {}

    public function passes($attribute, $value): bool
    {
        $query = ConstraintDetails::query();
        $box = null;
        $account_id = null;

        if ($this->account_id !== null) {
            $account_id = BankAccount::find($this->account_id)?->account_id;
            if ($account_id !== null) {
                $query->where('account_id', $account_id);
            }
        }

        if ($this->box_id !== null) {
            $box = Box::find($this->box_id)?->account_id;
            if ($box !== null) {
                $query->where('account_id', $box);
            }
        }

        if (! $query->exists()) {
            return true;
        }

        $this->message = $account_id ?? $box
            ? 'تم العثور على تفاصيل قيود للبنك المحدد'
            : 'constraint details found in the system.';

        return false;
    }

    public function message(): string
    {
        return $this->message;
    }
}
