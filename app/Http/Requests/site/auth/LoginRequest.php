<?php

namespace App\Http\Requests\site\auth;

use App\Http\Requests\MainRequest;

class LoginRequest extends MainRequest
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
            'phone'    => 'bail|required|exists:users,phone',
            'password' => 'required|string',
        ];
    }


    public function messages(): array
    {
        return [
            // الهاتف
            'phone.required'  => __('ingaz.phone_required'),
            'phone.exists'    => __('ingaz.phone_exists'),

            // كلمة المرور
            'password.required' => __('ingaz.password_required'),
            'password.string'   => __('ingaz.password_string'),
        ];
    }



}
