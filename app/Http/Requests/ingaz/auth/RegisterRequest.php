<?php

namespace App\Http\Requests\ingaz\auth;

use App\Enums\api\UserTypeisEnum;
use App\Enums\RegisterFromType;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rules\Enum;
use App\Models\ingaz\User;
use App\Support\ExternalAuthSettings;

class RegisterRequest extends MainRequest
{
    protected function prepareForValidation()
    {
        if (!ExternalAuthSettings::usesOtpOnly()) {
            return;
        }

        $this->merge([
            'phone_code' => $this->input('phone_code', '+966'),
            'register_from' => $this->input('register_from', RegisterFromType::App->value),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $isFirstTime = !User::where('phone', $this->phone)->exists();

        $rules = [
            'name' => $isFirstTime ? 'required|string|min:2|max:255' : 'nullable|string|min:2|max:255',
            'register_from' => ['required',new Enum(RegisterFromType::class)],
            'phone_code' => 'required',
            'phone' => 'required|unique:users,phone',
        ];

        if (!ExternalAuthSettings::usesOtpOnly()) {
            $rules['password'] = 'required|min:6';
            $rules['password_confirmation'] = 'required|same:password';
        }

        return $rules;
    }
}
