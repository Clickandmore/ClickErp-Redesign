<?php

namespace App\Http\Requests\site\auth;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class SettingsRequest extends MainRequest
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

    public function rules()
    {
        return [
            'name' => 'nullable',
            'city' => 'nullable|exists:cities,id',
            'phone' => [
                'nullable',
                Rule::unique('users', 'phone')->ignore(auth()->id()),
            ],
            'g-recaptcha-response' => 'required',

        ];
    }


    public function messages()
    {
        return [
            'phone.unique' => __('ingaz.phone_unique'),
            'g-recaptcha-response.required' => __('ingaz.captcha_required'),

        ];
    }


}
