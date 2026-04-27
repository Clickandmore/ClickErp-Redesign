<?php

namespace Nami\Hr\Http\Requests\Salary;

use Illuminate\Validation\Rule;
use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class SalaryRequest extends MainRequest
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

        $employeeId = $this->input('employee_id');
        $monthYear = $this->input('month_year');

        $uniqueRule = Rule::unique('hr_employee_salary')->where(function ($query) use ($employeeId, $monthYear) {
            return $query->where('employee_id', $employeeId)->where('month_year', $monthYear);
        });
        return [
            'month_year' => 'required',
            'sanctions' => 'required',
            'employee_id' => ['required', $uniqueRule],
            'allowances' => 'required',
            'Insurances' => 'required',
            'tax','delays' => 'required',
            'attandance_days' => 'required',
            'final' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'month_year.required' => 'يرجى تحديد الشهر',
            'employee_id.unique' => 'تم صرف الراتب لهذا الموظف من قبل',
        ];
    }
}
