<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\OnlyOnePrincipleManualDeprecationInDay;

class ManualDepreciationRequest extends MainRequest
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
            'principle_id' => ['required', 'exists:fn_principles,id', new OnlyOnePrincipleManualDeprecationInDay()],
            'account_id' => 'required',
            'perished_account_id' => 'required',
            'expense_account_id' => 'required',
            'date' => 'required',
            'value' => 'required',
            'statement' => 'nullable',
        ];
    }

    protected function update(): array
    {
        return [
            'date' => 'required',
            'value' => 'required',
            'statement' => 'nullable',
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
