<?php

namespace App\Http\Requests\APPAPI\Auth;

use App\Enums\RegisterFromType;
use App\Enums\ingaz\FirebaseTypeIsEnum;
use Illuminate\Validation\Rules\Enum;

class RegisterRequest extends AppApiRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'regex:/^5\d{8}$/', 'unique:users,phone'],
            'password' => [\App\Support\ExternalAuthSettings::usesOtpOnly() ? 'nullable' : 'required', 'string', 'min:6'],
            'password_confirmation' => [\App\Support\ExternalAuthSettings::usesOtpOnly() ? 'nullable' : 'required', 'same:password'],
            'phone_code' => ['required', 'string', 'max:10'],
            'register_from' => ['required', new Enum(RegisterFromType::class)],
            'software_type' => ['required', new Enum(FirebaseTypeIsEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => $this->appApiMessage('الاسم مطلوب.', 'Name is required.'),
            'phone.required' => $this->appApiMessage('رقم الجوال مطلوب.', 'Phone number is required.'),
            'phone.regex' => $this->appApiMessage('صيغة رقم الجوال غير صحيحة. يجب أن يبدأ بـ 5 ويتكون من 9 أرقام.', 'Invalid phone number format. It must start with 5 and contain 9 digits.'),
            'phone.unique' => $this->appApiMessage('هذا الرقم مسجل بالفعل.', 'This phone number is already registered.'),
            'password.required' => $this->appApiMessage('كلمة المرور مطلوبة.', 'Password is required.'),
            'password.min' => $this->appApiMessage('كلمة المرور يجب ألا تقل عن 6 أحرف.', 'Password must be at least 6 characters.'),
            'password_confirmation.required' => $this->appApiMessage('تأكيد كلمة المرور مطلوب.', 'Password confirmation is required.'),
            'password_confirmation.same' => $this->appApiMessage('تأكيد كلمة المرور غير متطابق.', 'Password confirmation does not match.'),
            'phone_code.required' => $this->appApiMessage('مفتاح الدولة مطلوب.', 'Phone code is required.'),
            'register_from.required' => $this->appApiMessage('نوع التسجيل مطلوب.', 'Register source is required.'),
            'software_type.required' => $this->appApiMessage('نوع النظام مطلوب.', 'Software type is required.'),
        ];
    }
}
