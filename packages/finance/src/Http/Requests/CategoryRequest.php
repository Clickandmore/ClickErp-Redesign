<?php

namespace Nami\Finance\Http\Requests;

use App\Http\Requests\MainRequest;

class CategoryRequest extends MainRequest
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
            'title' => 'required|unique:fn_categories,title',
            'parent_id' => 'nullable',
            'code' => 'required|string|unique:fn_categories,code',
        ];
    }

    protected function update(): array
    {
        return [
            'title' => 'required|unique:fn_categories,title,' . $this->route('category'),
            'parent_id' => 'nullable',
            'code' => 'required|string|unique:fn_categories,code,' . $this->route('category'),
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
