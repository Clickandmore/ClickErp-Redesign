<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArrivalWorkerRequest extends MainRequest
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
            $rules["title:$locale"] = ['required'];
            $rules["sub_title:$locale"] = ['required'];
            $rules["body:$locale"] = ['required'];
        }
        $rules['image'] = [$this->isMethod('post') ? 'required' : ''];
        return $rules;
    }
}
