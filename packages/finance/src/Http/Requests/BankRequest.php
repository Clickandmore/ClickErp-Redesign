<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class BankRequest extends MainRequest
{
    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            default => [],
        };
    }

    protected function store(): array
    {
        return array_merge($this->accountRules(), [
            'name' => ['required', 'string', 'max:255'],
        ]);
    }

    protected function update(): array
    {
        return array_merge($this->accountRules(), [
            'name' => ['required', 'string', 'max:255'],
            'accounts.*.id' => ['nullable', 'integer', 'exists:fn_accounts,id'],
        ]);
    }

    private function accountRules(): array
    {
        return [
            'accounts' => ['required', 'array', 'min:1'],
            'accounts.*.name' => ['required', 'string', 'max:255', 'distinct'],
            'accounts.*.bank_number' => ['nullable', 'string', 'max:255', 'distinct'],
            'accounts.*.iban' => ['nullable', 'string', 'max:50', 'distinct'],
        ];
    }
}
