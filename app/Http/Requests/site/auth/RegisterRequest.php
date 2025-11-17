<?php

namespace App\Http\Requests\site\auth;

use App\Http\Requests\MainRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends MainRequest
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

    public function prepareForValidation()
    {
        $this->merge([
            'phone_code' => '+966',
        ]);
        $phone = $this->input('phone');
        if ($phone && RateLimiter::tooManyAttempts('register:' . $phone, 1)) {
            throw ValidationException::withMessages([
                'phone' => [__('web.wait_before_retry')],
            ]);
        }
        if ($phone) {
            RateLimiter::hit('register:' . $phone, 10);
        }
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:50'],
            'phone' => ['required', 'string', 'unique:users,phone', 'regex:/^5\d{8}$/','min:9', 'max:9'],
            'phone_code' => ['required', 'string'],
            // 'email' => ['required', 'string', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'g-recaptcha-response' => 'required',

        ];
    }


    public function messages(): array
    {
        return [
            // الاسم
            'name.required' => __('ingaz.name_required'),
            'name.string' => __('ingaz.name_string'),
            'name.min' => __('ingaz.name_min', ['min' => '3']),
            'name.max' => __('ingaz.name_max', ['max' => '50']),

            'phone.exists' => __('ingaz.phone_exists'),
            // الهاتف
            'phone.required' => __('ingaz.phone_required'),
            'phone.string' => __('ingaz.phone_string'),
            'phone.unique' => __('ingaz.phone_unique'),
            'phone.regex' => __('web.phone_validation_text'),
            'phone.min' => __('ingaz.phone_min', ['min'=> '9']),
            'phone.max' => __('ingaz.phone_max', ['max'=> '9']),


            // الايميل
            'email.required' => __('ingaz.email_required'),
            'email.string' => __('ingaz.email_string'),
            'email.unique' => __('ingaz.email_unique'),

            // كلمة المرور
            'password.required' => __('ingaz.password_required'),
            'password.string' => __('ingaz.password_string'),
            'password.min' => __('ingaz.password_min', ['min' => 6]),
            'password.confirmed' => __('ingaz.password_confirmed'),
        ];
    }


}
