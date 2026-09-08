<?php

namespace App\Http\Requests\auth\profile;

use App\Enums\auth\AdminTypes;
use App\Http\Requests\MainRequest;
use App\Http\Requests\auth\profile\Concerns\ExplainsAdminUniqueConflicts;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;


class UpdateAdminRequest extends MainRequest
{
    use ExplainsAdminUniqueConflicts;

    protected function ignoredAdminId(): ?int
    {
        $id = $this->route('admin');

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required'],
            'username' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9._@-]+$/', Rule::unique('admins', 'username')->ignore($this->admin)],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['required', Rule::unique('admins')->ignore($this->admin)],
            'password' => ['nullable', 'string', 'min:6'],
            'admin_type' => ['required', new Enum(AdminTypes::class)],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('guard_name', 'admin')],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'verification_phone' => ['nullable', 'string', 'max:255'],
            'identity_number' => ['nullable', 'string', 'max:255', Rule::unique('admins', 'identity_number')->ignore($this->admin)],
            'identity_expiry_date' => ['nullable', 'date'],
            'sex_type' => ['required', "in:0,1"],
            'sort' => ['nullable'],
            'status' => ['nullable', 'in:active,suspended'],
            'note' => ['nullable', 'string', 'max:1000'],
            //            'permissions' => 'required|min:1'
        ];
    }

    public function messages()
    {
        return array_merge([
            'username.regex' => __('validation.username_format'),
        ], $this->uniqueConflictMessages());
    }
}
