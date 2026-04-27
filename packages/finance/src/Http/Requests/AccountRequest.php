<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class AccountRequest extends MainRequest
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
            'name' => 'required|unique:fn_accounts,name',
            'code' => 'required|numeric',
            'status' => 'required|in:debtor,creditor',
            'parent_id' => 'nullable',
            'type' => 'nullable',
            'operation_type' => 'nullable',
        ];
    }

    protected function update(): array
    {
        return [
            'name' => 'required',
            'code' => 'required|numeric',
            'status' => 'required|in:debtor,creditor',
            'parent_id' => 'nullable',
            'type' => 'nullable',
            'operation_type' => 'nullable',
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
