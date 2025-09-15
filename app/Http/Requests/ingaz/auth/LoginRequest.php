<?php

namespace App\Http\Requests\ingaz\auth;

use App\Enums\api\UserTypeisEnum;
use App\Http\Requests\MainRequest;
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
        return [
            'phone' => 'required|regex:/^5\d{8}$/',
            'password' => 'required|min:6',
            'name' => 'nullable|string|min:2|max:255',
        ];
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
