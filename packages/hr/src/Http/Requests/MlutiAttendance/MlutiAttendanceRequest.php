<?php

namespace Nami\Hr\Http\Requests\MlutiAttendance;

use App\Http\Requests\MainRequest;
use Illuminate\Foundation\Http\FormRequest;

class MlutiAttendanceRequest extends MainRequest
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
            'attendance_time' => 'required',
            'leave_time' => 'required',
        ];
    }
}
