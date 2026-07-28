<?php

namespace App\Http\Requests\ingaz\auth;

use App\Enums\api\UserTypeisEnum;
use App\Http\Requests\MainRequest;
use App\Support\ExternalAuthSettings;
use Illuminate\Validation\Rules\Enum;

class LoginRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'phone' => 'required|regex:/^5\d{8}$/',
            'name' => 'nullable|string|min:2|max:255',
        ];

        if (ExternalAuthSettings::usesOtpOnly()) {
            $rules['phone'] .= '|exists:users,phone';
        } else {
            $rules['password'] = 'required|min:6';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'phone.required' => 'رقم الجوال مطلوب',
            'phone.regex' => 'رقم الجوال يجب أن يبدأ بـ 5 ويتكون من 9 أرقام (مثال: 05xxxxxxx)',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
        ];
    }
}
