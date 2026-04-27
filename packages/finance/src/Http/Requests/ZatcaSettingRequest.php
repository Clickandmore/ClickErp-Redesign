<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class ZatcaSettingRequest extends MainRequest
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
            'VAT_name' => 'required|string',
            'CRN_number' => 'required|string',
            'city' => 'required|string',
            'street' => 'nullable|string',
            'postal_zone' => 'required|string',
            'VAT_number' => 'required|string',
            'city_subdivision' => 'nullable|string',
            'building' => 'nullable|string',
            'branch_name' => 'nullable|string',
            'production' => 'nullable',
            'OTP' => 'required',
        ];
    }

    protected function update(): array
    {
        return [
            'VAT_name' => 'required|string',
            'CRN_number' => 'required|string',
            'city' => 'required|string',
            'street' => 'nullable|string',
            'postal_zone' => 'required|string',
            'VAT_number' => 'required|string',
            'city_subdivision' => 'nullable|string',
            'building' => 'nullable|string',
            'branch_name' => 'nullable|string',
            'production' => 'nullable',
            'OTP' => 'required',
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
