<?php

namespace App\Http\Requests\ingaz;

use Illuminate\Validation\Rule;

use App\Enums\ingaz\RequirementEnum;
use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class SelectWorkerRequest extends MainRequest
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
            'icon' => [$this->isMethod('post') ? 'nullable' : ''],
        ];



        return $rules;
    }
}
