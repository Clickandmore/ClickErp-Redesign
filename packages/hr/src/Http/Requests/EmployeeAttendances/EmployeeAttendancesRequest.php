<?php

namespace Nami\Hr\Http\Requests\EmployeeAttendances;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeAttendancesRequest extends MainRequest
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
            'employee_id' => 'required|exists:hr_employee,id',
            'date' => 'required',
            'type' => 'required',
            'attendance_time' => 'required_if:type,1,2',
            'leave_time' => 'required_if:type,1',
            'month_year' => 'required',
            'deducted_from_salary' => 'nullable',
            'deducted_from_salary_vlaue' => 'nullable',
        ];
    }


    public function messages()
    {
        return[
            'leave_time.required_if' => __('hr.leave_time.required_if'),
            'attendance_time.required_if' => __('hr.attendance_time.required_if'),
        ];
    }
}
