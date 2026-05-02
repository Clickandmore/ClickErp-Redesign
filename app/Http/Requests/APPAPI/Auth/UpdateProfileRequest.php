<?php

namespace App\Http\Requests\APPAPI\Auth;

use Illuminate\Validation\Rule;

class UpdateProfileRequest extends AppApiRequest
{
    public function rules(): array
    {
        $userId = auth('api')->id();

        return [
            'name' => ['nullable', 'string', 'min:2', 'max:255'],
            'phone' => ['nullable', 'regex:/^5\d{8}$/', Rule::unique('users', 'phone')->ignore($userId)],
            'phone_code' => ['nullable', 'string', 'max:10'],
            'old_password' => ['required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:6'],
            'confirm_password' => ['required_with:new_password', 'same:new_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => $this->appApiMessage('صيغة رقم الجوال غير صحيحة. يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.', 'Invalid phone number format. It must start with 5 and contain 9 digits.'),
            'phone.unique' => $this->appApiMessage('هذا الرقم مسجل بالفعل.', 'This phone number is already registered.'),
            'old_password.required_with' => $this->appApiMessage('كلمة المرور الحالية مطلوبة.', 'Current password is required.'),
            'new_password.min' => $this->appApiMessage('كلمة المرور الجديدة يجب ألا تقل عن 6 أحرف.', 'New password must be at least 6 characters.'),
            'confirm_password.required_with' => $this->appApiMessage('تأكيد كلمة المرور مطلوب.', 'Password confirmation is required.'),
            'confirm_password.same' => $this->appApiMessage('تأكيد كلمة المرور غير متطابق.', 'Password confirmation does not match.'),
        ];
    }
}
