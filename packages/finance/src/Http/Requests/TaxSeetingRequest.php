<?php

namespace Nami\Finance\Http\Requests;
use App\Http\Requests\MainRequest;
class TaxSeetingRequest extends MainRequest
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
            'name' => 'required|string|max:500',
            'account_id' => 'required|exists:fn_accounts,id',
            'value' => 'required|integer',
        ];
    }

    protected function update(): array
    {
        return [
            'name' => 'required|string|max:500',
            'account_id' => 'required|exists:fn_accounts,id',
            'value' => 'required|integer',
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
