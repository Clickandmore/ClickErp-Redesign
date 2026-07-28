<?php

namespace App\Http\Requests\ingaz;

use App\Enums\ingaz\RequirementEnum;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class RequirementRequest extends MainRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules()
    {
        return [
            'type' => ['required', Rule::in(array_column(RequirementEnum::cases(), 'value'))],
            'title:ar' => 'required|string|max:255',
            'title:en' => 'required|string|max:255',
            'sort_order' => 'required|integer|min:1|max:9999',
            'is_active' => 'required|boolean',
            'points' => 'required|array|min:1|max:30',
            'points.*.id' => 'nullable|integer|exists:requirement_items,id',
            'points.*.sort_order' => 'required|integer|min:1|max:9999',
            'points.*.ar.text' => 'required|string|max:500',
            'points.*.en.text' => 'required|string|max:500',
        ];
    }
}
