<?php

namespace App\Http\Requests\APPAPI\Auth;

class ConfirmCodeRequest extends AppApiRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^5\d{8}$/'],
            'phone_code' => ['required', 'string', 'max:10'],
            'code' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => $this->appApiMessage('رقم الجوال مطلوب.', 'Phone number is required.'),
            'phone.regex' => $this->appApiMessage('صيغة رقم الجوال غير صحيحة. يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.', 'Invalid phone number format. It must start with 5 and contain 9 digits.'),
            'phone_code.required' => $this->appApiMessage('مفتاح الدولة مطلوب.', 'Phone code is required.'),
        ];
    }
}
