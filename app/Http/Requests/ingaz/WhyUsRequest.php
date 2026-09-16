<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;

class WhyUsRequest extends MainRequest
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
        $rules = [
            'icon' => 'required|string|max:100',
            'sort_order' => 'required|integer|min:1|max:9999',
            'is_active' => 'required|boolean',
        ];

        foreach (config('translatable.locales') as $locale) {
            $rules["title:$locale"] = 'required|string|max:255';
            $rules["description:$locale"] = 'required|string|max:1000';
        }

        return $rules;
    }
}
