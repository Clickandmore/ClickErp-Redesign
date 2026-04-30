<?php

namespace App\Http\Requests\APPAPI\Auth;

class ResetPasswordRequest extends AppApiRequest
{
    public function rules(): array
    {
        return [
            'new_password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:new_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.required' => $this->appApiMessage('كلمة المرور الجديدة مطلوبة.', 'New password is required.'),
            'confirm_password.required' => $this->appApiMessage('تأكيد كلمة المرور مطلوب.', 'Password confirmation is required.'),
            'confirm_password.same' => $this->appApiMessage('تأكيد كلمة المرور غير متطابق.', 'Password confirmation does not match.'),
        ];
    }
}
