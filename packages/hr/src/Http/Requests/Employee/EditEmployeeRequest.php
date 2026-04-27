<?php

namespace Nami\Hr\Http\Requests\Employee;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditEmployeeRequest extends MainRequest
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
        return [
            'name' =>  ['required', Rule::unique('hr_employee')->ignore(request()->input('employeee_id')),],
            'image' => 'nullable',
            'code' => ['nullable', Rule::unique('hr_employee')->ignore(request()->input('employeee_id'))],
            'address' => 'nullable',
            'phone' => ['required', Rule::unique('hr_employee')->ignore(request()->input('employeee_id'))],
            'birthday' => 'required',
            'email' => ['required', Rule::unique('hr_employee')->ignore(request()->input('employeee_id'))],
            'national_num' => ['required', Rule::unique('hr_employee')->ignore(request()->input('employeee_id'))],
            'country_id' => 'required',
            'deparment_id' => 'required',
            'job_id' => 'required',
            'shift_id' => 'required',
            'hired_date' => 'required',
            'contract_end_date' => 'required',
            'salary' => 'required',
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
        ];
    }
}
