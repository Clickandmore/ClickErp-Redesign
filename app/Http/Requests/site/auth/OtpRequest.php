<?php

namespace App\Http\Requests\site\auth;

use App\Http\Requests\MainRequest;

class OtpRequest extends MainRequest
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
            'code' => 'string',
            'phone_code' => 'string',
        ];
    }


    public function messages(): array
    {
        return [
            // الهاتف
            'phone.required' => __('ingaz.phone_required'),
            'phone.exists' => __('ingaz.phone_exists'),
        ];
    }


}
