<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class MaidLanguageRequest extends MainRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $languageId = $this->route('language')?->id ?? $this->route('language');

        return [
            'title_ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('maid_languages', 'title_ar')->whereNull('deleted_at')->ignore($languageId),
            ],
            'title_en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('maid_languages', 'title_en')->whereNull('deleted_at')->ignore($languageId),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title_ar.required' => 'عنوان اللغة بالعربية مطلوب.',
            'title_ar.unique' => 'عنوان اللغة بالعربية مستخدم مسبقًا.',
            'title_en.required' => 'عنوان اللغة بالإنجليزية مطلوب.',
            'title_en.unique' => 'عنوان اللغة بالإنجليزية مستخدم مسبقًا.',
        ];
    }
}
