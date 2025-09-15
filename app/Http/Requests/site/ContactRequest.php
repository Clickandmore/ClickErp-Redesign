<?php

namespace App\Http\Requests\site;

use App\Http\Requests\MainRequest;

class ContactRequest extends MainRequest
{

    public function authorize() { return true; }

public function rules() {
    return [
        'name' => 'required|string|max:255',
         'phone' => 'nullable|string|max:50',
        'subject' => 'nullable|string|max:255',
        'message' => 'required|string',
        'latitude' => 'nullable|numeric',
'longitude' => 'nullable|numeric',
        'g-recaptcha-response' => 'required|captcha'

     ];
}

public function messages() {
    return [
        'name.required' => __('contact.validation.name_required'),
        'message.required' => __('contact.validation.message_required'),
    ];
}
}