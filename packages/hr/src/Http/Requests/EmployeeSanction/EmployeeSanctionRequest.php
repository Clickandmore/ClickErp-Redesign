<?php

namespace Nami\Hr\Http\Requests\EmployeeSanction;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeSanctionRequest extends MainRequest
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
            'employee_id' => 'required',
            'sanction_id' => 'required',
            'value' => 'required',
            'date' => 'required',
            'with_salary' => 'nullable',
            'reason' => 'required',
            'file' => 'nullable',
        ];
    }
}
