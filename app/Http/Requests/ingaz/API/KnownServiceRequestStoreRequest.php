<?php

namespace App\Http\Requests\ingaz\API;

use App\Http\Requests\MainRequest;

class KnownServiceRequestStoreRequest extends MainRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:50'],
            'client_email' => ['required', 'email', 'max:255'],
            'client_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'worker_name' => ['required', 'string', 'max:255'],
            'nationality_id' => ['required', 'exists:nationalities,id'],
            'passport_number' => ['required', 'string', 'max:100'],
            'passport_expiration_date' => ['nullable', 'date'],
            'age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'social_status_id' => ['nullable', 'exists:social_statuses,id'],
            'religion_id' => ['nullable', 'exists:religions,id'],
            'skills' => ['nullable', 'string', 'max:2000'],
            'required_salary' => ['nullable', 'numeric', 'min:0'],
            'worker_phone' => ['nullable', 'string', 'max:50'],
            'worker_country_address' => ['nullable', 'string', 'max:255'],
            'relative_in_saudi_name' => ['nullable', 'string', 'max:255'],
            'relative_in_saudi_phone' => ['nullable', 'string', 'max:50'],
            'worker_passport_copy' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'worker_personal_photo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'client_identity_copy' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'g-recaptcha-response' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_name.required' => 'اسم العميل مطلوب.',
            'client_phone.required' => 'رقم هاتف العميل مطلوب.',
            'client_email.required' => 'البريد الإلكتروني مطلوب.',
            'client_email.email' => 'البريد الإلكتروني غير صالح.',
            'client_address.required' => 'عنوان العميل مطلوب.',
            'city.required' => 'المدينة مطلوبة.',
            'worker_name.required' => 'اسم العاملة مطلوب.',
            'nationality_id.required' => 'جنسية العاملة مطلوبة.',
            'nationality_id.exists' => 'جنسية العاملة غير صحيحة.',
            'passport_number.required' => 'رقم جواز السفر مطلوب.',
            'passport_expiration_date.date' => 'تاريخ انتهاء الجواز غير صالح.',
            'age.integer' => 'العمر يجب أن يكون رقمًا صحيحًا.',
            'age.min' => 'العمر يجب ألا يقل عن 0.',
            'age.max' => 'العمر يجب ألا يزيد عن 100.',
            'religion_id.exists' => 'الديانة غير صحيحة.',
            'skills.string' => 'المهارات يجب أن تكون نصًا صالحًا.',
            'required_salary.numeric' => 'الراتب المطلوب يجب أن يكون رقمًا.',
            'worker_passport_copy.mimes' => 'صورة جواز العاملة يجب أن تكون PDF أو صورة.',
            'worker_personal_photo.mimes' => 'الصورة الشخصية للعاملة يجب أن تكون PDF أو صورة.',
            'client_identity_copy.mimes' => 'هوية العميل يجب أن تكون PDF أو صورة.',
            'g-recaptcha-response.required' => 'التحقق الأمني مطلوب.',
        ];
    }
}


