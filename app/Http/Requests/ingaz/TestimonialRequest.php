<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TestimonialRequest extends MainRequest
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
        foreach (config('translatable.locales') as $locale) {
            $rules["name:$locale"] = ['required'];
            $rules["message:$locale"] = ['required'];
            $rules["job:$locale"] = ['required'];
        }
        $rules['image'] = [$this->isMethod('post') ? 'required' : ''];
        return $rules;
    }
}
