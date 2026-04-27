<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\CheckDeletePrinciple;

class PrincipleRequest extends MainRequest
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
            'parent_account_id' => 'required|exists:fn_accounts,id',
            'registration_type' => 'required',
            'code' => 'nullable',
            'name' => 'required|unique:fn_principles,name',
            'purchase_date' => 'required',
            'purchase_value' => 'required',
            'principle_value' => 'required',
            'service_start_date' => 'required',
            'principle_website_id' => 'nullable|exists:fn_principle_websites,id',
            'employee_id' => 'nullable|exists:hr_employee,id',
            'creditor_account_id' => 'nullable|exists:fn_accounts,id',
            'first_tax' => 'nullable|exists:fn_tax_settings,id',
            'second_tax' => 'nullable|exists:fn_tax_settings,id',
            'hypothetical_years' => 'nullable',
            'perished_type' => 'required',
            'junk_value' => 'nullable',
            'perished_value' => 'nullable',
            'perished_account_id' => 'nullable|exists:fn_accounts,id',
            'expense_account_id' => 'nullable|exists:fn_accounts,id',
            'account_type' => 'nullable',
            'perished_end_date' => 'nullable',
            'period' => 'nullable',
            'description' => 'nullable',
            'image' => 'nullable',
            'file' => 'nullable|array',
        ];
    }

    protected function update(): array
    {
        return [
            'parent_account_id' => 'nullable|exists:fn_accounts,id',
            'registration_type' => 'required',
            'code' => 'nullable',
            'name' => 'required|unique:fn_principles,name,' . $this->route('principle') . ',id',
            'purchase_date' => 'required',
            'purchase_value' => 'required',
            'principle_value' => 'required',
            'service_start_date' => 'required',
            'principle_website_id' => 'nullable|exists:fn_principle_websites,id',
            'employee_id' => 'nullable|exists:hr_employee,id',
            'creditor_account_id' => 'nullable|exists:fn_accounts,id',
            'first_tax' => 'nullable|exists:fn_tax_settings,id',
            'second_tax' => 'nullable|exists:fn_tax_settings,id',
            'hypothetical_years' => 'nullable',
            'perished_type' => 'required',
            'junk_value' => 'nullable',
            'perished_value' => 'nullable',
            'perished_account_id' => 'nullable|exists:fn_accounts,id',
            'expense_account_id' => 'nullable|exists:fn_accounts,id',
            'account_type' => 'nullable',
            'perished_end_date' => 'nullable',
            'period' => 'nullable',
            'description' => 'nullable',
            'file' => 'nullable|array',
            'image' => 'nullable',
        ];
    }

    protected function destroy(): array
    {
        return [
            'principle_id' => [new CheckDeletePrinciple()],
        ];
    }
    public function validationData()
    {
        return array_merge($this->all(), [
            'principle_id' => $this->route('principle'),
        ]);
    }

    protected function view(): array
    {
        return [];
    }
}
