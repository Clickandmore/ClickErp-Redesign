<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class ConfirmSettingRequest extends MainRequest
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
            'title' => 'nullable',
            'value' => 'nullable',
            'status' => 'nullable',
            'tax_rate_revenue' => 'nullable',
            'tax_rate_expenditure' => 'nullable',
            'data' => 'nullable|array',
            'data.recruitment_supplier_cost_recognition.title' => 'sometimes|in:recruitment_supplier_cost_recognition',
            'data.recruitment_supplier_cost_recognition.value' => 'sometimes|required|in:on_arrival,on_dispatch',
        ];
    }

    protected function update(): array
    {
        return [
            'title' => 'nullable',
            'value' => 'nullable',
            'status' => 'nullable',
            'tax_rate_revenue' => 'nullable',
            'tax_rate_expenditure' => 'nullable',
            'data' => 'nullable|array',
            'data.recruitment_supplier_cost_recognition.title' => 'sometimes|in:recruitment_supplier_cost_recognition',
            'data.recruitment_supplier_cost_recognition.value' => 'sometimes|required|in:on_arrival,on_dispatch',
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
