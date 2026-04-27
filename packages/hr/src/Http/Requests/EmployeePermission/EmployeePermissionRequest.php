<?php

namespace Nami\Hr\Http\Requests\EmployeePermission;

use App\Http\Requests\MainRequest;

class EmployeePermissionRequest extends MainRequest
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
            'employee_id' => ['required'],
            'permission_id' => 'required',
            'file' => 'nullable',
            'date' => 'required|date|after_or_equal:today',
            "from_time" => "required|before:to_time",
            "to_time" => "required|after:from_time",
            'time' => ['required'],
            'reason' => 'required',
        ];
    }

    public function messages(): array
    {
        $locale = app()->getLocale();

        return $locale === 'ar' ? [
            'employee_id.required' => 'يجب اختيار الموظف.',
            'permission_id.required' => 'يجب اختيار نوع الإذن.',
            'date.required' => 'يجب تحديد التاريخ.',
            'date.date' => 'يجب أن يكون التاريخ بصيغة صحيحة.',
            'date.after_or_equal' => 'التاريخ يجب أن يكون اليوم أو بعده.',
            'from_time.required' => 'وقت البداية مطلوب.',
            'from_time.before' => 'يجب أن يكون وقت البداية قبل وقت النهاية.',
            'to_time.required' => 'وقت النهاية مطلوب.',
            'to_time.after' => 'يجب أن يكون وقت النهاية بعد وقت البداية.',
            'time.required' => 'مدة الإذن مطلوبة.',
            'reason.required' => 'يجب كتابة سبب الإذن.',
        ] : [
            'employee_id.required' => 'The employee must be selected.',
            'permission_id.required' => 'The type of permission must be selected.',
            'date.required' => 'The date is required.',
            'date.date' => 'The date must be a valid date format.',
            'date.after_or_equal' => 'The date must be today or later.',
            'from_time.required' => 'The start time is required.',
            'from_time.before' => 'The start time must be before the end time.',
            'to_time.required' => 'The end time is required.',
            'to_time.after' => 'The end time must be after the start time.',
            'time.required' => 'The duration of the permission is required.',
            'reason.required' => 'The reason for the permission is required.',
        ];
    }
}
