<?php

namespace App\Http\Requests\auth\profile;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;


class UpdateAdminRequest extends MainRequest
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
            'username' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('admins', 'username')->ignore($this->admin)],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', Rule::unique('admins')->ignore($this->admin)],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('guard_name', 'admin')],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'verification_phone' => ['nullable', 'string', 'max:255'],
            'identity_number' => ['nullable', 'string', 'max:255', Rule::unique('admins', 'identity_number')->ignore($this->admin)],
            'identity_expiry_date' => ['nullable', 'date'],
            'sex_type' => ['required', "in:0,1"],
            'sort' => ['nullable'],
            //            'permissions' => 'required|min:1'
        ];
    }
}
