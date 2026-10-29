<?php

namespace App\Http\Requests\APPAPI\Auth;

use App\Models\ingaz\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends AppApiRequest
{
    protected function failedValidation(Validator $validator)
    {
        if (isset($validator->failed()['phone']['Exists'])) {
            $message = $this->appApiMessage('رقم الجوال غير مسجل في النظام.', 'Phone number is not registered.');
            throw new HttpResponseException(apiResponse(false, $message, [
                'registered' => false,
                'next_step' => 'register',
                'auth_mode' => \App\Support\AppAuthSettings::mode(),
                'phone' => (string) $this->phone,
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        return [
            'phone' => ['bail', 'required', 'regex:/^5\d{8}$/', 'exists:users,phone'],
            'password' => [\App\Support\AppAuthSettings::usesOtpOnly() ? 'nullable' : 'required', 'string', 'min:6'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->phone) {
                return;
            }

            $user = User::where('phone', $this->phone)->first();

            if ($user && (int) $user->is_blocked === 1) {
                $validator->errors()->add('phone', $this->appApiMessage('تم حظر حسابك، يرجى التواصل مع الدعم.', 'Your account has been blocked, please contact support.'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'phone.required' => $this->appApiMessage('رقم الجوال مطلوب.', 'Phone number is required.'),
            'phone.regex' => $this->appApiMessage('صيغة رقم الجوال غير صحيحة. يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.', 'Invalid phone number format. It must start with 5 and contain 9 digits.'),
            'phone.exists' => $this->appApiMessage('رقم الجوال غير مسجل في النظام.', 'Phone number is not registered.'),
            'password.required' => $this->appApiMessage('كلمة المرور مطلوبة.', 'Password is required.'),
            'password.min' => $this->appApiMessage('كلمة المرور يجب ألا تقل عن 6 أحرف.', 'Password must be at least 6 characters.'),
        ];
    }
}
