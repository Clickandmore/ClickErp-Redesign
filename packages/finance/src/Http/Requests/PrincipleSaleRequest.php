<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\OnlyOnePrincipleSale;

class PrincipleSaleRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */


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
            'principle_id' => ['required', 'exists:fn_principles,id',new OnlyOnePrincipleSale()],
            'principle_value' => 'required|numeric|min:0',
            'sales_value' => 'required|numeric|min:0',
            'date' => 'required|date',
            'tax_setting_id' => 'nullable|exists:fn_tax_settings,id',
            'tax_setting_value' => 'nullable|numeric|min:0',
            'total_tax_value' => 'nullable|numeric|min:0',
            'tax_account_id' => 'nullable|exists:fn_accounts,id',
            'debit_account_id' => 'required|exists:fn_accounts,id',
            'financial_value' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    protected function update(): array
    {
        return [
            'principle_id' => 'required|exists:fn_principles,id',
            'principle_value' => 'required|numeric|min:0',
            'sales_value' => 'required|numeric|min:0',
            'date' => 'required|date',
            'tax_setting_id' => 'nullable|exists:fn_tax_settings,id',
            'tax_setting_value' => 'nullable|numeric|min:0',
            'total_tax_value' => 'nullable|numeric|min:0',
            'tax_account_id' => 'nullable|exists:fn_accounts,id',
            'debit_account_id' => 'required|exists:fn_accounts,id',
            'financial_value' => 'required|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    protected function destroy(): array
    {
        return [];
    }

    protected function view(): array
    {
        return [];
    }
}
