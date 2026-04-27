<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\HasConstraintDetails;

class

BankAccountRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    protected function prepareForValidation()
    {
        if ($this->method() === 'DELETE') {
            $this->merge([
                'bank_account' => request()->route()->parameters()['bank_account'],
            ]);
        }
    }
    public function rules(): array
    {
        return match ($this->method()) {
            'POST' => $this->store(),
            'PUT', 'PATCH' => $this->update(),
            'DELETE' => $this->destroy(),
            default => $this->view()
        };

    }

    protected function store(): array
    {
        return [
            'name' => 'required',
            'bank_id' => 'required:exists:banks,id',
            'bank_number' => 'required|unique:bank_accounts,bank_number',
            'iban' => 'required|unique:bank_accounts,iban',
        ];
    }

    protected function update(): array
    {

        return [
            'name' => 'required',
            'bank_id' => 'required:exists:banks,id',
            'bank_number' => 'required|unique:bank_accounts,bank_number,'.$this->bank_account,
            'iban' => 'required|unique:bank_accounts,iban,'.$this->bank_account,
        ];
    }

    protected function destroy(): array
    {
        return [
            'bank_account' => [
                new HasConstraintDetails(request()->route()->parameters()['bank_account'])
            ]
        ];
    }

    protected function view(): array
    {
        return [];
    }
}
