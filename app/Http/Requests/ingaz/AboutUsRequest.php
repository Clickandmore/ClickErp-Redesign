<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class AboutUsRequest extends MainRequest
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
        return [
            'title:en' => 'sometimes',
            'description:en' => 'sometimes',
            'title:ar' => 'sometimes',
            'description:ar' => 'sometimes',
            'mission:en' => 'sometimes',
            'vision:en' => 'sometimes',
            'mission:ar' => 'sometimes',
            'vision:ar' => 'sometimes',
            'value:en' => 'sometimes',
            'value:ar' => 'sometimes',
        ];
    }
}
