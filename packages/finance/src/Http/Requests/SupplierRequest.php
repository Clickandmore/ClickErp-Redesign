<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Models\SupplierType;

class SupplierRequest extends MainRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'supplier_type_name' => trim((string) $this->input('supplier_type_name', '')),
        ]);
    }

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
            'name' => 'required|unique:fn_suppliers,name',
            'code' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string|email',
            'tax_number' => 'nullable|integer',
            'tax_image' => 'nullable|image',
            'commercial_register_number' => 'required|integer',
            'days_number' => 'nullable|integer',
            'exceeding_minimum_cash_limit' => 'nullable',
            'exceeding_minimum_days_limit' => 'nullable',
            'commercial_register_image' => 'nullable|image',
            'credit_limit' => 'required|integer',
            'national_address' => 'nullable',
            'supplier_type_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && is_numeric($value) && !SupplierType::query()->whereKey((int) $value)->exists()) {
                        $fail('فئة المورد المحددة غير موجودة.');
                    }
                },
            ],
            'supplier_type_name' => 'required|string|max:255',
            'file' => 'nullable',
            // 'account_id' => 'required|exists:fn_accounts,id',
        ];
    }

    protected function update(): array
    {
        return [
            'name' => 'required|unique:fn_suppliers,name,' . $this->supplier,
            'code' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string|email',
            'tax_number' => 'nullable|integer',
            'tax_image' => 'nullable|image',
            'days_number' => 'nullable|integer',
            'exceeding_minimum_cash_limit' => 'nullable',
            'exceeding_minimum_days_limit' => 'nullable',
            'commercial_register_number' => 'required|integer',
            'commercial_register_image' => 'nullable|image',
            'credit_limit' => 'required|integer',
            'national_address' => 'nullable',
            'supplier_type_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '' && is_numeric($value) && !SupplierType::query()->whereKey((int) $value)->exists()) {
                        $fail('فئة المورد المحددة غير موجودة.');
                    }
                },
            ],
            'supplier_type_name' => 'required|string|max:255',
            'file' => 'nullable',
            // 'account_id' => 'required|exists:fn_accounts,id',
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
