<?php

namespace App\Http\Requests\office;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class OfficeFileRequest extends MainRequest
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
            "recruitment_office_id" => "required|exists:recruitment_offices,id",
            "office_file_name" => "required|string",
            "job_title_id" => "required|exists:job_titles,id",
            "religion_id" => "required|exists:religions,id",
            "marital_status" => "required|string",
            "contract_duration" => "required|string",
            "passport_validity_period" => "required|string",
            "salary" => "required|string",
            "recruitment_price" => "required|string",
            "languages" => "required|array",
            "skillsIds" => "required|array",
            "skillsIds.*" => "required|exists:skills,id",
            "passport_issuance_location" => "required|array",
            "education" => "required|array",
        ];
        return $rules;
    }

    public function messages(): array
    {
        return [
            'recruitment_office_id.required' => __('ingaz.recruitment_office_id_required'),
            'recruitment_office_id.exists'   => __('ingaz.recruitment_office_id_exists'),

            'office_file_name.required'      => __('ingaz.office_file_name_required'),
            'office_file_name.string'        => __('ingaz.office_file_name_string'),

            'job_title_id.required'          => __('ingaz.job_title_id_required'),
            'job_title_id.exists'            => __('ingaz.job_title_id_exists'),

            'religion_id.required'           => __('ingaz.religion_id_required'),
            'religion_id.exists'             => __('ingaz.religion_id_exists'),

            'nationality_id.required'        => __('ingaz.nationality_id_required'),
            'nationality_id.exists'          => __('ingaz.nationality_id_exists'),

            'marital_status.required'        => __('ingaz.marital_status_required'),
            'marital_status.string'          => __('ingaz.marital_status_string'),

            'contract_duration.required'     => __('ingaz.contract_duration_required'),
            'contract_duration.string'       => __('ingaz.contract_duration_string'),

            'passport_validity_period.required' => __('ingaz.passport_validity_period_required'),
            'passport_validity_period.string'   => __('ingaz.passport_validity_period_string'),

            'salary.required'                => __('ingaz.salary_required'),
            'salary.string'                  => __('ingaz.salary_string'),

            'recruitment_price.required'     => __('ingaz.recruitment_price_required'),
            'recruitment_price.string'       => __('ingaz.recruitment_price_string'),

            'languages.required'             => __('ingaz.languages_required'),
            'languages.array'                => __('ingaz.languages_array'),

            'skillsIds.required'                => __('ingaz.skills_required'),
            'skillsIds.array'                   => __('ingaz.skills_array'),
            'skillsIds.*.exists'                => __('ingaz.skills_exists'),

            'passport_issuance_location.required' => __('ingaz.passport_issuance_location_required'),
            'passport_issuance_location.numeric'  => __('ingaz.passport_issuance_location_numeric'),

            'education.required'             => __('ingaz.education_required'),
            'education.numeric'              => __('ingaz.education_numeric'),
        ];
    }


}
