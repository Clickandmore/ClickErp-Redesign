<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class ConstraintDetailsRequest extends MainRequest
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
            'type' => 'required',
            'debtor_value' => 'required',
            'creditor_value' => 'required|date',
            'statement_account' => 'nullable',
            'account_id' => 'required',
        ];
    }

    protected function update(): array
    {
        return [
            'type' => 'required',
            'debtor_value' => 'required',
            'creditor_value' => 'required|date',
            'statement_account' => 'nullable',
            'account_id' => 'required',
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
