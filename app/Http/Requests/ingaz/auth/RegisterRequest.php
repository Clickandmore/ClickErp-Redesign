<?php

namespace App\Http\Requests\ingaz\auth;

use App\Enums\api\UserTypeisEnum;
use App\Enums\RegisterFromType;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rules\Enum;
use App\Models\ingaz\User;

class RegisterRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $isFirstTime = !User::where('phone', $this->phone)->exists();

        return [
            'name' => $isFirstTime ? 'required|string|min:2|max:255' : 'nullable|string|min:2|max:255',
            'register_from' => ['required',new Enum(RegisterFromType::class)],
            'password' => 'required|min:6',
            'password_confirmation' => 'required|same:password',
            'phone_code' => 'required',
            'phone' => 'required|unique:users,phone',
        ];
    }
}
