<?php

namespace App\Http\Requests\ingaz\auth;

use App\Enums\api\UserTypeisEnum;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rules\Enum;

class LoginRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'phone' => 'required',
            'password' => 'required|min:6',
            'name' => 'nullable|string|min:2|max:255',
        ];
    }
}
