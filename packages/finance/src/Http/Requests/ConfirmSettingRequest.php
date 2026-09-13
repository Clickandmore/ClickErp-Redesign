<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use App\Support\ShelterAccountingSettings;

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
        ] + $this->shelterAccountingRules();
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
        ] + $this->shelterAccountingRules();
    }

    /**
     * قواعد قسم «حسابات الإيواء والترحيل» (App\Support\ShelterAccountingSettings):
     * مفتاحان منطقيان on/off، وخمسة حسابات طرفية اختيارية من fn_accounts.
     */
    protected function shelterAccountingRules(): array
    {
        $rules = [];

        foreach (ShelterAccountingSettings::TOGGLE_KEYS as $key) {
            $rules["data.{$key}.title"] = "sometimes|in:{$key}";
            $rules["data.{$key}.value"] = 'sometimes|in:on,off';
        }

        foreach (ShelterAccountingSettings::ACCOUNT_KEYS as $key) {
            $rules["data.{$key}.title"] = "sometimes|in:{$key}";
            $rules["data.{$key}.value"] = 'nullable|integer|exists:fn_accounts,id';
        }

        return $rules;
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
