<?php

namespace App\Http\Requests\ingaz\auth;

use App\Http\Requests\MainRequest;

class ResetPasswordRequest extends MainRequest
{
    public function rules()
    {
        return [
            'phone' => 'required',
            'phone_code' => 'required',
            'reset' => 'nullable',
            'g-recaptcha-response' => 'nullable',
        ];
    }
}
