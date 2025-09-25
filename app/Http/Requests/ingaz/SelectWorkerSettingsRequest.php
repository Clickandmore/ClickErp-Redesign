<?php

namespace App\Http\Requests\ingaz;

use Illuminate\Validation\Rule;

use App\Enums\ingaz\RequirementEnum;
use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class SelectWorkerSettingsRequest extends MainRequest
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
            'body:ar' => 'required|string',
            'body:en' => 'required|string',
            'link'    => 'required|string',
            'image'   => $this->isMethod('post')
                ? 'required|image'
                : 'nullable|image',
        ];


        return $rules;
    }
}
