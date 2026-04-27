<?php

namespace Nami\Hr\Http\Requests\Employee;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends MainRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => 'required|unique:hr_employee,name',
            'image' => 'nullable',
            'code' => 'nullable|unique:hr_employee,code',
            'address' => 'nullable',
            'phone' => 'required|unique:hr_employee,phone',
            'birthday' => 'required',
            'email' => 'required|unique:hr_employee,email',
            'national_num' => 'required|unique:hr_employee,national_num',
            'country_id' => 'required',
            'deparment_id' => 'required',
            'responsible_employee_id' => 'nullable|exists:hr_employee,id',
            'job_id' => 'required',
            'shift_id' => 'required',
            'hired_date' => 'required',
            'contract_end_date' => 'required',
            'salary' => 'required',
            'vacations.*' => 'nullable',
            'total_days.*' => 'required_with:vacations.*',
            'token_days.*' => 'required_with:vacations.*|lte:total_days.*',
        ];

        return $rules;
        /* [
            'name' => 'required',
            'image' => 'nullable',
            'code' => 'nullable',
            'address' => 'nullable',
            'phone' => 'required',
            'birthday' => 'required',
            'email' => 'required',
            'national_num' => 'required',
            'country_id' => 'required',
            'deparment_id' => 'required',
            'job_id' => 'required',
            'shift_id' => 'required',
            'hired_date' => 'required',
            'contract_end_date' => 'required',
            'salary' => 'required',

            'vacations.*' => 'nullable|exists:hr_vacations,id',
            'total_days' => 'required_if:vacations,present',
            'token_days' => 'required_if:vacations,present',

            // 'title' => 'nullable',
            // 'file' => 'nullable',
            // 'allowances' => 'nullable',
            // 'value' => 'nullable',
            // 'type' => 'nullable',
            // 'insurance' => 'nullable',
            // 'tax' => 'nullable',
            // 'vacations' => 'nullable',
            // 'total_days' => 'nullable',
            // 'token_days' => 'nullable',
            // 'remain_days' => 'nullable',
        ]; */
    }
}
