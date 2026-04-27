<?php

namespace Nami\Hr\Http\Requests\Salary;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class EditSalaryRequest extends MainRequest
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
            'month_year' => 'required',
            'sanctions' => 'required',
            'employee_id' => 'required',
            'allowances' => 'required',
            'Insurances' => 'required',
            'tax','delays' => 'required',
            'attandance_days' => 'required',
            'final' => 'required',
        ];
    }
}
