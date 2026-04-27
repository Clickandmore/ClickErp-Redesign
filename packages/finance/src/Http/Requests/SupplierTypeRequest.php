<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Nami\Finance\Rules\HasConstraintDetails;

class SupplierTypeRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function prepareForValidation()
    {

    }
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
            'name' => 'required|unique:fn_supplier_types,name',
            'account_id' => 'nullable',
        ];
    }

    protected function update(): array
    {
        return [
            'name' => 'required',
            'account_id' => 'nullable',
        ];
    }

    protected function destroy(): array
    {
        return [
            'box' => [
                new  HasConstraintDetails(box_id:request()->box )
            ]
        ];
    }

    protected function view(): array
    {
        return [];
    }
}
