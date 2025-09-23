<?php

namespace App\Http\Requests\site\auth;

use App\Http\Requests\MainRequest;

class ChangePasswordRequest extends MainRequest
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'exists:users,phone'],
            'g-recaptcha-response' => 'required',

        ];
    }


    public function messages(): array
    {
        return [
            'password.required' => __('ingaz.password_required'),
            'password.string' => __('ingaz.password_string'),
            'password.min' => __('ingaz.password_min'),
            'password.confirmed' => __('ingaz.password_confirmed'),
            'g-recaptcha-response.required' => __('ingaz.captcha_required'),

        ];
    }


}
