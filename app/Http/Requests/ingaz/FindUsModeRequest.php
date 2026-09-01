<?php

namespace App\Http\Requests\ingaz;

use App\Support\FindUsSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FindUsModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'find_us_mode' => ['required', 'string', Rule::in(FindUsSettings::modes())],
        ];
    }

    public function attributes(): array
    {
        return [
            'find_us_mode' => __('find_us.mode'),
        ];
    }
}
