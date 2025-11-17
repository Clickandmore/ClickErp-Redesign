<?php

namespace App\Http\Requests\site;

use App\Http\Requests\MainRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class DailyLabotDemandRequest extends MainRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function prepareForValidation()
    {
        $phone = $this->input('phone');
        if ($phone && RateLimiter::tooManyAttempts('daily-lebor-demand:' . $phone, 1)) {
            throw ValidationException::withMessages([
                'phone' => [__('web.wait_before_retry')],
            ]);
        }
        if ($phone) {
            RateLimiter::hit('daily-lebor-demand:' . $phone, 3);
        }
    }

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'facility_name' => ['required', 'string'],
            'phone' => ['required', 'string',],
            'number_of_workers_required' => ['required', 'string'],
            'job_title_id' => ['required', 'exists:job_titles,id'],
            'visa_number' => ['required', 'string'],
            'other_requirement' => ['required', 'string'],
            'g-recaptcha-response' => 'required',

        ];
    }


    public function messages(): array
    {
        return [
            // اسم المنشأة
            'facility_name.required' => __('ingaz.facility_name_required'),
            'facility_name.string' => __('ingaz.facility_name_string'),

            // الهاتف
            'phone.required' => __('ingaz.phone_required'),
            'phone.string' => __('ingaz.phone_string'),

            // عدد العمال
            'number_of_workers_required.required' => __('ingaz.number_of_workers_required_required'),
            'number_of_workers_required.string' => __('ingaz.number_of_workers_required_string'),

            // المسمى الوظيفي
            'job_title_id.required' => __('ingaz.job_title_id_required'),
            'job_title_id.exists' => __('ingaz.job_title_id_exists'),

            // رقم التأشيرة
            'visa_number.required' => __('ingaz.visa_number_required'),
            'visa_number.string' => __('ingaz.visa_number_string'),

            // متطلبات أخرى
            'other_requirement.required' => __('ingaz.other_requirement_required'),
            'other_requirement.string' => __('ingaz.other_requirement_string'),
        ];
    }
}
