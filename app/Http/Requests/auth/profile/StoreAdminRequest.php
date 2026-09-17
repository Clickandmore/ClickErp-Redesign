<?php

namespace App\Http\Requests\auth\profile;

use App\Enums\auth\AdminTypes;
use App\Http\Requests\MainRequest;
use App\Http\Requests\auth\profile\Concerns\ExplainsAdminUniqueConflicts;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreAdminRequest extends MainRequest
{
    use ExplainsAdminUniqueConflicts;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $creatingEmployee = $this->boolean('create_employee');

        return [
            'name' => ['required', Rule::when($creatingEmployee, Rule::unique('hr_employee', 'name'))],
            'username' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9._@-]+$/', 'unique:admins,username'],
            'phone' => ['required', 'string', 'max:255', Rule::when($creatingEmployee, Rule::unique('hr_employee', 'phone'))],
            'email' => ['required', 'email', 'unique:admins', Rule::when($creatingEmployee, Rule::unique('hr_employee', 'email'))],
            'password' => ['required', 'string', 'min:6'],
            'admin_type' => ['required', new Enum(AdminTypes::class)],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('guard_name', 'admin')],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'verification_phone' => ['nullable', 'string', 'max:255'],
            'identity_number' => [$creatingEmployee ? 'required' : 'nullable', 'string', 'max:255', 'unique:admins,identity_number', Rule::when($creatingEmployee, Rule::unique('hr_employee', 'national_num'))],
            'identity_expiry_date' => [$creatingEmployee ? 'required' : 'nullable', 'date'],
            'sex_type' => ['required', "in:0,1"],
            'sort' => ['nullable'],
            'status' => ['nullable', 'in:active,suspended'],
            'note' => ['nullable', 'string', 'max:1000'],
            'create_employee' => ['nullable', 'boolean'],
            'employee_code' => ['nullable', 'string', 'max:255', Rule::unique('hr_employee', 'code')],
            'employee_address' => ['nullable', 'string', 'max:1000'],
            'employee_birthday' => [$creatingEmployee ? 'required' : 'nullable', 'date', 'before:today'],
            'employee_country_id' => [$creatingEmployee ? 'required' : 'nullable', Rule::exists('countries', 'id')],
            'employee_department_id' => [$creatingEmployee ? 'required' : 'nullable', Rule::exists('hr_department', 'id')],
            'employee_job_id' => [$creatingEmployee ? 'required' : 'nullable', Rule::exists('hr_jobs', 'id')],
            'employee_shift_id' => [$creatingEmployee ? 'required' : 'nullable', Rule::exists('hr_shifts', 'id')],
            'employee_hired_date' => [$creatingEmployee ? 'required' : 'nullable', 'date'],
            'employee_contract_end_date' => [$creatingEmployee ? 'required' : 'nullable', 'date', 'after_or_equal:employee_hired_date'],
            'employee_salary' => [$creatingEmployee ? 'required' : 'nullable', 'numeric', 'min:0'],
            'employee_net_salary' => ['nullable', 'numeric', 'min:0'],

            //            'permissions' => 'required|min:1',
        ];
    }

    public function messages()
    {
        return array_merge([
            'username.regex' => __('validation.username_format'),
        ], $this->uniqueConflictMessages());
    }
}
