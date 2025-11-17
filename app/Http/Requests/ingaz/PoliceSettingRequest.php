<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;

class PoliceSettingRequest extends MainRequest
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
            'title_1:en' => 'sometimes',
            'description_1:en' => 'sometimes',
            'title_2:ar' => 'sometimes',
            'description_2:ar' => 'sometimes',
            'file_workers_rights' => 'sometimes',
            'file_employee_rights' => 'sometimes',
        ];
    }
}
