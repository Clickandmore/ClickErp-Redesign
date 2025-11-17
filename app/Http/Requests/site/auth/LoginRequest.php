<?php

namespace App\Http\Requests\site\auth;

use App\Http\Requests\MainRequest;
use App\Models\ingaz\User;

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
            'phone' => [
                'bail',
                'required',
                'regex:/^5\d{8}$/', // يبدأ بـ 5 ويتكون من 9 أرقام
                'exists:users,phone',
            ],
            'password' => 'required|string',
            'g-recaptcha-response' => 'required',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->phone) {
                $user = User::where('phone', $this->phone)->first();

                if ($user && $user->is_blocked == 1) {
                    $validator->errors()->add('phone', __('web.account_is_blocked'));
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            // الهاتف
            'phone.required' => __('ingaz.phone_required'),
            'phone.exists' => __('ingaz.phone_exists'),
            'g-recaptcha-response.required' => __('ingaz.captcha_required'),
            'phone.regex' => __('web.phone_validation_text'),

            // كلمة المرور
            'password.required' => __('ingaz.password_required'),
            'password.string' => __('ingaz.password_string'),
        ];
    }


}
