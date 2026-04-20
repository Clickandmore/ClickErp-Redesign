<?php

namespace App\Http\Requests\ingaz\API;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'visa_number' => ['required', 'string'],
            'nationality' => ['required', 'string'],
            'job_title' => ['required', 'string'],
            'social_status' => ['required', 'string'],
            'age' => ['required', 'integer'],
            'religion_id' => ['required', 'exists:religions,id'],
            'language' => ['required', 'string'],
            'worker_status' => ['required'],
            'notes' => ['nullable', 'string'],
            'g-recaptcha-response' => 'required'
        ];
    }


    public function messages(): array
    {
        return [
            'visa_number.required'               => __('ingaz.visa_number_is_required'),
            'nationality.required'               => __('ingaz.nationality_is_required'),
            'job_title.required'                 => __('ingaz.job_title_is_required'),
            'social_status.required'             => __('ingaz.social_status_is_required'),
            'age.required'                       => __('ingaz.age_is_required'),
            'religion_id.required'                 => __('ingaz.religion_is_required'),
            'religion_id.exists'                   => __('ingaz.religion_is_not_exists'),
            'language.required'                  => __('ingaz.language_is_required'),
            'worker_status.required'             => __('ingaz.worker_status_is_required'),
            'notes.required'                     => __('ingaz.notes_is_required'),


        ];
    }
}
