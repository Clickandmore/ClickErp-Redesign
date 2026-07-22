<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Validator;

class ConstraintRequest extends MainRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->normalizeNumericValue($this->input('code')),
            'value' => $this->normalizeNumericValue($this->input('value')),
            'debtor_value' => $this->normalizeNumericValue($this->input('debtor_value', [])),
            'creditor_value' => $this->normalizeNumericValue($this->input('creditor_value', [])),
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
            'type' => 'nullable',
            'value' => 'required|numeric|gt:0',
            'date' => 'required|date_format:Y-m-d',
            'statement' => 'nullable|string',
            'account_id' => 'required|array|min:2',
            'account_id.*' => 'required|exists:fn_accounts,id',
            'debtor_value' => 'required|array|min:1',
            'debtor_value.*' => 'nullable|numeric|min:0',
            'creditor_value' => 'required|array|min:1',
            'creditor_value.*' => 'nullable|numeric|min:0',
            'code' => 'required|numeric',
            'cost_center_required' => 'nullable',
            'cost_center_id' => 'nullable|array',
            'cost_center_id.*' => 'nullable|required_if:cost_center_required,on|exists:fn_cost_centers,id',
            'file' => 'nullable|array',
            'file.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv|max:10240',
            'statement_account' => 'nullable|array',
            'statement_account.*' => 'nullable|string',
            'account_type' => 'required|array|min:2',
            'account_type.*' => 'required|in:debtor,creditor',
        ];
    }

    protected function update(): array
    {
        return [
            'type' => 'nullable',
            'value' => 'sometimes|nullable|numeric|gt:0',
            'date' => 'required|date_format:Y-m-d',
            'statement' => 'nullable|string',
            'account_id' => 'required|array|min:2',
            'account_id.*' => 'required|exists:fn_accounts,id',
            'debtor_value' => 'required|array|min:1',
            'debtor_value.*' => 'nullable|numeric|min:0',
            'old_id' => 'nullable|array',
            'old_id.*' => 'nullable|exists:fn_constraint_details,id',
            'creditor_value' => 'required|array|min:1',
            'creditor_value.*' => 'nullable|numeric|min:0',
            'cost_center_required' => 'nullable',
            'cost_center_id' => 'nullable|array',
            'cost_center_id.*' => 'nullable|required_if:cost_center_required,on|exists:fn_cost_centers,id',
            'file' => 'nullable|array',
            'file.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv|max:10240',
            'statement_account' => 'nullable|array',
            'statement_account.*' => 'nullable|string',
            'account_type' => 'required|array|min:2',
            'account_type.*' => 'required|in:debtor,creditor',
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $debtorTotal = $this->sumAmounts($this->input('debtor_value', []));
            $creditorTotal = $this->sumAmounts($this->input('creditor_value', []));

            if ($debtorTotal <= 0 || round($debtorTotal, 2) !== round($creditorTotal, 2)) {
                $validator->errors()->add('value', __('finance::finance.debit_credit_totals_must_match'));
            }
        });
    }

    private function normalizeNumericValue($value)
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeNumericValue($item), $value);
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫', '٬', '،'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '', '.'],
            $value
        );

        if (substr_count($value, ',') === 1 && !str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        return str_replace(' ', '', $value);
    }

    private function sumAmounts(array $amounts): float
    {
        return collect($amounts)
            ->map(fn ($amount) => (float) $this->normalizeNumericValue($amount))
            ->sum();
    }
}
