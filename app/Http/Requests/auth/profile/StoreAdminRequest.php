<?php

namespace App\Http\Requests\auth\profile;

use App\Enums\auth\AdminTypes;
use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreAdminRequest extends MainRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required'],
            'username' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9._@-]+$/', 'unique:admins,username'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:admins'],
            'password' => ['required', 'string', 'min:6'],
            'admin_type' => ['required', new Enum(AdminTypes::class)],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('guard_name', 'admin')],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'verification_phone' => ['nullable', 'string', 'max:255'],
            'identity_number' => ['nullable', 'string', 'max:255', 'unique:admins,identity_number'],
            'identity_expiry_date' => ['nullable', 'date'],
            'sex_type' => ['required', "in:0,1"],
            'sort' => ['nullable'],
            'status' => ['nullable', 'in:active,suspended'],
            'note' => ['nullable', 'string', 'max:1000'],

            //            'permissions' => 'required|min:1',
        ];
    }

    public function messages()
    {
        return [
            'username.regex' => __('validation.username_format'),
        ];
    }
}
