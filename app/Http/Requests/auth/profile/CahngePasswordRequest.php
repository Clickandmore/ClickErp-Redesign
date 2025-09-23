<?php

namespace App\Http\Requests\auth\profile;

use App\Http\Requests\MainRequest;
use App\Rules\MatchOldPassword;


class CahngePasswordRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'current_password' => [
                'sometimes',
                'nullable',
                function ($attribute, $value, $fail) {
                    $rule = new MatchOldPassword();
                    if (!empty($value) && ! $rule->passes($attribute, $value)) {
                        $fail($rule->message());
                    }
                },

            ],
            'new_password' => ['required','min:8'],
            'new_confirm_password' => ['same:new_password'],
        ];
    }
    public function messages()
    {
        return [
            'current_password.sometimes' => __('ingaz.current_password_required'),
            'current_password.nullable'  => __('ingaz.current_password_required'),
            'new_password.required'      => __('ingaz.new_password_required'),
            'new_password.min'           => __('ingaz.new_password_min'),
            'new_confirm_password.same'  => __('ingaz.new_password_confirm_same'),
        ];
    }

}
