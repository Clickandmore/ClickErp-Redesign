<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends MainRequest
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
            'title' => 'required|unique:fn_units,title',
            'parent_id' => 'nullable|integer',
            'value' => 'nullable|numeric',
        ];
    }

    protected function update(): array
    {
        return [
            'title' => 'required|unique:fn_units,title,' . $this->unit,
            'parent_id' => 'nullable|integer',
            'value' => 'nullable|numeric',
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
