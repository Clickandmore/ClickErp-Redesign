<?php

namespace App\Http\Requests\ingaz;

use Illuminate\Validation\Rule;

use App\Enums\ingaz\RequirementEnum;
use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class FeatureRequest extends MainRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [
            'title:ar' => 'string',
            'title:en' => 'string',
            'description:ar' => 'string',
            'description:en' => 'string',
            'image' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ];



        return $rules;
    }
}
