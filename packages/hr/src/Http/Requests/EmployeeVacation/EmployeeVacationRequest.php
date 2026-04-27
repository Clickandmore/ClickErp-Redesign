<?php

namespace Nami\Hr\Http\Requests\EmployeeVacation;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeVacationRequest extends MainRequest
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
            'vacation_id' => 'required',
            'value' => 'required',
            'from_date' => 'required',
            'to_date' => 'required',
            'days' => 'required|lte:value',
            'reason' => 'required',
            'vacation_file' => 'nullable',
        ];
    }

    public function messages()
    {
        return[
            'reason.required' => "يرجي ادخال ملاحظات مثل سبب الاجازة"
        ];
    }
}
