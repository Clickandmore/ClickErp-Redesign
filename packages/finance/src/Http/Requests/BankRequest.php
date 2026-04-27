<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\HasBankAccount;
use Illuminate\Validation\Rule;

class BankRequest extends MainRequest
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
                'bank' => $this->route('bank'),
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
            'name:ar' => [
                'required',
                Rule::unique('bank_translations', 'name')->where(function ($query) {
                    return $query->where('locale', 'ar');
                })
            ],
            'name:en' => [
                'nullable',
                Rule::unique('bank_translations', 'name')->where(function ($query) {
                    return $query->where('locale', 'en');
                })
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'accounts' => 'required|array|min:1',
            'accounts.*.bank_number' => [
                'required',
                'distinct',
                Rule::unique('bank_accounts', 'bank_number')
            ],
            'accounts.*.iban' => [
                'required',
                'distinct',
                Rule::unique('bank_accounts', 'iban')
            ],
        ];
    }

    protected function update(): array
    {
        return [
            'name:ar' => 'required',
            'name:en' => 'nullable',
            'image' => 'nullable',
//            'accounts.*.bank_number' => 'required|unique:bank_accounts,bank_number',
//            'accounts.*.iban' => 'required|unique:bank_accounts,iban',
            'account_id' => 'nullable',
        ];
    }

    protected function destroy(): array
    {
        return [
            'bank' => [
                'required',
                new HasBankAccount($this->bank)
            ]
        ];
    }

    protected function view(): array
    {
        return [];
    }
}
