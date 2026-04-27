<?php

namespace Nami\Finance\Http\Requests;


use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class CostCenterRequest extends MainRequest
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
            'title' => 'required|unique:fn_cost_centers,title',
            'table_name' => 'required|string',
            'code' => 'required|string|unique:fn_cost_centers,code',
            'table_related_id' => 'nullable|integer',
            'parent_id' => 'nullable|exists:fn_cost_centers,id',
        ];
    }

    protected function update(): array
    {
        return [
            'title' => [
                'required',
                'unique:fn_cost_centers,title,' . $this->route('cost_center') . ',id',
            ],
            'table_name' => 'required|string',
            'code' => [
                'required',
                'string',
                'unique:fn_cost_centers,code,' . $this->route('cost_center') . ',id',
            ],
            'table_related_id' => 'nullable|integer',
            'parent_id' => 'nullable|exists:fn_cost_centers,id',
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

    public function messages(): array
    {
        return [
            'title.required' => __('finance.title_required'),
            'title.unique' => __('finance.title_unique'),
            'table_name.required' => __('finance.table_name_required'),
            'table_name.string' => __('finance.table_name_string'),
            'code.required' => __('finance.code_required'),
            'code.string' => __('finance.code_string'),
            'code.unique' => __('finance.code_unique'),
            'table_related_id.integer' => __('finance.table_related_id_integer'),
            'parent_id.exists' => __('finance.parent_id_exists'),
        ];
    }


}
