<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class ClientRequest extends MainRequest
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
            'is_company' => 'required|in:0,1',
            'name' => 'required|unique:fn_clients,name',
            'client_category_id' => 'required|exists:fn_client_categories,id',
            'phone' => 'required',
            'address' => 'nullable',
            'email' => 'required|email',
            'tax_number' => 'required_if:is_company,1|nullable|regex:/^3\d{13}3$/',
            'city' => 'required_if:is_company,1|nullable',
            'postal_zone' => 'required_if:is_company,1|nullable',
            'days_credit_period' => 'nullable|numeric',
            'credit_limit' => 'required|numeric',
            'code' => 'nullable',
            'status' => 'nullable',
            'exceeding_minimum_cash_limit' => 'nullable',
            'exceeding_minimum_days_limit' => 'nullable',
            'account_id' => 'nullable|exists:accounts,id',
            'image' => 'nullable',
            'images' => 'nullable|array',
            "file"=>'nullable|array',

        ];
    }

    protected function update(): array
    {
        return [
            'is_company' => 'required|in:0,1',
            'name' => 'required|unique:fn_clients,name,' . $this->route('client'),
            'client_category_id' => 'required|exists:fn_client_categories,id',
            'phone' => 'required',
            'address' => 'nullable',
            'email' => 'required|email',
            'tax_number' => 'required_if:is_company,1|nullable|regex:/^3\d{13}3$/',
            'city' => 'required_if:is_company,1|nullable',
            'postal_zone' => 'required_if:is_company,1|nullable',
            'days_credit_period' => 'required|numeric',
            'credit_limit' => 'required|numeric',
//            'days_number' => 'nullable|integer',
            'exceeding_minimum_cash_limit' => 'nullable',
            'exceeding_minimum_days_limit' => 'nullable',
            'code' => 'nullable',
            'status' => 'nullable',
            'account_id' => 'nullable|exists:accounts,id',
            'image' => 'nullable',
            'images' => 'nullable|array',
            "attachments"=>'nullable|array',

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

    public function messages(): array
    {
        return [
            'is_company.required' => __('finance.is_company_required'),
            'is_company.in' => __('finance.is_company_in'),
            'name.required' => __('finance.name_required'),
            'client_category_id.required' => __('finance.client_category_id_required'),
            'client_category_id.exists' => __('finance.client_category_id_exists'),
            'phone.required' => __('finance.phone_required'),
            'address.required' => __('finance.address_required'),
            'email.required' => __('finance.email_required'),
            'email.email' => __('finance.email_email'),
            'tax_number.required_if' => __('finance.tax_number_required_if'),
            'tax_number.regex' => __('finance.tax_number_regex'),
            'city.required_if' => __('finance.city_required_if'),
            'postal_zone.required_if' => __('finance.postal_zone_required_if'),
            'days_credit_period.required' => __('finance.days_credit_period_required'),
            'days_credit_period.numeric' => __('finance.days_credit_period_numeric'),
            'credit_limit.numeric' => __('finance.credit_limit_numeric'),
            'account_id.exists' => __('finance.account_id_exists'),
            'exceeding_minimum_cash_limit.boolean' => __('finance.exceeding_minimum_cash_limit_boolean'),
            'exceeding_minimum_days_limit.boolean' => __('finance.exceeding_minimum_days_limit_boolean'),
        ];
    }
}
