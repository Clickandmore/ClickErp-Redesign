<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class FiscalYearClosingRequest extends MainRequest
{
    protected function prepareForValidation(): void
    {
        $allocations = collect($this->input('allocations', []))->map(function ($allocation) {
            if (isset($allocation['value'])) {
                $allocation['value'] = str_replace(
                    ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫', '٬', ','],
                    ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', '', ''],
                    (string) $allocation['value']
                );
            }
            return $allocation;
        })->all();

        $this->merge(['allocations' => $allocations]);
    }

    public function rules(): array
    {
        return [
            'year_id' => ['required', 'integer', 'exists:fn_years,id'],
            'closing_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.account_id' => ['required', 'integer', 'distinct', 'exists:fn_accounts,id'],
            'allocations.*.value' => ['required', 'numeric', 'gt:0'],
            'allocations.*.movement' => ['required', 'in:debtor,creditor'],
            'allocations.*.statement' => ['nullable', 'string', 'max:500'],
        ];
    }
}
