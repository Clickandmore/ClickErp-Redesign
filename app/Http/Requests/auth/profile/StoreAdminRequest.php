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
            'username' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:admins,username'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:admins'],
            'password' => ['required', 'string', 'min:6'],
            'admin_type' => ['required', new Enum(AdminTypes::class)],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('guard_name', 'admin')],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'verification_phone' => ['nullable', 'string', 'max:255'],
            'identity_number' => ['required', 'string', 'max:255', 'unique:admins,identity_number'],
            'identity_expiry_date' => ['required', 'date'],
            'sex_type' => ['required', "in:0,1"],
            'sort' => ['nullable'],

            //            'permissions' => 'required|min:1',
        ];
    }
}
