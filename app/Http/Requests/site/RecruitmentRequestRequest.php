<?php

namespace App\Http\Requests\site;

use App\Http\Requests\MainRequest;

class RecruitmentRequestRequest extends MainRequest
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

    public function rules()
    {
        return [
            'visa_number' => ['required','string'],
            'nationality' => ['required' , 'string'],
            'job_title' => ['required' , 'string'],
            'social_status' => ['required' , 'string'],
            'age' => ['required' , 'integer'],
            'religion_id' => ['required' , 'exists:religions,id'],
            'language' => ['required' , 'string'],
            'worker_status' => ['required'],
            'notes' => ['required'],
        ];
    }


    public function messages(): array
    {
        return [
            'client_name.required'               => __('ingaz.client_name_required'),
            'client_name.string'                 => __('ingaz.client_name_string'),

        ];
    }



}
