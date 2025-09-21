<?php

namespace App\Http\Requests\site;

use App\Http\Requests\MainRequest;

class ContactRequest extends MainRequest
{

    public function authorize() { return true; }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',

            'g-recaptcha-response' => 'required'

        ];
    }

    public function messages() {
        return [
            'name.required' => __('ingaz.name_required'),
            'name.string' => __('ingaz.name_string'),
            'name.max' => __('ingaz.name_max'),

            'phone.required' => __('ingaz.phone_required'),
            'phone.string' => __('ingaz.phone_string'),
            'phone.max' => __('ingaz.phone_max'),

            'subject.required' => __('ingaz.subject_required'),
            'subject.string' => __('ingaz.subject_string'),
            'subject.max' => __('ingaz.subject_max'),

            'message.required' => __('ingaz.message_required'),
            'message.string' => __('ingaz.message_string'),

            'g-recaptcha-response.required' => __('ingaz.captcha_required'),
        ];
    }
}
