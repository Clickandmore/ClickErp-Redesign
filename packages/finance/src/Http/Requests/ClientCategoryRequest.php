<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class ClientCategoryRequest extends MainRequest
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
            'name' => 'required|unique:fn_client_categories,name',
        ];
    }

    protected function update(): array
    {
//        dd($this->route()->parameter('client_category'));
        return [
            'name' => 'required|unique:fn_client_categories,name,' . $this->route('client_category')        ];
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
