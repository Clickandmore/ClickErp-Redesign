<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class AccountTransferRequest extends MainRequest
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
            'debit_account_id' => 'required',
            'credit_account_id' => 'required',
            'cost_center_id' => 'nullable',
            'constraint_id' => 'nullable',
            'value' => 'required',
            'date' => 'required',
            'file' => 'nullable',
            'statement' => 'required',
            'is_confirm' => 'nullable',
        ];
    }

    protected function update(): array
    {
        return [
            'debit_account_id' => 'required',
            'credit_account_id' => 'required',
            'cost_center_id' => 'nullable',
            'constraint_id' => 'nullable',
            'value' => 'required',
            'date' => 'required',
            'statement' => 'required',
            'is_confirm' => 'nullable',
            'file' => 'nullable',
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
