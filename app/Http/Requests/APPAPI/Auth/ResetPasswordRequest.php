<?php

namespace App\Http\Requests\APPAPI\Auth;

class ResetPasswordRequest extends AppApiRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^5\d{8}$/', 'exists:users,phone'],
            'new_password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:new_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => $this->appApiMessage('رقم الجوال مطلوب.', 'Phone number is required.'),
            'phone.regex' => $this->appApiMessage('صيغة رقم الجوال غير صحيحة. يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.', 'Invalid phone number format. It must start with 5 and contain 9 digits.'),
            'phone.exists' => $this->appApiMessage('رقم الجوال غير مسجل في النظام.', 'Phone number is not registered.'),
            'new_password.required' => $this->appApiMessage('كلمة المرور الجديدة مطلوبة.', 'New password is required.'),
            'new_password.min' => $this->appApiMessage('كلمة المرور الجديدة يجب ألا تقل عن 6 أحرف.', 'New password must be at least 6 characters.'),
            'confirm_password.required' => $this->appApiMessage('تأكيد كلمة المرور مطلوب.', 'Password confirmation is required.'),
            'confirm_password.same' => $this->appApiMessage('تأكيد كلمة المرور غير متطابق.', 'Password confirmation does not match.'),
        ];
    }
}
