<?php

namespace App\Http\Requests\site\auth;

use App\Http\Requests\MainRequest;

class ForgetPasswordRequest extends MainRequest
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

    protected function prepareForValidation()
    {
        $this->merge([
            'phone_code' => '+966',
        ]);
    }

    public function rules()
    {
        return [
            'phone' => 'bail|required|exists:users,phone',
            'phone_code' => 'string',
            'g-recaptcha-response' => 'required',

        ];
    }


    public function messages(): array
    {
        return [
            // الهاتف
            'phone.required' => __('ingaz.phone_required'),
            'phone.exists' => __('ingaz.phone_exists'),
            'g-recaptcha-response.required' => __('ingaz.captcha_required'),

        ];
    }


}
