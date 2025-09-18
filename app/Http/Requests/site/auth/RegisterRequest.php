<?php

namespace App\Http\Requests\site\auth;

use App\Http\Requests\MainRequest;

class RegisterRequest extends MainRequest
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

    public function prepareForValidation()
    {
        $this->merge([
            'phone_code' => '+966',
        ]);
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'phone_code' => ['required', 'string'],
            // 'email' => ['required', 'string', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }


    public function messages(): array
    {
        return [
            // الاسم
            'name.required' => __('ingaz.name_required'),
            'name.string' => __('ingaz.name_string'),

            // الهاتف
            'phone.required' => __('ingaz.phone_required'),
            'phone.string' => __('ingaz.phone_string'),
            'phone.unique' => __('ingaz.phone_unique'),

            // الايميل
            'email.required' => __('ingaz.email_required'),
            'email.string' => __('ingaz.email_string'),
            'email.unique' => __('ingaz.email_unique'),

            // كلمة المرور
            'password.required' => __('ingaz.password_required'),
            'password.string' => __('ingaz.password_string'),
            'password.min' => __('ingaz.password_min', ['min' => 6]),
            'password.confirmed' => __('ingaz.password_confirmed'),
        ];
    }


}
